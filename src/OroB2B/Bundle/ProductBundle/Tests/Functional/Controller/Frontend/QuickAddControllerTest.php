<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\DomCrawler\Crawler;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class QuickAddControllerTest extends WebTestCase
{
    const VALIDATION_TOTAL_ROWS = 'Total number of records';

    const VALIDATION_VALID_ROWS = 'Valid items';

    const VALIDATION_ERROR_ROWS = 'Records with errors';

    const VALIDATION_ERRORS = 'Errors';

    const VALIDATION_RESULT_SELECTOR = 'div.validation-info table tbody tr';

    const VALIDATION_ERRORS_SELECTOR = 'div.import-errors ol li';

    const VALIDATION_ERROR_NOT_FOUND = 'Item number %s does not found.';

    const VALIDATION_ERROR_MALFORMED = 'Row #%d has invalid format.';

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData'
        ]);
    }

    public function testCopyPasteAction()
    {
        $example = [
            LoadProductData::PRODUCT_1 . ", 1",
            LoadProductData::PRODUCT_2 . ",     2",
            LoadProductData::PRODUCT_3 . "\t3",
            "not-existing-product\t  4",
            "malformed-line"
        ];

        $expectedValidationResult = [
            self::VALIDATION_TOTAL_ROWS => 5,
            self::VALIDATION_VALID_ROWS => 3,
            self::VALIDATION_ERROR_ROWS => 2,
            self::VALIDATION_ERRORS => [
                sprintf(self::VALIDATION_ERROR_NOT_FOUND, 'not-existing-product'),
                sprintf(self::VALIDATION_ERROR_MALFORMED, 5)
            ]
        ];

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_frontend_quick_add'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertContains(htmlentities('Copy & Paste'), $crawler->html());

        $form = $form = $crawler->selectButton('Continue')->form();
        $form['orob2b_product_quick_add_copy_paste[collection]'] = implode(PHP_EOL, $example);

        $crawler = $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals($expectedValidationResult, $this->parseValidationResult($crawler));
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    private function parseValidationResult(Crawler $crawler)
    {
        $result = [];

        $crawler->filter(self::VALIDATION_RESULT_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $result[trim($node->children()->eq(0)->text())] = (int) $node->children()->eq(1)->text();
            }
        );

        $crawler->filter(self::VALIDATION_ERRORS_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $result[self::VALIDATION_ERRORS][] = trim($node->text());
            }
        );

        return $result;
    }
}
