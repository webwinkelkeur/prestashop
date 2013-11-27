<?php

if(!defined('_PS_VERSION_'))
    exit;

class Webwinkelkeur extends Module {
    public function __construct() {
        $this->name = 'webwinkelkeur';
        $this->tab = 'Webwinkelkeur';
        $this->version = 1.0;
        $this->author = 'Albert Peschar';
        $this->need_instance = 0;

        parent::__construct();
        
        $this->displayName = $this->l('Webwinkelkeur');
        $this->description = $this->l('Integreer de Webwinkelkeur sidebar in uw webwinkel.');
    }

    public function install() {
        if(parent::install()) {
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
        } else {
            return false;
        }
    }
}
