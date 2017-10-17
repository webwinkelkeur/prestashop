<?php

if(!defined('_PS_VERSION_'))
    exit;

require_once dirname(__FILE__) . '/vendor/Peschar/URLRetriever.php';

class WebwinkelKeur extends Module {
    public function __construct() {
        $this->name = 'webwinkelkeur';
        $this->tab = 'advertising_marketing';
        $this->version = '1.2.0';
        $this->author = 'Albert Peschar';
        $this->need_instance = 0;
        $this->module_key = '905d0071afeef0d6aaf724f0a8bb801f';

        parent::__construct();
        
        $this->displayName = $this->l('WebwinkelKeur');
        $this->description = $this->l('Integreer de WebwinkelKeur sidebar in uw webwinkel.');
    }

    public function install() {
        if(!parent::install())
            return false;

        if(!$this->registerHook('header'))
            return false;

        if(!$this->registerHook('footer'))
            return false;

        if(!$this->registerHook('backOfficeTop'))
            return false;

        Db::getInstance()->execute("
            ALTER TABLE `" . _DB_PREFIX_ . "orders`
                ADD COLUMN `webwinkelkeur_invite_sent` tinyint(1) NOT NULL,
                ADD COLUMN `webwinkelkeur_invite_tries` int NOT NULL,
                ADD COLUMN `webwinkelkeur_invite_time` int NOT NULL,
                ADD KEY `webwinkelkeur_invite_sent`
                    (`webwinkelkeur_invite_sent`, `webwinkelkeur_invite_tries`)
        ");

        Db::getInstance()->execute("
            UPDATE `" . _DB_PREFIX_ . "orders`
                SET `webwinkelkeur_invite_sent` = 1
        ");
        
        Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS
                `" . _DB_PREFIX_ . "webwinkelkeur_invite_error`
            (
              `id` int NOT NULL AUTO_INCREMENT,
              `url` varchar(255) NOT NULL,
              `response` text NOT NULL,
              `time` bigint NOT NULL,
              PRIMARY KEY (`id`),
              KEY `time` (`time`)
            ) ENGINE=MyISAM
        ");
        Db::getInstance()->execute("
            DELETE FROM `" . _DB_PREFIX_ . "webwinkelkeur_invite_error`
        ");

        Configuration::updateValue('WEBWINKELKEUR_INVITE', '');
        Configuration::updateValue('WEBWINKELKEUR_JAVASCRIPT', '1');

        return true;
    }

    public function uninstall() {
        foreach(array('invite_sent', 'invite_tries', 'invite_time') as $column)
            Db::getInstance()->execute("
                ALTER TABLE `" . _DB_PREFIX_ . "orders` DROP COLUMN `webwinkelkeur_$column`
            ");

        Db::getInstance()->execute("
            ALTER TABLE `" . _DB_PREFIX_ . "orders` DROP KEY `webwinkelkeur_invite_sent`
        ");

        return parent::uninstall();
    }

    public function hookHeader($params) {
        if(!Configuration::get('WEBWINKELKEUR_JAVASCRIPT')) {
            return "<!-- WebwinkelKeur: JS disabled -->\n";
        }

        $shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID');
        if(!$shop_id)
            return "<!-- WebwinkelKeur: shop_id not set -->\n";
        if(!ctype_digit($shop_id))
            return "<!-- WebwinkelKeur: shop_id not a number -->\n";

        $settings = array(
            '_webwinkelkeur_id' => (int) $shop_id,
        );

        ob_start();
        require dirname(__FILE__) . '/sidebar.php';
        return ob_get_clean();
    }

    public function hookFooter($params) {
        if(!Configuration::get('WEBWINKELKEUR_RICH_SNIPPET')
           || !($shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID'))
           || !ctype_digit($shop_id)
        )
            return '';

        $html = $this->getRichSnippet($shop_id);

        if($html)
            return $html;
    }

    private function getRichSnippet($shop_id) {
        $tmp_dir = @sys_get_temp_dir();
        if(!@is_writable($tmp_dir))
            $tmp_dir = '/tmp';
        if(!@is_writable($tmp_dir))
            return;

        $url = sprintf('http://www.webwinkelkeur.nl/shop_rich_snippet.php?id=%s',
                       (int) $shop_id);

        $cache_file = $tmp_dir . DIRECTORY_SEPARATOR . 'WEBWINKELKEUR_'
            . md5(__FILE__) . '_' . md5($url);

        $fp = @fopen($cache_file, 'rb');
        if($fp)
            $stat = @fstat($fp);

        if($fp && $stat && $stat['mtime'] > time() - 7200
           && ($json = @stream_get_contents($fp))
        ) {
            $data = json_decode($json, true);
        } else {
            $context = @stream_context_create(array(
                'http' => array('timeout' => 3),
            ));
            $json = @file_get_contents($url, false, $context);
            if(!$json) return;

            $data = @json_decode($json, true);
            if(empty($data['result'])) return;

            $new_file = $cache_file . '.' . uniqid();
            if(@file_put_contents($new_file, $json))
                @rename($new_file, $cache_file) or @unlink($new_file);
        }

        if($fp)
            @fclose($fp);
        
        if($data['result'] == 'ok')
            return $data['content'];
    }

    public function getOrdersToInvite($db, $ps_shop_id) {
        $max_time = time() - 1800;

        $query = $db->executeS("
            SELECT
                o.*,
                c.email,
                a.firstname,
                a.lastname,
                l.language_code
            FROM `" . _DB_PREFIX_ . "orders` o
            INNER JOIN `" . _DB_PREFIX_ . "order_state` os ON
                os.id_order_state = o.current_state
            INNER JOIN `" . _DB_PREFIX_ . "customer` c USING (id_customer)
            LEFT JOIN `" . _DB_PREFIX_ . "address` a ON o.id_address_invoice = a.id_address
            LEFT JOIN `" . _DB_PREFIX_ . "lang` l ON o.id_lang = l.id_lang
            WHERE
                o.webwinkelkeur_invite_sent = 0
                AND o.id_shop = $ps_shop_id
                AND o.webwinkelkeur_invite_tries < 10
                AND o.webwinkelkeur_invite_time < $max_time
                AND os.shipped = 1
        ");

        if($query === false)
            $query = $db->executeS("
                SELECT
                    o.*,
                    c.email,
                a.firstname,
                a.lastname,
                l.language_code
                FROM `" . _DB_PREFIX_ . "orders` o
                INNER JOIN `" . _DB_PREFIX_ . "order_history` oh ON
                    oh.id_order = o.id_order
                INNER JOIN `" . _DB_PREFIX_ . "order_state` os ON
                    os.id_order_state = oh.id_order_state
                INNER JOIN `" . _DB_PREFIX_ . "order_state_lang` osl ON
                    osl.id_order_state = osl.id_order_state
                INNER JOIN `" . _DB_PREFIX_ . "customer` c USING (id_customer)
                LEFT JOIN `" . _DB_PREFIX_ . "address` a ON o.id_address_invoice = a.id_address
                LEFT JOIN `" . _DB_PREFIX_ . "lang` l ON o.id_lang = l.id_lang
                WHERE
                    o.webwinkelkeur_invite_sent = 0
                    AND o.webwinkelkeur_invite_tries < 10
                    AND o.webwinkelkeur_invite_time < $max_time
                    AND osl.template = 'shipped'
                GROUP BY
                    o.id_order
            ");

        return $query;
    }

    public function sendInvites($ps_shop_id) {
        if(!($shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID', null, null, $ps_shop_id))
           || !($api_key = Configuration::get('WEBWINKELKEUR_API_KEY', null, null, $ps_shop_id))
           || !($invite = Configuration::get('WEBWINKELKEUR_INVITE', null, null, $ps_shop_id))
        )
            return;
        
        $invite_delay = (int) Configuration::get('WEBWINKELKEUR_INVITE_DELAY', null, null, $ps_shop_id);

        $db = Db::getInstance();

        $orders = $this->getOrdersToInvite($db, $ps_shop_id);

        if(!$orders)
            return;

        foreach($orders as $order) {
            $db->execute("
                UPDATE `" . _DB_PREFIX_ . "orders`
                SET
                    webwinkelkeur_invite_tries = webwinkelkeur_invite_tries + 1,
                    webwinkelkeur_invite_time = " . time() . "
                WHERE
                    id_order = " . $order['id_order'] . "
                    AND webwinkelkeur_invite_tries = " . $order['webwinkelkeur_invite_tries'] . "
                    AND webwinkelkeur_invite_time = " . $order['webwinkelkeur_invite_time'] . "
            ");
            if($db->Affected_Rows()) {
                $post = array(
                    'email'     => $order['email'],
                    'order'     => $order['id_order'],
                    'delay'     => $invite_delay,
                    'language'      => str_replace('-', '_', $order['language_code']),
                    'customername' => $order['firstname'].' '.$order['lastname'],
                    'client'    => 'prestashop'
                );
                if($invite == 2)
                    $post['noremail'] = '1';

                $url = "https://dashboard.webwinkelkeur.nl/api/1.0/invitations.json?id=" . $shop_id . "&code=" . $api_key;
                $curl = curl_init($url);
                curl_setopt_array($curl, [
                    CURLOPT_HTTPHEADER => [
                        "Content-type" => "application/json"
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $post
                ]);
                $response = curl_exec($curl);
                curl_close($curl);

                if ($response === false) {
                    $success = false;
                } else {
                    $data = json_decode($response);
                    $success = is_array($data) && isset ($data['status']) && $data['status'] == 'success';
                }

                if($success) {
                    $db->execute("UPDATE `" . _DB_PREFIX_ . "orders` SET webwinkelkeur_invite_sent = 1 WHERE id_order = " . (int) $order['id_order']);
                } else {
                    $db->execute("INSERT INTO `" . _DB_PREFIX_ . "webwinkelkeur_invite_error` SET url = '" . pSQL($url, true) . "', response = '" . pSQL($response, true) . "', time = " . time());
                }
            }
        }
    }


    public function hookBackofficeTop() {
        if(method_exists('Shop', 'getCompleteListOfShopsID'))
            foreach(Shop::getCompleteListOfShopsID() as $shop)
                $this->sendInvites($shop);
        else
            $this->sendInvites(null);
    }

    public function getContent() {
        $db = Db::getInstance();
        $errors = array();
        $success = false;

        if(tools::isSubmit('webwinkelkeur')) {
            $shop_id = tools::getValue('shop_id');
            if(strlen($shop_id))
                $shop_id = (int) $shop_id;
            else
                $shop_id = '';

            $api_key = tools::getValue('api_key');

            if(!$shop_id || !$api_key)
                $errors[] = $this->l('Om de sidebar weer te geven of uitnodigingen te versturen, zijn uw webwinkel en API key vereist.');

            Configuration::updateValue('WEBWINKELKEUR_SHOP_ID', trim($shop_id));
            Configuration::updateValue('WEBWINKELKEUR_API_KEY', trim($api_key));

            Configuration::updateValue('WEBWINKELKEUR_INVITE',
                (int) tools::getValue('invite'));

            $invite_delay = tools::getValue('invite_delay');
            if(strlen($invite_delay) == 0) $invite_delay = 3;
            Configuration::updateValue('WEBWINKELKEUR_INVITE_DELAY', (int) $invite_delay);

            Configuration::updateValue('WEBWINKELKEUR_JAVASCRIPT',
                !!tools::getValue('javascript'));

            Configuration::updateValue('WEBWINKELKEUR_RICH_SNIPPET',
                !!tools::getValue('rich_snippet'));

            if(sizeof($errors) == 0)
                $success = true;
        }

        $invite_errors = $db->executeS("
            SELECT *
            FROM `" . _DB_PREFIX_ . "webwinkelkeur_invite_error`
            WHERE
                time > " . (time() - 86400 * 3) . "
            ORDER BY time
        ");

        ob_start();
        foreach($errors as $error)
            echo $this->displayError($error);
        if($success)
            echo $this->displayConfirmation($this->l('Uw wijzigingen zijn opgeslagen.'));
        require dirname(__FILE__) . '/config_form.php';
        return ob_get_clean();
    }

    public function escape($string) {
        return htmlentities($string, ENT_QUOTES, 'UTF-8');
    }
}
