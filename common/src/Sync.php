<?php

namespace Valued\PrestaShop;

use Configuration;
use Customer;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use ModuleFrontController;
use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\PrestaShop\Adapter\Validate;
use Product;

class Sync extends ModuleFrontController {

    /** @var bool */
    public $ajax;

    public function postProcess(): void {
        $request_data = trim(file_get_contents('php://input'));
        if (!$request_data) {
            http_response_code(400);
            echo 'Empty request data.';
            exit();
        }
        if (!$request_data = json_decode($request_data, true)) {
            http_response_code(400);
            echo 'Invalid JSON data provided.';
            exit();
        }

        if (
            !$this->hasCredentialFields($request_data['webshop_id'], $request_data['api_key'])
            || $this->credentialsEmpty($request_data['webshop_id'], $request_data['api_key'])
        ) {
            http_response_code(403);
            echo 'Missing credential fields.';
            exit();
        }

        $this->isAuthorized($request_data['webshop_id'], $request_data['api_key']);

        $lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        $product = new Product($request_data['product_review']['product_id'], false, $lang_id);
        if (!Validate::isLoadedObject($product)) {
            http_response_code(404);
            echo sprintf('Could not find product with ID (%d)', $request_data['product_review']['product_id']);
            exit();
        }

        if (!Configuration::get($this->module->getConfigName('SYNC_PROD_REVIEWS'))) {
            http_response_code(403);
            echo 'Product review sync is disabled.';
            exit();
        }

        $this->syncProductReview($request_data['product_review']);
    }

    private function syncProductReview(array $product_review): void {
        $this->ajax = 1;
        /** @var EntityManagerInterface $entityManager */
        $entity_manager = $this->container->get('doctrine.orm.entity_manager');
        $date_add = DateTime::createFromFormat('Y-m-d H:i:s', $product_review['created']);

        $product_comment_repository = $entity_manager->getRepository(ProductComment::class);
        $product_comment = $product_review['id'] ? $product_comment_repository->find($product_review['id']) : new ProductComment();
        $product_comment->setProductId($product_review['product_id'])
            ->setCustomerId($this->getCustomerIdByEmail($product_review['reviewer']['email']) ?? 0)
            ->setGuestId(0)
            ->setTitle($product_review['title'])
            ->setContent($product_review['review'])
            ->setCustomerName($product_review['reviewer']['name'])
            ->setGrade($product_review['rating'])
            ->setValidate(1)
            ->setDateAdd($date_add);

            if (!$product_review['id']) {
                $entity_manager->persist($product_comment);
            }
            $entity_manager->flush();
            $this->logReviewSync($product_review['id'] ?? $product_comment->getId(), $product_review['deleted']);
            $this->ajaxRender(json_encode(['review_id' => $product_review['id']] ?? $product_comment->getId(), JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    private function getCustomerIdByEmail(string $email): ?int {
        $customer = new Customer();
        $customer->getByEmail($email);

        return $customer->id;
    }

    private function logReviewSync(string $review_id, bool $deleted = false): void {
        \PrestaShopLogger::addLog(sprintf('%s product review with ID (%d)', $deleted ? 'Deleted' : 'Saved', $review_id));
    }

    private function isAuthorized(string $webshop_id, string $api_key): void {
        $curr_webshop_id = Configuration::get($this->module->getConfigName('SHOP_ID'));
        $curr_api_key = Configuration::get($this->module->getConfigName('API_KEY'));
        if ($webshop_id == $curr_webshop_id && hash_equals($api_key, $curr_api_key)) {
            return;
        }
        http_response_code(401);
        echo 'Wrong credentials.';
        exit();
    }

    private function hasCredentialFields(?string $webshop_id, ?string $api_key): bool {
        return isset($webshop_id) && isset($api_key);
    }

    private function credentialsEmpty(?string $webshop_id, ?string $api_key): bool {
        return !trim($webshop_id) || !trim($api_key);
    }
}