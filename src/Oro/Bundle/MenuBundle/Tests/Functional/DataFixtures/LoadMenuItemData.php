<?php

namespace Oro\Bundle\MenuBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MenuBundle\Entity\MenuItem;

class LoadMenuItemData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $menuItems = [
        'menu_item.1' => [
            'uri' => '#',
            'titles' => [
                'en_US' => 'menu_item.1.en_US',
                'en_CA' => 'menu_item.1.en_CA',
            ],
        ],
        'menu_item.1_2' => [
            'uri' => '#',
            'parent' => 'menu_item.1',
            'titles' => [
                'en_US' => 'menu_item.1_2.en_US',
                'en_CA' => 'menu_item.1_2.en_CA',
            ],
        ],
        'menu_item.1_3' => [
            'uri' => '#',
            'parent' => 'menu_item.1',
            'titles' => [
                'en_US' => 'menu_item.1_3.en_US',
                'en_CA' => 'menu_item.1_3.en_CA',
            ],
        ],
        'menu_item.4' => [
            'uri' => '#',
            'titles' => [
                'en_US' => 'menu_item.4.en_US',
                'en_CA' => 'menu_item.4.en_CA',
            ],
        ],
        'menu_item.4_5' => [
            'uri' => '#',
            'parent' => 'menu_item.4',
            'titles' => [
                'en_US' => 'menu_item.4_5.en_US',
                'en_CA' => 'menu_item.4_5.en_CA',
            ],
        ],
        'menu_item.4_5_6' => [
            'uri' => '#',
            'parent' => 'menu_item.4_5',
            'titles' => [
                'en_US' => 'menu_item.4_5_6.en_US',
                'en_CA' => 'menu_item.4_5_6.en_CA',
            ],
        ],
        'menu_item.4_5_7' => [
            'uri' => '#',
            'parent' => 'menu_item.4_5',
            'titles' => [
                'en_US' => 'menu_item.4_5_7.en_US',
                'en_CA' => 'menu_item.4_5_7.en_CA',
            ],
        ],
        'menu_item.4_5_6_8' => [
            'uri' => '#',
            'parent' => 'menu_item.4_5_6',
            'titles' => [
                'en_US' => 'menu_item.4_5_6_8.en_US',
                'en_CA' => 'menu_item.4_5_6_8.en_CA',
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$menuItems as $menuItemReference => $data) {
            $defaultTitle = new LocalizedFallbackValue();
            $defaultTitle->setString($menuItemReference);
            $entity = new MenuItem();
            $entity->addTitle($defaultTitle)
                ->setUri($data['uri']);
            if (isset($data['parent'])) {
                $entity->setParent($this->getReference($data['parent']));
            }
            foreach ($data['titles'] as $localization => $title) {
                $fallbackValue = new LocalizedFallbackValue();
                $fallbackValue->setLocalization($this->getReference($localization))
                    ->setString($title);
                $entity->addTitle($fallbackValue);
            }
            $this->setReference($menuItemReference, $entity);
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
