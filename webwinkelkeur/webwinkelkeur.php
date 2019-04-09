<?php

if(!defined('_PS_VERSION_'))
    exit;

require_once dirname(__FILE__) . '/vendor/Peschar/URLRetriever.php';

class WebwinkelKeur extends Module {
    public function __construct() {
        $this->name = 'webwinkelkeur';
        $this->tab = 'advertising_marketing';
        $this->version = '1.3.0';
        $this->author = '<a href="https://kiboit.com">Kibo IT</a>';
        $this->need_instance = 0;
        $this->module_key = '905d0071afeef0d6aaf724f0a8bb801f';

        parent::__construct();
        
        $this->displayName = $this->l('WebwinkelKeur');
        $this->description = $this->l('Integreer de WebwinkelKeur sidebar in uw webwinkel.');
    }

    public function install() {
        if(!parent::install())
            return false;

        Db::getInstance()->execute("
            ALTER TABLE `" . _DB_PREFIX_ . "orders`
                ADD COLUMN `webwinkelkeur_invite_sent` tinyint(1) NOT NULL DEFAULT 0,
                ADD COLUMN `webwinkelkeur_invite_tries` int NOT NULL DEFAULT 0,
                ADD COLUMN `webwinkelkeur_invite_time` int NOT NULL DEFAULT 0,
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
                COALESCE(o.webwinkelkeur_invite_sent, 0) = 0
                AND o.id_shop = $ps_shop_id
                AND COALESCE(o.webwinkelkeur_invite_tries, 0) < 10
                AND COALESCE(o.webwinkelkeur_invite_time, 0) < $max_time
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
                    COALESCE(o.webwinkelkeur_invite_sent, 0) = 0
                    AND COALESCE(o.webwinkelkeur_invite_tries, 0) < 10
                    AND COALESCE(o.webwinkelkeur_invite_time, 0) < $max_time
                    AND osl.template = 'shipped'
                GROUP BY
                    o.id_order
            ");

        return $query;
    }

    public function getOrderLines($db, $order_id) {
        $query = "SELECT * FROM `" . _DB_PREFIX_ . "order_detail` WHERE id_order = $order_id";
        $results = $db->executeS($query);
        return $results;
    }

    public function getOrderAddress($db, $address_id) {
        $query = "SELECT * FROM `" . _DB_PREFIX_ . "address` WHERE id_address = $address_id";
        return $db->executeS($query);
    }

    public function getCustomerInfo($db, $customer_id) {
        $query = "SELECT * FROM `" . _DB_PREFIX_ . "customer` WHERE id_customer = $customer_id";
        $customer_info = $db->executeS($query);
        $customer_info = $customer_info[0];
        unset ($customer_info['passwd']);
        unset ($customer_info['last_passwd_gen']);
        unset ($customer_info['secure_key']);
        unset ($customer_info['reset_password_token']);
        unset ($customer_info['reset_password_validity']);
        return $customer_info;
    }

    public function sendInvites($ps_shop_id) {
        if(!($shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID', null, null, $ps_shop_id))
           || !($api_key = Configuration::get('WEBWINKELKEUR_API_KEY', null, null, $ps_shop_id))
           || !($invite = Configuration::get('WEBWINKELKEUR_INVITE', null, null, $ps_shop_id))
        )
            return;
        
        $invite_delay = (int) Configuration::get('WEBWINKELKEUR_INVITE_DELAY', null, null, $ps_shop_id);
        $with_order_data = !Configuration::get('WEBWINKELKEUR_LIMIT_ORDER_DATA', null, null, $ps_shop_id);

        $db = Db::getInstance();

        $orders = $this->getOrdersToInvite($db, $ps_shop_id);

        if(!$orders)
            return;

        foreach($orders as $order) {
            $invoice_address = $this->getOrderAddress($db, $order['id_address_invoice'])[0];
            $delivery_address = $this->getOrderAddress($db, $order['id_address_delivery'])[0];
            $phones = array_unique(array_filter([
                $invoice_address['phone'],
                $invoice_address['phone_mobile'],
                $delivery_address['phone'],
                $delivery_address['phone_mobile']
            ]));

            $post = array(
                'email'     => $order['email'],
                'order'     => $order['id_order'],
                'delay'     => $invite_delay,
                'language'      => str_replace('-', '_', $order['language_code']),
                'customer_name' => $order['firstname'].' '.$order['lastname'],
                'phone_numbers' => $phones,
                'order_total' => $order['total_paid'],
                'client'    => 'prestashop',
                'platform_version' => _PS_VERSION_,
                'plugin_version' => $this->version

            );
            if($invite == 2) {
                $post['max_invitations_per_email'] = '1';
            }

            if ($with_order_data) {
                $order_lines = $this->getOrderLines($db, $order['id_order']);
                $customer_info = $this->getCustomerInfo($db, $order['id_customer']);

                array_walk($order_lines, function (&$line) {
                    $images = Image::getImages(
                        Context::getContext()->language->id,
                        $line['product_id'],
                        $line['product_attribute_id']
                    );
                    if (empty ($images)) {
                        $images = Image::getImages(
                            Context::getContext()->language->id,
                            $line['product_id']
                        );
                    }
                    $product = new Product($line['product_id'], false, Context::getContext()->language->id);
                    foreach ($images as $image) {
                        $line['product_image'][] = (new Link())->getImageLink(
                            $product->link_rewrite,
                            $image['id_image'],
                            'large_default'
                        );
                    }
                });
                $post['order_data'] = json_encode([
                    'order' => $order,
                    'products' => $order_lines,
                    'customer' => $customer_info,
                    'delivery_address' => $delivery_address,
                    'invoice_address' => $invoice_address
                ]);
            }

            $db->execute("
                UPDATE `" . _DB_PREFIX_ . "orders`
                SET
                    webwinkelkeur_invite_tries = webwinkelkeur_invite_tries + 1,
                    webwinkelkeur_invite_time = " . time() . "
                WHERE
                    id_order = " . $order['id_order'] . "
                    AND COALESCE(webwinkelkeur_invite_tries, 0) = " . (int) $order['webwinkelkeur_invite_tries'] . "
                    AND COALESCE(webwinkelkeur_invite_time, 0) = " . (int) $order['webwinkelkeur_invite_time'] . "
            ");
            if($db->Affected_Rows()) {
                $url = "https://dashboard.webwinkelkeur.nl/api/1.0/invitations.json?id=" . $shop_id . "&code=" . $api_key;
                $retriever = new Peschar_URLRetriever();
                $response = $retriever->retrieve($url, $post);

                if ($response === false) {
                    $success = false;
                } else {
                    $data = json_decode($response, JSON_OBJECT_AS_ARRAY);
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

            Configuration::updateValue('WEBWINKELKEUR_LIMIT_ORDER_DATA',
                !!tools::getValue('limit_order_data'));

            $this->registerHook('header');
            $this->registerHook('footer');
            $this->registerHook('backOfficeTop');

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
