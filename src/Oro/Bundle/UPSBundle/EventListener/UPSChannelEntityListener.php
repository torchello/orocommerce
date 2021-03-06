<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class UPSChannelEntityListener
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ShippingMethodRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Channel $channel
     * @param LifecycleEventArgs $args
     */
    public function preRemove(Channel $channel, LifecycleEventArgs $args)
    {
        if ('ups' === $channel->getType()) {
            $entityManager = $args->getEntityManager();
            $shippingMethods = $this->registry->getShippingMethods();
            foreach ($shippingMethods as $shippingMethod) {
                if ($shippingMethod->getLabel() === $channel->getName()) {
                    $identifier = $shippingMethod->getIdentifier();
                    $configuredMethods = $entityManager
                        ->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                        ->findBy(['method' => $identifier,]);

                    foreach ($configuredMethods as $configuredMethod) {
                        $entityManager->getRepository('OroShippingBundle:ShippingRuleMethodConfig')
                            ->deleteByMethod($configuredMethod->getMethod());
                    }
                    $entityManager->getRepository('OroShippingBundle:ShippingRule')
                        ->disableRulesWithoutShippingMethods();
                    
                    break;
                }
            }
        }
    }
}
