<?php

require_once __DIR__ . '/common/autoload.php';

class WebwinkelKeur extends Valued\PrestaShop\Module {
    protected function getModuleKey() {
        return '905d0071afeef0d6aaf724f0a8bb801f';
    }

    protected function getDisplayName() {
        return 'WebwinkelKeur';
    }

    protected function getDashboardDomain() {
        return 'dashboard.webwinkelkeur.nl';
    }
}
