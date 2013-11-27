<?php

if(!defined('_PS_VERSION_'))
    exit;

class Webwinkelkeur extends Module {
    public function __construct() {
        $this->name = 'webwinkelkeur';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'Albert Peschar';
        $this->need_instance = 0;

        parent::__construct();
        
        $this->displayName = $this->l('Webwinkelkeur');
        $this->description = $this->l('Integreer de Webwinkelkeur sidebar in uw webwinkel.');
    }

    public function install() {
        if(!parent::install())
            return false;

        if(!$this->registerHook('header'))
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
        Configuration::updateValue('WEBWINKELKEUR_INVITE', '');

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
        if(!Configuration::get('WEBWINKELKEUR_SIDEBAR'))
            return "<!-- Webwinkelkeur: sidebar disabled -->\n";

        $shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID');
        if(!$shop_id)
            return "<!-- Webwinkelkeur: shop_id not set -->\n";
        if(!ctype_digit($shop_id))
            return "<!-- Webwinkelkeur: shop_id not a number -->\n";

        ob_start();
        require dirname(__FILE__) . '/sidebar.php';
        return ob_get_clean();
    }

    public function getOrdersToInvite($db) {
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
                AND o.webwinkelkeur_invite_tries < 10
                AND o.webwinkelkeur_invite_time < $max_time
                AND os.shipped = 1
        ");

        return $query;
    }

    public function sendInvites() {
        if(!($shop_id = Configuration::get('WEBWINKELKEUR_SHOP_ID'))
           || !($api_key = Configuration::get('WEBWINKELKEUR_API_KEY'))
           || !($invite = Configuration::get('WEBWINKELKEUR_INVITE'))
           || !($invite_delay = Configuration::get('WEBWINKELKEUR_INVITE_DELAY'))
        )
            return;

        $db = Db::getInstance();

        $orders = $this->getOrdersToInvite($db);

        foreach($orders as $order) {
            $db->query("
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
                $url = 'http://www.webwinkelkeur.nl/api.php?' . http_build_query($parameters);
                $response = @file_get_contents($url);
                if(preg_match('|^Success:|', $response)
                   || preg_match('|invite already sent|', $response)
                ) {
                    $db->execute("UPDATE `" . _DB_PREFIX_ . "orders` SET webwinkelkeur_invite_sent = 1 WHERE id_order = " . (int) $order['id_order']);
                } else {
                    $db->execute("INSERT INTO `" . _DB_PREFIX_ . "webwinkelkeur_invite_error` SET url = '" . $db->escape($url, true) . "', response = '" . $db->escape($response, true) . "', time = " . time());
                }
            }
        }
    }

    public function hookBackofficeTop() {
        $this->sendInvites();
    }

    public function getContent() {
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

            Configuration::updateValue('WEBWINKELKEUR_SHOP_ID', $shop_id);
            Configuration::updateValue('WEBWINKELKEUR_API_KEY', $api_key);

            Configuration::updateValue('WEBWINKELKEUR_SIDEBAR',
                !!tools::getValue('sidebar'));
            Configuration::updateValue('WEBWINKELKEUR_INVITE',
                !!tools::getValue('invite'));

            $invite_delay = tools::getValue('invite_delay');
            if(strlen($invite_delay) == 0) $invite_delay = 3;
            Configuration::updateValue('WEBWINKELKEUR_INVITE_DELAY', (int) $invite_delay);

            if(sizeof($errors) == 0)
                $success = true;
        }

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
