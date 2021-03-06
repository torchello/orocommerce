<?php

namespace Oro\Bundle\ShippingBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class FormViewListener
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ShippingOriginProvider */
    protected $shippingOriginProvider;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     * @param ShippingOriginProvider $shippingOriginProvider
     * @param RequestStack $requestStack
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper,
        ShippingOriginProvider $shippingOriginProvider,
        RequestStack $requestStack
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->requestStack = $requestStack;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onWarehouseView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $warehouseId = (int)$request->get('id');
        if (!$warehouseId) {
            return;
        }

        /** @var Warehouse $warehouse */
        $warehouse = $this->doctrineHelper->getEntityReference('OroWarehouseBundle:Warehouse', $warehouseId);
        if (!$warehouse) {
            return;
        }

        $shippingOrigin = $this->shippingOriginProvider->getShippingOriginByWarehouse($warehouse);

        if ($shippingOrigin->isEmpty()) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroShippingBundle:Warehouse:shipping_origin_view.html.twig',
            ['entity' => $shippingOrigin]
        );
        $this->addBlock($event->getScrollData(), $template, 'oro.shipping.warehouse.section.shipping_origin');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onWarehouseEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroShippingBundle:Warehouse:shipping_origin_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addBlock($event->getScrollData(), $template, 'oro.shipping.warehouse.section.shipping_origin');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductView(BeforeListRenderEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $productId = (int)$request->get('id');
        if (!$productId) {
            return;
        }

        /** @var Product $product */
        $product = $this->doctrineHelper->getEntityReference('OroProductBundle:Product', $productId);
        if (!$product) {
            return;
        }

        $shippingOptions = $this->doctrineHelper
            ->getEntityRepositoryForClass('OroShippingBundle:ProductShippingOptions')
            ->findBy(['product' => $productId]);

        if (0 === count($shippingOptions)) {
            return;
        }

        $template = $event->getEnvironment()->render(
            'OroShippingBundle:Product:shipping_options_view.html.twig',
            [
                'entity' => $product,
                'shippingOptions' => $shippingOptions
            ]
        );
        $this->addBlock($event->getScrollData(), $template, 'oro.shipping.product.section.shipping_options');
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onProductEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroShippingBundle:Product:shipping_options_update.html.twig',
            ['form' => $event->getFormView()]
        );
        $this->addBlock($event->getScrollData(), $template, 'oro.shipping.product.section.shipping_options');
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     * @param string $label
     * @param int $priority
     */
    protected function addBlock(ScrollData $scrollData, $html, $label, $priority = 100)
    {
        $blockLabel = $this->translator->trans($label);
        $blockId    = $scrollData->addBlock($blockLabel, $priority);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
