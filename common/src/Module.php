<?php
namespace Valued\PrestaShop;

use Configuration;
use Context;
use Db;
use Link;
use Module as PSModule;
use PrestaShopLogger;
use Product;
use RuntimeException;
use Shop;
use Tools;

abstract class Module extends PSModule {
    /** @return string */
    abstract protected function getModuleKey();

    /** @return string */
    abstract protected function getDisplayName();

    /** @return string */
    abstract protected function getDashboardDomain();
    const SYNC_URL = 'https://%s/webshops/sync_url';

    public function __construct() {
        $this->name = $this->getName();
        $this->tab = 'pricing_promotion';
        $this->version = '$VERSION$'; // @phan-suppress-current-line PhanTypeMismatchProperty
        $this->author = $this->getDisplayName();
        $this->need_instance = 0;
        $this->module_key = $this->getModuleKey();

        parent::__construct();

        $this->displayName = $this->getDisplayName();
        $this->description = $this->getDescription();
    }

    protected function getName() {
        return strtolower(static::class);
    }

    private function getDescription() {
        return sprintf($this->l('Integrate the %s sidebar in your store, and send review invitations.', 'module'), $this->getDisplayName());
    }

    public function install() {
        if (!parent::install()) {
            return false;
        }

        $this->execSQL("
            ALTER TABLE `{$this->getTableName('orders')}`
                ADD COLUMN `{$this->getPluginColumnName('invite_sent')}` tinyint(1) NOT NULL DEFAULT 0,
                ADD COLUMN `{$this->getPluginColumnName('invite_tries')}` int NOT NULL DEFAULT 0,
                ADD COLUMN `{$this->getPluginColumnName('invite_time')}` int NOT NULL DEFAULT 0,
                ADD KEY `{$this->getPluginColumnName('invite_sent')}` (
                    `{$this->getPluginColumnName('invite_sent')}`,
                    `{$this->getPluginColumnName('invite_tries')}`
                )
        ");

        $this->execSQL("
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

        $this->execSQL("
            DELETE FROM `{$this->getPluginTableName('invite_error')}`
        ");

        Configuration::updateGlobalValue($this->getConfigName('INVITE'), '');
        Configuration::updateGlobalValue($this->getConfigName('JAVASCRIPT'), '1');

        $this->sendSyncUrl();

        return true;
    }

    private function sendSyncUrl(): void {
        if (!Configuration::get($this->getConfigName('SYNC_PROD_REVIEWS'))) {
            return;
        }
        $url = sprintf(self::SYNC_URL, $this->getDashboardDomain());
        $data = [
            'webshop_id' => Configuration::get($this->getConfigName('SHOP_ID')),
            'api_key' => Configuration::get($this->getConfigName('API_KEY')),
            'url' => Context::getContext()->link->getModuleLink($this->getName(), 'sync'),
        ];
        try {
            $this->doSendSyncUrl($url, $data);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(sprintf('Sending sync URL to Dashboard failed with error %s', $e->getMessage()));
        }
    }

    /**
     * @throws Exception
     */
    private function doSendSyncUrl(string $url, array $data): void {
        $curl = curl_init();
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type:application/json'],
            CURLOPT_URL => $url,
            CURLOPT_TIMEOUT => 10,
        ];
        if (!curl_setopt_array($curl, $options)) {
            throw new Exception('Could not set cURL options');
        }

        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception(sprintf('(%s) %s', curl_errno($curl), curl_error($curl)));
        }

        curl_close($curl);
    }

    private function execSQL($query) {
        $db = Db::getInstance();
        if ($db->execute($query) === false) {
            throw new RuntimeException(sprintf(
                'Database error: (%s) %s',
                $db->getNumberError(),
                $db->getMsgError()
            ));
        }
    }

    public function uninstall() {
        foreach (['invite_sent', 'invite_tries', 'invite_time'] as $column) {
            Db::getInstance()->execute("
                ALTER TABLE `{$this->getTableName('orders')}`
                DROP COLUMN `{$this->getPluginColumnName($column)}`
            ");
        }

        Db::getInstance()->execute("
            DROP TABLE IF EXISTS `{$this->getPluginTableName('invite_error')}`
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

        return $this->render('sidebar', [
            'dashboard_domain' => $this->getDashboardDomain(),
            'shop_id' => (int) $shop_id,
        ]);
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

        if (($fp = @fopen($cache_file, 'rb'))
            && ($stat = @fstat($fp))
            && $stat['mtime'] > time() - 7200
            && ($json = @stream_get_contents($fp))
        ) {
            $data = json_decode($json, true);
        } else {
            try {
                $json = $this->request($url, [
                    CURLOPT_CONNECTTIMEOUT => 2,
                    CURLOPT_TIMEOUT => 4,
                ]);

                $data = @json_decode($json, true);

                if (empty($data['result'])) {
                    throw new RuntimeException('No JSON or no result element');
                }
            } catch (RuntimeException $e) {
                return $this->consoleWarn(
                    'Error while retrieving rich snippet: %s: %s',
                    get_class($e),
                    $e->getMessage()
                );
            }

            $new_file = $cache_file . '.' . uniqid();
            if (@file_put_contents($new_file, $json) === strlen($json)) {
                @rename($new_file, $cache_file);
            }
            @unlink($new_file);
        }

        if ($fp) {
            @fclose($fp);
        }

        if ($data['result'] == 'ok') {
            return $data['content'];
        }

        if (isset($data['error'])) {
            return $this->consoleWarn('Rich snippet error: %s', $data['error']);
        }
    }

    private function consoleWarn($message, ...$args) {
        if ($args) {
            $message = sprintf($message, ...$args);
        }
        $message = "[{$this->getDisplayName()}] $message";
        return sprintf('<script>console.warn(%s)</script>', json_encode($message));
    }

    private function getOrdersToInvite($db, $ps_shop_id, $first_order_id) {
        if ($first_order_id < 1) {
            return [];
        }

        $max_time = time() - 1800;

        $result = $db->executeS("
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
                o.id_shop = $ps_shop_id
                AND os.shipped = 1
                AND o.id_order >= " . (int) $first_order_id . "
                AND (
                    o.{$this->getPluginColumnName('invite_sent')} = 0
                    OR o.{$this->getPluginColumnName('invite_sent')} IS NULL
                )
                AND (
                    o.{$this->getPluginColumnName('invite_tries')} < 10
                    OR o.{$this->getPluginColumnName('invite_tries')} IS NULL
                )
                AND COALESCE(o.{$this->getPluginColumnName('invite_time')}, 0) < $max_time
            LIMIT 10
        ");

        if ($result === false) {
            PrestaShopLogger::addLog(sprintf(
                '%s: Database error: (%s) %s',
                $this->getName(),
                $db->getNumberError(),
                $db->getMsgError()
            ), 3);
        }

        shuffle($result);

        return $result;
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
                $post['order_data'] = json_encode([
                    'order' => $order,
                    'products' => $this->getProducts($order_lines),
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
                try {
                    $url =
                        'https://' . $this->getDashboardDomain() . '/api/1.0/invitations.json?' .
                        http_build_query([
                            'id' => $shop_id,
                            'code' => $api_key,
                        ]);

                    $response = $this->request($url, [
                        CURLOPT_POSTFIELDS => $post,
                    ]);

                    $data = json_decode($response, true);

                    if (!isset($data['status'])) {
                        throw new RuntimeException("Invalid response from server: {$response}");
                    }

                    if ($data['status'] != 'success') {
                        throw new RuntimeException($data['message']);
                    }

                    $db->execute("UPDATE `{$this->getTableName('orders')}` SET {$this->getPluginColumnName('invite_sent')} = 1 WHERE id_order = " . (int) $order['id_order']);

                    PrestaShopLogger::addLog(sprintf(
                        '%s: Requested invitation for order %s',
                        $this->getName(),
                        $order['id_order']
                    ), 1, null, 'Order', $order['id_order']);
                } catch (RuntimeException $e) {
                    PrestaShopLogger::addLog(sprintf(
                        '%s: Could not request invitation for order %s: %s',
                        $this->getName(),
                        $order['id_order'],
                        $e->getMessage()
                    ), 3, null, 'Order', $order['id_order']);
                    $db->execute("INSERT INTO `{$this->getPluginTableName('invite_error')}` SET url = '" . pSQL($url, true) . "', response = '" . pSQL($e->getMessage(), true) . "', time = " . time());
                }
            }
        }
    }

    private function getProducts($order_lines): array {
        return array_map(function ($line) {
            $product = new Product($line['product_id'], false, Context::getContext()->language->id);
            $line['name'] = $line['product_name'];
            $line['url'] = (new Link())->getProductLink($product);
            $line['id'] = $line['product_id'];
            $line['sku'] = $line['product_reference'];
            $line['image_url'] = $this->getProductImage($product);
            $line['gtin'] = $line['product_ean13'] ?? $line['product_isbn'];
            $line['mpn'] = $line['product_mpn'] ?: null;
            $line['brand'] = $product->getWsManufacturerName();

            return $line;
        }, $order_lines);
    }

    private function getProductImage($product): string {
        $context = Context::getContext();
        $img = $product->getCover($product->id);

        return str_replace('http://', Tools::getShopProtocol(), $context->link->getImageLink($img['link_rewrite'], $img['id_image'], 'large_default'));
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

        if (Tools::isSubmit($this->getName())) {
            $shop_id = Tools::getValue('shop_id');
            if (strlen($shop_id)) {
                $shop_id = (int) $shop_id;
            } else {
                $shop_id = '';
            }

            $api_key = Tools::getValue('api_key');

            if ((!$shop_id || !$api_key) && (int) Tools::getValue('invite')) {
                $errors[] = $this->l('To send invitations, your API key is required.', 'module');
            }

            Configuration::updateValue($this->getConfigName('SHOP_ID'), trim($shop_id));
            Configuration::updateValue($this->getConfigName('API_KEY'), trim($api_key));
            Configuration::updateValue($this->getConfigName('SYNC_PROD_REVIEWS'), (bool) Tools::getValue('sync_prod_reviews'));
            if (Configuration::get($this->getConfigName('SYNC_PROD_REVIEWS'))) {
                $this->sendSyncUrl();
            }

            Configuration::updateValue(
                $this->getConfigName('INVITE'),
                (int) Tools::getValue('invite')
            );

            $invite_delay = Tools::getValue('invite_delay');
            if (strlen($invite_delay) == 0) {
                $invite_delay = 3;
            }
            Configuration::updateValue($this->getConfigName('INVITE_DELAY'), (int) $invite_delay);

            Configuration::updateValue(
                $this->getConfigName('INVITE_FIRST_ORDER_ID'),
                (int) Tools::getValue('invite_first_order_id')
            );
            $this->fixUnsentOrders(Configuration::get($this->getConfigName('INVITE_FIRST_ORDER_ID')));

            Configuration::updateValue(
                $this->getConfigName('JAVASCRIPT'),
                !!Tools::getValue('javascript')
            );

            Configuration::updateValue(
                $this->getConfigName('RICH_SNIPPET'),
                !!Tools::getValue('rich_snippet')
            );

            Configuration::updateValue(
                $this->getConfigName('LIMIT_ORDER_DATA'),
                !!Tools::getValue('limit_order_data')
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

        $output = '';
        foreach ($errors as $error) {
            $output .= $this->displayError($error);
        }
        if ($success) {
            $output .= $this->displayConfirmation($this->l('Your changes have been saved.', 'module'));
        }
        $output .= $this->render('config_form', [
            'invite_errors' => $invite_errors,
            'module' => $this,
        ]);
        return $output;
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

    private function getTableName($name) {
        return _DB_PREFIX_ . $name;
    }

    public function getConfigName($name) {
        return strtoupper($this->getName()) . '_' . $name;
    }

    private function getConfigValue($name, $default = null) {
        $key = $this->getConfigName($name);
        if (!Configuration::hasKey($key)) {
            return $default;
        }
        return Configuration::get($key);
    }

    private function getPluginColumnName($name) {
        return $this->getName() . '_' . $name;
    }

    private function getPluginTableName($name) {
        return $this->getTableName($this->getName() . '_' . $name);
    }

    /**
     * @param string $url
     * @param array $options
     * @return string
     */
    private function request($url, $options = []) {
        $ch = curl_init($url);
        if (!$ch) {
            throw new RuntimeException('curl_init failed');
        }
        $options += [
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ];
        if (!curl_setopt_array($ch, $options)) {
            throw new RuntimeException('curl_setopt_array failed');
        }
        $response = curl_exec($ch);
        if ($response === false) {
            throw new RuntimeException(sprintf(
                'curl: (%s) %s',
                curl_errno($ch),
                curl_error($ch)
            ));
        }
        return $response;
    }

    private function render($__template, array $__scope) {
        return (static function () use ($__template, $__scope) {
            extract($__scope);
            ob_start();
            require __DIR__ . '/../templates/' . $__template . '.php';
            return ob_get_clean();
        })();
    }
}
