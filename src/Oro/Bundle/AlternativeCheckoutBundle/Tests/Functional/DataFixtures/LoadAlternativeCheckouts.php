<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures\AbstractLoadCheckouts;
use Oro\Bundle\PaymentBundle\Method\PaymentTerm;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData;

class LoadAlternativeCheckouts extends AbstractLoadCheckouts
{
    const CHECKOUT_1 = 'alternative.checkout.1';
    const CHECKOUT_2 = 'alternative.checkout.2';

    /**
     * {@inheritDoc}
     */
    protected function getData()
    {
        return [
            self::CHECKOUT_1 => [
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_1,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
            ],
            self::CHECKOUT_2 => [
                'source' => LoadQuoteProductDemandData::QUOTE_DEMAND_2,
                'checkout' => ['payment_method' => PaymentTerm::TYPE]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorkflowName()
    {
        return 'b2b_flow_alternative_checkout';
    }

    /**
     * {@inheritDoc}
     */
    protected function createCheckout()
    {
        return new Checkout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCheckoutSourceName()
    {
        return 'quoteDemand';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(
            parent::getDependencies(),
            ['Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteProductDemandData']
        );
    }
}
