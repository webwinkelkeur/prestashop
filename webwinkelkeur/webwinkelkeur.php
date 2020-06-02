<?php

require_once __DIR__ . '/common/autoload.php';

class WebwinkelKeur extends Valued\PrestaShop\Module {
    protected function getModuleKey() {
        return '905d0071afeef0d6aaf724f0a8bb801f';
    }

    protected function getDisplayName() {
        return $this->l('WebwinkelKeur');
    }

    protected function getDescription() {
        return $this->l('Integreer de WebwinkelKeur sidebar in uw webwinkel.');
    }
}
