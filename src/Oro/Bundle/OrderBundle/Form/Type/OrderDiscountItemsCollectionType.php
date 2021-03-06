<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FormBundle\Form\Type\CollectionType;

class OrderDiscountItemsCollectionType extends AbstractType
{
    const NAME = 'oro_order_discount_items_collection';

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CollectionType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'type' => OrderDiscountItemType::NAME,
                'show_form_when_empty' => false,
                'error_bubbling' => false,
                'cascade_validation' => true,
                'prototype_name' => '__nameorderdiscountitem__',
                'prototype' => true,
                'handle_primary' => false
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
