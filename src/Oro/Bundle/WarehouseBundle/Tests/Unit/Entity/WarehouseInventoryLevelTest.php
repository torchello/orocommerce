<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;
use Oro\Bundle\WarehouseBundle\Entity\WarehouseInventoryLevel;

class WarehouseInventoryLevelTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '123'],
            ['quantity', 10.55],
            ['warehouse', new Warehouse()],
            ['product', new Product()],
            ['productUnitPrecision', new ProductUnitPrecision()]
        ];

        $warehouseInventoryLevel = new WarehouseInventoryLevel();
        $this->assertPropertyAccessors($warehouseInventoryLevel, $properties);
    }

    public function testSetProductUnitPrecision()
    {
        $product = new Product();
        $productUnitPrecision = new ProductUnitPrecision();
        $productUnitPrecision->setProduct($product);

        $warehouseInventoryLevel = new WarehouseInventoryLevel();
        $this->assertEmpty($warehouseInventoryLevel->getProduct());
        $this->assertEmpty($warehouseInventoryLevel->getProductUnitPrecision());

        $warehouseInventoryLevel->setProductUnitPrecision($productUnitPrecision);
        $this->assertEquals($productUnitPrecision, $warehouseInventoryLevel->getProductUnitPrecision());
        $this->assertEquals($product, $warehouseInventoryLevel->getProduct());
    }
}
