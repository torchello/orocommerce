<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolation
 */
class AjaxOrderControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders'
            ]
        );
    }

    public function testNewOrderTotals()
    {
        $this->markTestIncomplete('Should be fixed in scope of task BB-3686');
        $crawler = $this->client->request('GET', $this->getUrl('oro_order_frontend_create'));
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertTotals($crawler);
    }

    public function testTotals()
    {
        $this->markTestIncomplete('Should be fixed in scope of task BB-3686');
        $order = $this->getReference(LoadOrders::MY_ORDER);

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_order_frontend_update', ['id' => $order->getId()])
        );
        $result = $this->client->getResponse();

        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertTotals($crawler, $order->getId());
    }

    /**
     * @param Crawler $crawler
     * @param null|int $id
     */
    protected function assertTotals(Crawler $crawler, $id = null)
    {
        $form = $crawler->selectButton('Save and Close')->form();

        $form->getFormNode()->setAttribute('action', $this->getUrl('oro_order_frontend_entry_point', ['id' => $id]));

        $this->client->submit($form);

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        $this->assertArrayHasKey('totals', $data);
        $this->assertArrayHasKey('subtotals', $data['totals']);
        $this->assertArrayHasKey(0, $data['totals']['subtotals']);
        $this->assertArrayHasKey('total', $data['totals']);
    }
}
