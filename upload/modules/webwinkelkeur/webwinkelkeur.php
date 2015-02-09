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

        Configuration::updateValue('WEBWINKELKEUR_SIDEBAR', '');
        Configuration::updateValue('WEBWINKELKEUR_SIDEBAR_POSITION', 'left');
        Configuration::updateValue('WEBWINKELKEUR_INVITE', '');
        Configuration::updateValue('WEBWINKELKEUR_TOOLTIP', '1');
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
        if(!Configuration::get('WEBWINKELKEUR_SIDEBAR')
           && !Configuration::get('WEBWINKELKEUR_TOOLTIP')
           && !Configuration::get('WEBWINKELKEUR_JAVASCRIPT')
        ) {
            return "<!-- WebwinkelKeur: JS disabled -->\n";
        }

        $shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID');
        if(!$shop_id)
            return "<!-- WebwinkelKeur: shop_id not set -->\n";
        if(!ctype_digit($shop_id))
            return "<!-- WebwinkelKeur: shop_id not a number -->\n";

        $settings = array(
            '_webwinkelkeur_id' => (int) $shop_id,
            '_webwinkelkeur_sidebar' => !!Configuration::get('WEBWINKELKEUR_SIDEBAR'),
            '_webwinkelkeur_tooltip' => !!Configuration::get('WEBWINKELKEUR_TOOLTIP'),
        );

        if($sidebar_position = Configuration::get('WEBWINKELKEUR_SIDEBAR_POSITION'))
            $settings['_webwinkelkeur_sidebar_position'] = $sidebar_position;

        if($sidebar_top = Configuration::get('WEBWINKELKEUR_SIDEBAR_TOP'))
            $settings['_webwinkelkeur_sidebar_top'] = $sidebar_top;

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
                c.email
            FROM `" . _DB_PREFIX_ . "orders` o
            INNER JOIN `" . _DB_PREFIX_ . "order_state` os ON
                os.id_order_state = o.current_state
            INNER JOIN `" . _DB_PREFIX_ . "customer` c USING (id_customer)
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
                    c.email
                FROM `" . _DB_PREFIX_ . "orders` o
                INNER JOIN `" . _DB_PREFIX_ . "order_history` oh ON
                    oh.id_order = o.id_order
                INNER JOIN `" . _DB_PREFIX_ . "order_state` os ON
                    os.id_order_state = oh.id_order_state
                INNER JOIN `" . _DB_PREFIX_ . "order_state_lang` osl ON
                    osl.id_order_state = osl.id_order_state
                INNER JOIN `" . _DB_PREFIX_ . "customer` c USING (id_customer)
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
                $parameters = array(
                    'id'        => $shop_id,
                    'password'  => $api_key,
                    'email'     => $order['email'],
                    'order'     => $order['id_order'],
                    'delay'     => $invite_delay,
                );
                if($invite == 2)
                    $parameters['noremail'] = '1';
                $url = 'http://www.webwinkelkeur.nl/api.php?' . http_build_query($parameters);
                $retriever = new Peschar_URLRetriever();
                $response = $retriever->retrieve($url);
                if(preg_match('|^Success:|', $response)
                   || preg_match('|invite already sent|', $response)
                ) {
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

            Configuration::updateValue('WEBWINKELKEUR_SIDEBAR',
                !!tools::getValue('sidebar'));
            Configuration::updateValue('WEBWINKELKEUR_SIDEBAR_POSITION',
                tools::getValue('sidebar_position'));
            Configuration::updateValue('WEBWINKELKEUR_SIDEBAR_TOP',
                tools::getValue('sidebar_top'));

            Configuration::updateValue('WEBWINKELKEUR_INVITE',
                (int) tools::getValue('invite'));

            Configuration::updateValue('WEBWINKELKEUR_TOOLTIP',
                !!tools::getValue('tooltip'));

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
