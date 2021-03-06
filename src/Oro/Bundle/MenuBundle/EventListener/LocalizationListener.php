<?php

namespace Oro\Bundle\MenuBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class LocalizationListener
{
    /**
     * @var ServiceLink
     */
    protected $menuProviderLink;

    /**
     * @param ServiceLink $menuProviderLink
     */
    public function __construct(ServiceLink $menuProviderLink)
    {
        $this->menuProviderLink = $menuProviderLink;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        /** @var Localization $entity */
        $entity = $args->getEntity();
        if (!$this->isLocalizationEntity($entity)) {
            return;
        }
        $this->getMenuProvider()->rebuildCacheByLocalization($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        /** @var Localization $entity */
        $entity = $args->getEntity();
        if (!$this->isLocalizationEntity($entity)) {
            return;
        }
        $uow = $args->getEntityManager()->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);
        if (array_key_exists('parentLocalization', $changes) === true) {
            $this->getMenuProvider()->rebuildCacheByLocalization($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        /** @var Localization $entity */
        $entity = $args->getEntity();
        if (!$this->isLocalizationEntity($entity)) {
            return;
        }
        $this->getMenuProvider()->clearCacheByLocalization($entity);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isLocalizationEntity($entity)
    {
        return $entity instanceof Localization;
    }

    /**
     * @return DatabaseMenuProvider
     */
    protected function getMenuProvider()
    {
        return $this->menuProviderLink->getService();
    }
}
