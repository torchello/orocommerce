<?php

namespace Oro\Bundle\WarehouseBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use Oro\Bundle\WarehouseBundle\Entity\Repository\WarehouseRepository;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class NormalizeInventoryLevelRequestData implements ProcessorInterface
{
    const PRODUCT = 'product';
    const WAREHOUSE = 'warehouse';
    const UNIT = 'unit';
    const UNIT_PRECISION = 'productUnitPrecision';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var  WarehouseCounter */
    protected $warehouseCounter;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param WarehouseCounter $warehouseCounter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        WarehouseCounter $warehouseCounter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (!$context instanceof FormContext) {
            return;
        }

        $requestData = $context->getRequestData();
        if (!$requestData) {
            return;
        }

        if (!array_key_exists(JsonApiDoc::DATA, $requestData)
            || !array_key_exists(JsonApiDoc::RELATIONSHIPS, $requestData[JsonApiDoc::DATA])
        ) {
            // the request data are already normalized
            return;
        }
        $relationships = $requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS];

        $productId = $this->resolveProductId($relationships);
        if (!$productId) {
            // Product is required in order to identify a WarehouseInventoryLevel
            return;
        }
        unset($relationships[self::PRODUCT]);

        $productUnitPrecision = $this->resolveProductUnitPrecision($relationships, $productId);
        if (!$productUnitPrecision) {
            // ProductUnitPrecision not found.
            return;
        }
        unset($relationships[self::UNIT]);
        $this->addRelationship(
            $relationships,
            self::UNIT_PRECISION,
            ProductUnitPrecision::class,
            $productUnitPrecision->getId()
        );

        if ($this->warehouseCounter->areMoreWarehouses()) {
            if (!$this->isRelationshipValid($relationships, self::WAREHOUSE)) {
                // warehouse is required if there are more warehouses in the system
                return;
            }
        } else {
            $warehouse = $this->resolveWarehouse();
            if ($warehouse) {
                $this->addRelationship($relationships, self::WAREHOUSE, Warehouse::class, $warehouse->getId());
            }
        }

        $requestData[JsonApiDoc::DATA][JsonApiDoc::RELATIONSHIPS] = $relationships;
        $context->setRequestData($requestData);
    }

    /**
     * @param array $relationships
     * @param int $productId
     * @return null|object
     */
    protected function resolveProductUnitPrecision(array $relationships, $productId)
    {
        if (!$this->isRelationshipValid($relationships, self::UNIT)) {
            return $this->doctrineHelper->getEntity(Product::class, $productId)->getPrimaryUnitPrecision();
        }

        $productUnitPrecisionRepository = $this->doctrineHelper->getEntityRepository(ProductUnitPrecision::class);

        return $productUnitPrecisionRepository->findOneBy(
            [
                self::PRODUCT => $productId,
                self::UNIT => $relationships[self::UNIT][JsonApiDoc::DATA][JsonApiDoc::ID],
            ]
        );
    }

    /**
     * @param array $relationships
     * @return null|int
     */
    protected function resolveProductId(array $relationships)
    {
        if (!$this->isRelationshipValid($relationships, self::PRODUCT)) {
            // sku is required on request in order to identify a product
            return null;
        }

        /** @var ProductRepository $productRepository */
        $productRepository = $this->doctrineHelper->getEntityRepository(Product::class);
        $productIds = $productRepository
            ->getProductsIdsBySku([$relationships[self::PRODUCT][JsonApiDoc::DATA][JsonApiDoc::ID]]);

        return reset($productIds);
    }

    /**
     * @return null|Warehouse
     */
    protected function resolveWarehouse()
    {
        /** @var WarehouseRepository $warehouseRepository */
        $warehouseRepository = $this->doctrineHelper->getEntityRepository(Warehouse::class);

        return $warehouseRepository->getSingularWarehouse();
    }

    /**
     * @param array $data
     * @param string $relationship
     * @return bool
     */
    protected function isRelationshipValid(array $data, $relationship)
    {
        return array_key_exists($relationship, $data)
            && array_key_exists(JsonApiDoc::DATA, $data[$relationship])
            && array_key_exists(JsonApiDoc::ID, $data[$relationship][JsonApiDoc::DATA])
            && array_key_exists(JsonApiDoc::TYPE, $data[$relationship][JsonApiDoc::DATA]);
    }

    /**
     * @param array $data
     * @param string $relationship
     * @param string $type
     * @param int $id
     */
    protected function addRelationship(array &$data, $relationship, $type, $id)
    {
        $data[$relationship] = [
            JsonApiDoc::DATA => [
                JsonApiDoc::TYPE => $type,
                JsonApiDoc::ID => $id,
            ]
        ];
    }
}
