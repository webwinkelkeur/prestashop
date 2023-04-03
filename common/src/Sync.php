<?php

namespace Valued\PrestaShop;

use Configuration;
use CustomerCore;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use ModuleFrontController;
use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\PrestaShop\Adapter\Validate;
use Product;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Sync extends ModuleFrontController {

    /** @var bool */
    public $ajax;

    /**
     * @throws Exception
     */
    public function postProcess() {
        $request_data = trim(file_get_contents('php://input'));
        if (!$request_data) {
            throw new HttpException(400, 'Empty request data');
        }
        if (!$request_data = json_decode($request_data, true)) {
            throw new HttpException(400, 'Invalid JSON data provided');
        }

        if (
            !$this->hasCredentialFields($request_data['webshop_id'], $request_data['api_key'])
            || $this->credentialsEmpty($request_data['webshop_id'], $request_data['api_key'])
        ) {
            throw new HttpException(403, 'Missing credential fields');
        }

        $this->isAuthorized($request_data['webshop_id'], $request_data['api_key']);

        $lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
        $product = new Product($request_data['product_review']['product_id'], false, $lang_id);
        if (!Validate::isLoadedObject($product)) {
            throw new HttpException(404, sprintf('Could not find product with ID (%d)', $request_data['product_review']['product_id']));
        }

        if (!Configuration::get($this->module->getConfigName('SYNC_PROD_REVIEWS'))) {
            throw new HttpException(403, 'Product review sync is disabled.');
        }

        $this->syncProductReview($request_data['product_review']);
    }

    /**
     * @throws Exception
     */
    private function syncProductReview(array $product_review) {
        $this->ajax = 1;
        /** @var EntityManagerInterface $entityManager */
        $entity_manager = $this->container->get('doctrine.orm.entity_manager');
        $product_comment_entity = new ProductComment();
        $date_add = DateTime::createFromFormat('Y-m-d H:i:s', $product_review['created']);

        if ($product_review['id']) {
            $product_comment_repository = $entity_manager->getRepository(ProductComment::class);
            $product_comment = $product_comment_repository->find($product_review['id']);
            if ($product_review['deleted']) {
                $product_comment->setDeleted(1);
                $this->logReviewSync($product_review['id'], true);
            } else {
                $product_comment->setDeleted(0);
                $this->logReviewSync($product_review['id']);
            }
            $entity_manager->flush();
            $this->ajaxRender(json_encode(['review_id' => $product_review['id']], JSON_PARTIAL_OUTPUT_ON_ERROR));
            die();
        }

        $product_comment_entity->setProductId($product_review['product_id'])
            ->setCustomerId($customer->id ?? 0)
            ->setGuestId(0)
            ->setTitle($product_review['title'])
            ->setContent($product_review['review'])
            ->setCustomerName($product_review['reviewer']['name'])
            ->setGrade($product_review['rating'])
            ->setValidate(1)
            ->setDateAdd($date_add);
        $entity_manager->persist($product_comment_entity);
        $entity_manager->flush();

        $review_id = $product_comment_entity->getId();
        $this->logReviewSync($review_id);
        $this->ajaxRender(json_encode(['review_id' => $review_id], JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    private function logReviewSync(string $review_id, bool $deleted = false) {
        $deleted
            ? \PrestaShopLogger::addLog(sprintf('Deleted product review with ID (%d)', $review_id))
            : \PrestaShopLogger::addLog(sprintf('Saved product review with ID (%d)', $review_id));
    }

    /**
     * @throws Exception
     */
    private function isAuthorized(string $webshop_id, string $api_key): void {
        $curr_webshop_id = Configuration::get($this->module->getConfigName('SHOP_ID'));
        $curr_api_key = Configuration::get($this->module->getConfigName('API_KEY'));
        if ($webshop_id == $curr_webshop_id && hash_equals($api_key, $curr_api_key)) {
            return;
        }
        throw new HttpException(401, 'Wrong credentials');
    }

    private function hasCredentialFields(?string $webshop_id, ?string $api_key): bool {
        return isset($webshop_id) && isset($api_key);
    }

    private function credentialsEmpty(?string $webshop_id, ?string $api_key): bool {
        return !trim($webshop_id) || !trim($api_key);
    }
}