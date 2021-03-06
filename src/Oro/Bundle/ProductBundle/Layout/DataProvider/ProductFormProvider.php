<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddCopyPasteType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddImportFromFileType;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\ProductLineItem;

class ProductFormProvider extends AbstractFormProvider
{
    const PRODUCT_QUICK_ADD_ROUTE_NAME              = 'oro_product_frontend_quick_add';
    const PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME   = 'oro_product_frontend_quick_add_copy_paste';
    const PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME       = 'oro_product_frontend_quick_add_import';

    /**
     * @param null $data
     * @param array $options
     *
     * @return FormAccessor
     */
    public function getQuickAddForm($data = null, array $options = [])
    {
        return $this->getFormAccessor(QuickAddType::NAME, self::PRODUCT_QUICK_ADD_ROUTE_NAME, $data, [], $options);
    }

    /**
     * @return FormAccessor
     */
    public function getQuickAddCopyPasteForm()
    {
        return $this->getFormAccessor(QuickAddCopyPasteType::NAME, self::PRODUCT_QUICK_ADD_COPY_PASTE_ROUTE_NAME);
    }

    /**
     * @return FormAccessor
     */
    public function getQuickAddImportForm()
    {
        return $this->getFormAccessor(QuickAddImportFromFileType::NAME, self::PRODUCT_QUICK_ADD_IMPORT_ROUTE_NAME);
    }

    /**
     * @param Product|null $product
     * @param string $instanceName
     *
     * @return FormAccessor
     */
    public function getLineItemForm(Product $product = null, $instanceName = '')
    {
        $lineItem = new ProductLineItem(null);
        if ($product) {
            $lineItem->setProduct($product);
        }

        // in this context parameters used for generating local cache
        $parameters = $product ? ['id' => $product->getId()] : [];
        return $this->getFormAccessor(FrontendLineItemType::NAME, null, $lineItem, $parameters, [], $instanceName);
    }
}
