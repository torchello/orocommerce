<?php

namespace Oro\Bundle\UPSBundle\Method;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Oro\Bundle\UPSBundle\Factory\PriceRequestFactory;
use Oro\Bundle\UPSBundle\Provider\ChannelType;
use Oro\Bundle\UPSBundle\Provider\UPSTransport;

use Symfony\Bridge\Doctrine\ManagerRegistry;

class UPSShippingMethodProvider implements ShippingMethodProviderInterface
{
    /**
     * @var UPSTransport
     */
    protected $transportProvider;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var ShippingMethodInterface[]
     */
    protected $methods;

    /**
     * @var PriceRequestFactory
     */
    protected $priceRequestFactory;

    /**
     * @param UPSTransport $transportProvider
     * @param ManagerRegistry $doctrine
     * @param PriceRequestFactory $priceRequestFactory
     */
    public function __construct(
        UPSTransport $transportProvider,
        ManagerRegistry $doctrine,
        PriceRequestFactory $priceRequestFactory
    ) {
        $this->transportProvider = $transportProvider;
        $this->doctrine = $doctrine;
        $this->priceRequestFactory = $priceRequestFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethods()
    {
        if (!$this->methods) {
            $channels = $this->doctrine->getManagerForClass('OroIntegrationBundle:Channel')
                ->getRepository('OroIntegrationBundle:Channel')->findBy([
                    'type' => ChannelType::TYPE,
                ]);
            $this->methods = [];
            /** @var Channel $channel */
            foreach ($channels as $channel) {
                if ($channel->isEnabled()) {
                    $method = new UPSShippingMethod($this->transportProvider, $channel, $this->priceRequestFactory);
                    $this->methods[$method->getIdentifier()] = $method;
                }
            }
        }

        return $this->methods;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod($name)
    {
        if ($this->hasShippingMethod($name)) {
            return $this->getShippingMethods()[$name];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasShippingMethod($name)
    {
        return array_key_exists($name, $this->getShippingMethods());
    }
}
