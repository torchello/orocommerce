<?php

namespace Oro\Bundle\MenuBundle\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\MatcherInterface;

use Oro\Bundle\MenuBundle\Entity\MenuItem;
use Oro\Bundle\MenuBundle\JsTree\MenuItemTreeHandler;

class MenuItemExtension extends \Twig_Extension
{
    const NAME = 'oro_menu_item_extension';

    /**
     * @var MenuItemTreeHandler
     */
    protected $menuItemTreeHandler;

    /**
     * @var MatcherInterface
     */
    protected $matcher;

    /**
     * @param MenuItemTreeHandler $menuItemTreeHandler
     * @param MatcherInterface $matcher
     */
    public function __construct(MenuItemTreeHandler $menuItemTreeHandler, MatcherInterface $matcher)
    {
        $this->menuItemTreeHandler = $menuItemTreeHandler;
        $this->matcher = $matcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @inheritDoc
     */
    public function getFunctions()
    {
        return [
            'oro_menu_item_list' => new \Twig_Function_Method($this, 'getTree'),
            'oro_menu_is_current' => new \Twig_Function_Method($this, 'isCurrent'),
            'oro_menu_is_ancestor' => new \Twig_Function_Method($this, 'isAncestor'),
        ];
    }

    /**
     * @param MenuItem $entity
     * @return array
     */
    public function getTree(MenuItem $entity)
    {
        return $this->menuItemTreeHandler->createTree($entity->getRoot());
    }

    /**
     * @param ItemInterface $item
     * @return bool
     */
    public function isCurrent(ItemInterface $item)
    {
        return $this->matcher->isCurrent($item);
    }

    /**
     * @param ItemInterface $item
     * @return bool
     */
    public function isAncestor(ItemInterface $item)
    {
        return $this->matcher->isAncestor($item);
    }
}
