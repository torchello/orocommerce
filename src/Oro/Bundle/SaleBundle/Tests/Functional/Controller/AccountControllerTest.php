<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolation
 */
class AccountControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            'Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData',
        ]);
    }

    public function testQuoteGridOnAccountViewPage()
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.3');
        /** @var Account $account */
        $account = $quote->getAccount();
        $crawler = $this->client->request('GET', $this->getUrl('oro_account_view', ['id' => $account->getId()]));
        $gridAttr = $crawler->filter('[id^=grid-account-view-quote-grid]')
            ->first()->attr('data-page-component-options');
        $this->assertContains($quote->getOwner()->getFullName(), $gridAttr);
        $this->assertContains($quote->getAccountUser()->getFullName(), $gridAttr);
        $gridJsonElements = json_decode(html_entity_decode($gridAttr), true);
        $this->assertCount(
            count(LoadQuoteData::getQuotesFor('account', $account->getName())),
            $gridJsonElements['data']['data']
        );
    }
}
