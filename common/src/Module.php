<?php
namespace Valued\PrestaShop;

use Module as PSModule;

abstract class Module extends PSModule {
    /** @return string */
    abstract protected function getModuleKey();

    /** @return string */
    abstract protected function getDisplayName();

    /** @return string */
    abstract protected function getDescription();

    public function __construct() {
        $this->name = $this->getName();
        $this->tab = 'advertising_marketing';
        $this->version = '$VERSION$';
        $this->author = 'Albert Peschar (kiboit.com)';
        $this->need_instance = 0;
        $this->module_key = $this->getModuleKey();

        parent::__construct();

        $this->displayName = $this->getDisplayName();
        $this->description = $this->getDescription();
    }

    protected function getName() {
        return strtolower(static::class);
    }

    public function install() {
        if (!parent::install()) {
            return false;
        }

        Db::getInstance()->execute("
            ALTER TABLE `{$this->getTableName('orders')}`
                ADD COLUMN `{$this->getPluginColumnName('invite_sent')}` tinyint(1) NOT NULL DEFAULT 0,
                ADD COLUMN `{$this->getPluginColumnName('invite_tries')}` int NOT NULL DEFAULT 0,
                ADD COLUMN `{$this->getPluginColumnName('invite_time')}` int NOT NULL DEFAULT 0,
                ADD KEY `{$this->getPluginColumnName('invite_sent')}` (
                    `{$this->getPluginColumnName('invite_sent')}`,
                    `{$this->getPluginColumnName('invite_tries')}`
                )
        ");

        Db::getInstance()->execute("
            CREATE TABLE IF NOT EXISTS
                `{$this->getPluginTableName('invite_error')}`
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
            DELETE FROM `{$this->getPluginTableName('invite_error')}`
        ");

        Configuration::updateValue($this->getConfigName('INVITE'), '');
        Configuration::updateValue($this->getConfigName('JAVASCRIPT'), '1');

        return true;
    }

    public function uninstall() {
        foreach (['invite_sent', 'invite_tries', 'invite_time'] as $column) {
            Db::getInstance()->execute("
                ALTER TABLE `{$this->getTableName('orders')}`
                DROP COLUMN `{$this->getPluginColumnName($column)}`
            ");
        }

        Db::getInstance()->execute("
            ALTER TABLE `{$this->getTableName('orders')}`
            DROP KEY `{$this->getPluginColumnName('invite_sent')}`
        ");

        return parent::uninstall();
    }

    public function hookHeader($params) {
        if (!Configuration::get($this->getConfigName('JAVASCRIPT'))) {
            return "<!-- {$this->getDisplayName()}: JS disabled -->\n";
        }

        $shop_id = Configuration::get($this->getConfigName('SHOP_ID'));
        if (!$shop_id) {
            return "<!-- {$this->getDisplayName()}: shop_id not set -->\n";
        }
        if (!ctype_digit($shop_id)) {
            return "<!-- {$this->getDisplayName()}: shop_id not a number -->\n";
        }

        $settings = [
            "_{$this->getName()}_id" => (int) $shop_id,
        ];

        ob_start();
        require dirname(__FILE__) . '/sidebar.php';
        return ob_get_clean();
    }

    public function hookFooter($params) {
        if (!Configuration::get($this->getConfigName('RICH_SNIPPET'))
           || !($shop_id = Configuration::get($this->getConfigName('SHOP_ID')))
           || !ctype_digit($shop_id)
        ) {
            return '';
        }

        $html = $this->getRichSnippet($shop_id);

        if ($html) {
            return $html;
        }
    }

    private function getRichSnippet($shop_id) {
        $tmp_dir = @sys_get_temp_dir();
        if (!@is_writable($tmp_dir)) {
            $tmp_dir = '/tmp';
        }
        if (!@is_writable($tmp_dir)) {
            return;
        }

        $url = sprintf(
            'https://' . $this->getDashboardDomain() . '/webshops/rich_snippet?id=%s',
            (int) $shop_id
        );

        $cache_file = $tmp_dir . DIRECTORY_SEPARATOR . strtoupper($this->getName()) . '_'
            . md5(__FILE__) . '_' . md5($url);

        $fp = @fopen($cache_file, 'rb');
        if ($fp) {
            $stat = @fstat($fp);
        }

        if ($fp && $stat && $stat['mtime'] > time() - 7200
           && ($json = @stream_get_contents($fp))
        ) {
            $data = json_decode($json, true);
        } else {
            $context = @stream_context_create([
                'http' => ['timeout' => 3],
            ]);
            $json = @file_get_contents($url, false, $context);
            if (!$json) {
                return;
            }

            $data = @json_decode($json, true);
            if (empty($data['result'])) {
                return;
            }

            $new_file = $cache_file . '.' . uniqid();
            if (@file_put_contents($new_file, $json)) {
                @rename($new_file, $cache_file) or @unlink($new_file);
            }
        }

        if ($fp) {
            @fclose($fp);
        }

        if ($data['result'] == 'ok') {
            return $data['content'];
        }
    }

    private function getOrdersToInvite($db, $ps_shop_id, $first_order_id) {
        if ($first_order_id < 1) {
            return [];
        }

        $max_time = time() - 1800;

        return $db->executeS("
            SELECT
                o.*,
                c.email,
                a.firstname,
                a.lastname,
                l.language_code
            FROM `{$this->getTableName('orders')}` o
            INNER JOIN `{$this->getTableName('order_state')}` os ON
                os.id_order_state = o.current_state
            INNER JOIN `{$this->getTableName('customer')}` c USING (id_customer)
            LEFT JOIN `{$this->getTableName('address')}` a ON o.id_address_invoice = a.id_address
            LEFT JOIN `{$this->getTableName('lang')}` l ON o.id_lang = l.id_lang
            WHERE
                COALESCE(o.{$this->getPluginColumnName('invite_sent')}, 0) = 0
                AND o.id_shop = $ps_shop_id
                AND COALESCE(o.{$this->getPluginColumnName('invite_tries')}, 0) < 10
                AND COALESCE(o.{$this->getPluginColumnName('invite_time')}, 0) < $max_time
                AND os.shipped = 1
                AND o.id_order >= " . (int) $first_order_id . '
            ORDER BY RAND()
            LIMIT 10
        ');
    }

    private function getOrderLines($db, $order_id) {
        $query = "SELECT * FROM `{$this->getTableName('order_detail')}` WHERE id_order = $order_id";
        return $db->executeS($query);
    }

    private function getOrderAddress($db, $address_id) {
        $query = "SELECT * FROM `{$this->getTableName('address')}` WHERE id_address = $address_id";
        return $db->executeS($query);
    }

    private function getCustomerInfo($db, $customer_id) {
        $query = "SELECT * FROM `{$this->getTableName('customer')}` WHERE id_customer = $customer_id";
        $customer_info = $db->executeS($query);
        $customer_info = $customer_info[0];
        unset($customer_info['passwd']);
        unset($customer_info['last_passwd_gen']);
        unset($customer_info['secure_key']);
        unset($customer_info['reset_password_token']);
        unset($customer_info['reset_password_validity']);
        return $customer_info;
    }

    private function sendInvites($ps_shop_id) {
        if (!($shop_id = Configuration::get($this->getConfigName('SHOP_ID'), null, null, $ps_shop_id))
           || !($api_key = Configuration::get($this->getConfigName('API_KEY'), null, null, $ps_shop_id))
           || !($invite = Configuration::get($this->getConfigName('INVITE'), null, null, $ps_shop_id))
        ) {
            return;
        }

        $invite_delay = (int) Configuration::get($this->getConfigName('INVITE_DELAY'), null, null, $ps_shop_id);
        $first_order_id = (int) Configuration::get($this->getConfigName('INVITE_FIRST_ORDER_ID'), null, null, $ps_shop_id);
        $with_order_data = !Configuration::get($this->getConfigName('LIMIT_ORDER_DATA'), null, null, $ps_shop_id);

        $db = Db::getInstance();

        $orders = $this->getOrdersToInvite($db, $ps_shop_id, $first_order_id);

        if (!$orders) {
            return;
        }

        foreach ($orders as $order) {
            $invoice_address = $this->getOrderAddress($db, $order['id_address_invoice'])[0];
            $delivery_address = $this->getOrderAddress($db, $order['id_address_delivery'])[0];
            $phones = array_unique(array_filter([
                $invoice_address['phone'],
                $invoice_address['phone_mobile'],
                $delivery_address['phone'],
                $delivery_address['phone_mobile'],
            ]));

            $post = [
                'email'     => $order['email'],
                'order'     => $order['id_order'],
                'delay'     => $invite_delay,
                'language'      => str_replace('-', '_', $order['language_code']),
                'customer_name' => $order['firstname'] . ' ' . $order['lastname'],
                'phone_numbers' => $phones,
                'order_total' => $order['total_paid'],
                'client'    => 'prestashop',
                'platform_version' => _PS_VERSION_,
                'plugin_version' => $this->version,

            ];
            if ($invite == 2) {
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
                    if (empty($images)) {
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
                    'invoice_address' => $invoice_address,
                ]);
            }

            $db->execute("
                UPDATE `{$this->getTableName('orders')}`
                SET
                    {$this->getPluginColumnName('invite_tries')} = {$this->getPluginColumnName('invite_tries')} + 1,
                    {$this->getPluginColumnName('invite_time')} = " . time() . "
                WHERE
                    id_order = {$order['id_order']}
                    AND COALESCE({$this->getPluginColumnName('invite_tries')}, 0) = " . (int) $order[$this->getPluginColumnName('invite_tries')] . "
                    AND COALESCE({$this->getPluginColumnName('invite_time')}, 0) = " . (int) $order[$this->getPluginColumnName('invite_time')] . '
            ');

            if ($db->Affected_Rows()) {
                $url = 'https://' . $this->getDashboardDomain() . '/api/1.0/invitations.json?id=' . $shop_id . '&code=' . $api_key;
                $retriever = new Peschar_URLRetriever();
                $response = $retriever->retrieve($url, $post);

                if ($response === false) {
                    $success = false;
                } else {
                    $data = json_decode($response, JSON_OBJECT_AS_ARRAY);
                    $success = is_array($data) && isset($data['status']) && $data['status'] == 'success';
                }
                if ($success) {
                    $db->execute("UPDATE `{$this->getTableName('orders')}` SET {$this->getPluginColumnName('invite_sent')} = 1 WHERE id_order = " . (int) $order['id_order']);
                } else {
                    $db->execute("INSERT INTO `{$this->getPluginTableName('invite_error')}` SET url = '" . pSQL($url, true) . "', response = '" . pSQL($response, true) . "', time = " . time());
                }
            }
        }
    }

    public function hookBackofficeTop() {
        foreach (Shop::getCompleteListOfShopsID() as $shop) {
            $this->sendInvites($shop);
        }
    }

    public function getContent() {
        $db = Db::getInstance();
        $errors = [];
        $success = false;

        if (tools::isSubmit($this->getName())) {
            $shop_id = tools::getValue('shop_id');
            if (strlen($shop_id)) {
                $shop_id = (int) $shop_id;
            } else {
                $shop_id = '';
            }

            $api_key = tools::getValue('api_key');

            if (!$shop_id || !$api_key) {
                $errors[] = $this->l('Om de sidebar weer te geven of uitnodigingen te versturen, zijn uw webwinkel en API key vereist.');
            }

            Configuration::updateValue($this->getConfigName('SHOP_ID'), trim($shop_id));
            Configuration::updateValue($this->getConfigName('API_KEY'), trim($api_key));

            Configuration::updateValue(
                $this->getConfigName('INVITE'),
                (int) tools::getValue('invite')
            );

            $invite_delay = tools::getValue('invite_delay');
            if (strlen($invite_delay) == 0) {
                $invite_delay = 3;
            }
            Configuration::updateValue($this->getConfigName('INVITE_DELAY'), (int) $invite_delay);

            Configuration::updateValue(
                $this->getConfigName('INVITE_FIRST_ORDER_ID'),
                (int) tools::getValue('invite_first_order_id')
            );
            $this->fixUnsentOrders(Configuration::get($this->getConfigName('INVITE_FIRST_ORDER_ID')));

            Configuration::updateValue(
                $this->getConfigName('JAVASCRIPT'),
                !!tools::getValue('javascript')
            );

            Configuration::updateValue(
                $this->getConfigName('RICH_SNIPPET'),
                !!tools::getValue('rich_snippet')
            );

            Configuration::updateValue(
                $this->getConfigName('LIMIT_ORDER_DATA'),
                !!tools::getValue('limit_order_data')
            );

            $this->registerHook('header');
            $this->registerHook('footer');
            $this->registerHook('backOfficeTop');

            if (sizeof($errors) == 0) {
                $success = true;
            }
        }

        $invite_errors = $db->executeS("
            SELECT *
            FROM `{$this->getPluginTableName('invite_error')}`
            WHERE
                time > " . (time() - 86400 * 3) . '
            ORDER BY time
        ');

        ob_start();
        foreach ($errors as $error) {
            echo $this->displayError($error);
        }
        if ($success) {
            echo $this->displayConfirmation($this->l('Uw wijzigingen zijn opgeslagen.'));
        }
        require dirname(__FILE__) . '/config_form.php';
        return ob_get_clean();
    }

    private function fixUnsentOrders($first_order_id) {
        Db::getInstance()->execute("
            UPDATE `{$this->getTableName('orders')}`
            SET
                {$this->getPluginColumnName('invite_sent')} = 0
            WHERE
                {$this->getPluginColumnName('invite_sent')} = 1
                AND ({$this->getPluginColumnName('invite_tries')} IS NULL OR {$this->getPluginColumnName('invite_tries')} = 0)
                AND id_order >= " . (int) $first_order_id . '
                AND ' . (Shop::isFeatureActive() ? 'id_shop = ' . (int) Shop::getContextShopID() : '1') . '
        ');
    }

    private function getLastOrderId() {
        $result = Db::getInstance()->executeS("
            SELECT MAX(id_order) id_order FROM `{$this->getTableName('orders')}`
        ");
        return $result ? $result[0]['id_order'] : 0;
    }

    private function escape($string) {
        return htmlentities($string, ENT_QUOTES, 'UTF-8');
    }
}