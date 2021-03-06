<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class ProductEntityListenerTest extends WebTestCase
{
    use MessageQueueTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
            LoadPriceRuleLexemes::class
        ]);
        $this->cleanQueueMessageTraces();
    }

    public function testPreUpdate()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        /** @var PriceList $expectedPriceList */
        $expectedPriceList = $this->getReference('price_list_1');
        /** @var Product $product */
        $product = $this->getReference('product.1');
        $this->assertNotEquals(Product::STATUS_DISABLED, $product->getStatus());
        $product->setStatus(Product::STATUS_DISABLED);
        $em->persist($product);
        $em->flush();

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);

        $trace = $traces[0];
        $this->assertNotEmpty($this->getProductIdFromTrace($trace));
        $this->assertEquals($product->getId(), $this->getProductIdFromTrace($trace));
        $this->assertEquals($expectedPriceList->getId(), $this->getPriceListIdFromTrace($trace));
    }

    public function testPostPersist()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);

        $product = new Product();
        $product->setSku('TEST');

        $em->persist($product);
        $em->flush();

        $traces = $this->getQueueMessageTraces();
        $this->assertCount(1, $traces);

        $trace = $traces[0];
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');
        $this->assertEquals($priceList->getId(), $this->getPriceListIdFromTrace($trace));
    }
}
