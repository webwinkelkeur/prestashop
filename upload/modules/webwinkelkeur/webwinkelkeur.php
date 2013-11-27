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
        
        Db::getInstance()->Execute("
            CREATE TABLE IF NOT EXISTS
                `" . _DB_PREFIX_ . "webwinkelkeur_invite_error`
            (
              `id` int NOT NULL AUTO_INCREMENT,
              `url` varchar(255) NOT NULL,
              `response` text NOT NULL,
              `time` bigint NOT NULL,
              PRIMARY KEY (`id`),
              KEY `time` (`time`)
            ) ENGINE=InnoDB
        ");
        Db::getInstance()->Execute("
            DELETE FROM `" . _DB_PREFIX_ . "webwinkelkeur_invite_error`
        ");

        return true;
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
