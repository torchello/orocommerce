<?php

namespace Oro\Bundle\PaymentBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;

class LoadPaymentTermDemoData extends AbstractFixture
{
    /**
     * @var array
     */
    static public $paymentTermsLabels = [
        'net 10',
        'net 30',
        'net 60',
        'net 90',
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$paymentTermsLabels as $paymentTermLabel) {
            $paymentTerm = new PaymentTerm();
            $paymentTerm->setLabel($paymentTermLabel);

            $this->addReference($paymentTermLabel, $paymentTerm);
            $manager->persist($paymentTerm);
        }

        $manager->flush();
    }
}
