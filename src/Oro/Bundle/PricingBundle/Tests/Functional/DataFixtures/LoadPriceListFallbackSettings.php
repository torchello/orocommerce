<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccounts;
use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadGroups;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListAccountGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadPriceListFallbackSettings extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected $fallbackSettings = [
        'account' => [
            LoadWebsiteData::WEBSITE1 => [
                'account.level_1_1' => PriceListAccountFallback::ACCOUNT_GROUP,
                'account.level_1.3' => PriceListAccountFallback::ACCOUNT_GROUP,
                'account.level_1.2' => PriceListAccountFallback::CURRENT_ACCOUNT_ONLY,
            ],
            LoadWebsiteData::WEBSITE2 => [
                'account.level_1_1' => PriceListAccountFallback::CURRENT_ACCOUNT_ONLY,
                'account.level_1.3' => PriceListAccountFallback::ACCOUNT_GROUP,
                'account.level_1.2' => PriceListAccountFallback::CURRENT_ACCOUNT_ONLY,
            ],
        ],
        'accountGroup' => [
            LoadWebsiteData::WEBSITE1 => [
                'account_group.group1' => PriceListAccountGroupFallback::WEBSITE,
                'account_group.group2' => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
            ],
            LoadWebsiteData::WEBSITE2 => [
                'account_group.group1' => PriceListAccountGroupFallback::WEBSITE,
                'account_group.group2' => PriceListAccountGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY,
            ],
        ],
        'website' => [
            'US' => PriceListWebsiteFallback::CONFIG,
            'Canada' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsiteData::class,
            LoadAccounts::class,
            LoadGroups::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->fallbackSettings['account'] as $websiteReference => $fallbackSettings) {
            /** @var Website $website */
            $website = $this->getReference($websiteReference);
            foreach ($fallbackSettings as $accountReference => $fallbackValue) {
                /** @var Account $account */
                $account = $this->getReference($accountReference);

                $priceListAccountFallback = new PriceListAccountFallback();
                $priceListAccountFallback->setAccount($account);
                $priceListAccountFallback->setWebsite($website);
                $priceListAccountFallback->setFallback($fallbackValue);

                $manager->persist($priceListAccountFallback);
            }
        }

        foreach ($this->fallbackSettings['accountGroup'] as $websiteReference => $fallbackSettings) {
            /** @var Website $website */
            $website = $this->getReference($websiteReference);
            foreach ($fallbackSettings as $accountGroupReference => $fallbackValue) {
                /** @var AccountGroup $accountGroup */
                $accountGroup = $this->getReference($accountGroupReference);

                $priceListAccountGroupFallback = new PriceListAccountGroupFallback();
                $priceListAccountGroupFallback->setAccountGroup($accountGroup);
                $priceListAccountGroupFallback->setWebsite($website);
                $priceListAccountGroupFallback->setFallback($fallbackValue);

                $manager->persist($priceListAccountGroupFallback);
            }
        }

        foreach ($this->fallbackSettings['website'] as $websiteReference => $fallbackValue) {
            /** @var Website $website */
            $website = $this->getReference($websiteReference);

            $priceListWebsiteFallback = new PriceListWebsiteFallback();
            $priceListWebsiteFallback->setWebsite($website);
            $priceListWebsiteFallback->setFallback($fallbackValue);

            $manager->persist($priceListWebsiteFallback);
        }
        $manager->flush();
    }
}
