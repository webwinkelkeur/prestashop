<?php

require_once __DIR__ . '/common/autoload.php';

class TrustProfile extends Valued\PrestaShop\Module {
    protected function getModuleKey() {
        return '';
    }

    protected function getDisplayName() {
        return 'TrustProfile';
    }

    protected function getDashboardDomain() {
        return 'dashboard.trustprofile.io';
    }
}
