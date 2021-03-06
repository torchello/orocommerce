<?php

namespace Oro\Bundle\WarehouseBundle\SystemConfig;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseConfigConverter
{
    const PRIORITY_KEY = 'priority';
    const WAREHOUSE_KEY = 'warehouse';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityRepository|null */
    protected $warehouseRepository;

    /** @var string */
    protected $warehouseClass;

    public function __construct(DoctrineHelper $doctrineHelper, $warehouseClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->warehouseClass = $warehouseClass;
    }

    /**
     * @param array $configs
     * @return array
     */
    public function convertBeforeSave(array $configs)
    {
        return array_map(
            function (WarehouseConfig $config) {
                return [
                    self::WAREHOUSE_KEY => $config->getWarehouse()->getId(),
                    self::PRIORITY_KEY => $config->getPriority(),
                ];
            },
            $configs
        );
    }

    /**
     * @param array $configs
     * @return array
     */
    public function convertFromSaved(array $configs)
    {
        $ids = array_map(
            function ($config) {
                return $config[self::WAREHOUSE_KEY];
            },
            $configs
        );
        $result = [];

        if (0 !== count($ids)) {
            $warehouses = $this->getRepositoryForWarehouse()->findBy(['id' => $ids]) ?: [];

            foreach ($configs as $config) {
                $result[] = $this->createWarehouseConfig($config, $warehouses);
            }

            usort(
                $result,
                function (WarehouseConfig $firstWarehouse, WarehouseConfig $secondWarehouse) {
                    return ($firstWarehouse->getPriority() > $secondWarehouse->getPriority()) ? 1 : -1;
                }
            );
        }

        return $result;
    }

    /**
     * @param array $config
     * @param array|Warehouse[] $warehouses
     * @return WarehouseConfig
     */
    protected function createWarehouseConfig(array $config, array $warehouses)
    {
        foreach ($warehouses as $warehouse) {
            if ($config[self::WAREHOUSE_KEY] === $warehouse->getId()) {
                return new WarehouseConfig($warehouse, $config[self::PRIORITY_KEY]);
            }
        }

        $message = 'Warehouse record with id %s not found, while reading enabled warehouses system configuration.';
        throw new \InvalidArgumentException(sprintf($message, $config[self::WAREHOUSE_KEY]));
    }

    /**
     * @return EntityRepository
     */
    protected function getRepositoryForWarehouse()
    {
        if (!$this->warehouseRepository) {
            $this->warehouseRepository = $this->doctrineHelper->getEntityRepository($this->warehouseClass);
        }

        return $this->warehouseRepository;
    }
}
