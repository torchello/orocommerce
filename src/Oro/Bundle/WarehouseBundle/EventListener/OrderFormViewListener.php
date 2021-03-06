<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;

class OrderFormViewListener
{
    /**
     * @var WarehouseCounter
     */
    private $warehouseCounter;

    /**
     * OrderFormViewListener constructor.
     *
     * @param WarehouseCounter $warehouseCounter
     */
    public function __construct(WarehouseCounter $warehouseCounter)
    {
        $this->warehouseCounter = $warehouseCounter;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onOrderEdit(BeforeListRenderEvent $event)
    {
        if ($this->warehouseCounter->areMoreWarehouses()) {
            $template = $event->getEnvironment()->render(
                'OroWarehouseBundle:Order:update.html.twig',
                ['form' => $event->getFormView()]
            );
            $scrollData = $event->getScrollData();
            $scrollData->addSubBlockData(0, 0, $template);
        }
    }
}
