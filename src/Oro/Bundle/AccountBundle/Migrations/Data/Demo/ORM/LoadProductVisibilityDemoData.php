<?php

namespace Oro\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\WebsiteBundle\Migrations\Data\ORM\LoadWebsiteData;

class LoadProductVisibilityDemoData extends AbstractLoadProductVisibilityDemoData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return array_merge(parent::getDependencies(), [LoadWebsiteData::class, LoadCategoryVisibilityDemoData::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->resetVisibilities($manager);

        $locator = $this->container->get('file_locator');
        $filePath = $locator->locate('@OroAccountBundle/Migrations/Data/Demo/ORM/data/products-visibility.csv');
        $handler = fopen($filePath, 'r');
        $headers = fgetcsv($handler, 1000, ',');
        $website = $this->getWebsite($manager, LoadWebsiteData::DEFAULT_WEBSITE_NAME);
        while (($data = fgetcsv($handler, 1000, ',')) !== false) {
            $row = array_combine($headers, array_values($data));
            $product = $this->getProduct($manager, $row['product']);
            $visibility = $row['visibility'];
            $this->setProductVisibility($manager, $row, $website, $product, $visibility);
        }

        fclose($handler);
        $manager->flush();
        $this->container->get('oro_account.visibility.cache.product.cache_builder')->buildCache();
    }
}
