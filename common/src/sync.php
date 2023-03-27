<?php

namespace Valued\PrestaShop;
use Configuration;
use DateTime;
use Exception;
use ModuleFrontController;
use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\PrestaShop\Adapter\Validate;
use Product;

class sync extends ModuleFrontController {
    /**
     * @throws Exception
     */
    public function postProcess() {
        $request_data = trim(file_get_contents('php://input'));
        if (!$request_data) {
            throw new Exception('Empty request data');
        }
        if (!$request_data = json_decode($request_data, true)) {
            throw new Exception('Invalid JSON data provided');
        }

        if (
            !$this->hasCredentialFields($request_data['shop_id'], $request_data['api_key'])
            || $this->credentialsEmpty($request_data['shop_id'], $request_data['api_key'])
        ) {
            throw new Exception('Missing credential fields');
        }
        $lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        $product = new Product($request_data['id_product'], false, $lang_id);
        if (!Validate::isLoadedObject($product)) {
            throw new Exception(sprintf('Could not find product with ID (%d)', $request_data['id_product']));
        }

        if (!Configuration::get(strtoupper($request_data['module']) . "_SYNC_PROD_REVIEWS")) {
            throw new Exception('Product review sync is disabled.');
        }

        $this->isAuthorized($request_data['shop_id'], $request_data['api_key'], $request_data['module']);
        $this->syncProductReview($request_data);
    }

    /**
     * @throws Exception
     */
    private function syncProductReview(array $request_data): void {
        if (!$request_data['title'] || !$request_data['content'] || !$request_data['customer_name'] || !$request_data['grade']) {
            throw new Exception('Missing required content for product review');
        }
        $entity_manager = $this->container->get('doctrine.orm.entity_manager');
        $product_comment_entity = new ProductComment();
        $date_add = DateTime::createFromFormat('Y-m-d H:i:s', $request_data['date_add']);
        $product_comment_entity->setProductId($request_data['id_product'])
            ->setCustomerId($request_data['id_customer'] ?? 0)
            ->setGuestId($request_data['id_guest'] ?? 0)
            ->setTitle($request_data['title'])
            ->setContent($request_data['content'])
            ->setCustomerName($request_data['customer_name'])
            ->setGrade($request_data['grade'])
            ->setValidate($request_data['validate'] ?? 1)
            ->setDeleted($request_data['deleted'] ?? 0)
            ->setDateAdd($date_add);
        $entity_manager->persist($product_comment_entity);
        $entity_manager->flush();

        die("Successfully synced product reviews");
    }

    /**
     * @throws Exception
     */
    private function isAuthorized(string $shop_id, string $api_key, string $module_name): void {
        $curr_shop_id = Configuration::get(strtoupper($module_name) . '_SHOP_ID');
        $curr_api_key = Configuration::get(strtoupper($module_name) .'_API_KEY');
        if ($shop_id == $curr_shop_id && hash_equals($api_key, $curr_api_key)) {
            return;
        }
        throw new Exception('Wrong credentials');
    }

    private function hasCredentialFields(?string $shop_id, ?string $api_key): bool {
        return isset($shop_id) && isset($api_key);
    }

    private function credentialsEmpty(?string $shop_id, ?string $api_key): bool {
        return !trim($shop_id) || !trim($api_key);
    }
}