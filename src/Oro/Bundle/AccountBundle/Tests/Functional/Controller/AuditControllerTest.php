<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AuditControllerTest extends WebTestCase
{
    /**
     * @var array
     */
    protected $userData = [
        'enabled'   => 1,
        'password'  => 'password',
        'firstName' => 'first name',
        'lastName'  => 'last name',
        'email'     => 'test@example.com',
        'account'   => 'AccountUser AccountUser',
    ];

    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);
    }

    public function testAuditHistory()
    {
        if (!$this->client->getContainer()->hasParameter('oro_account.entity.account_user.class')) {
            $this->markTestSkipped('OroAccountBundle is not installed');
        }

        $this->createUser();

        /** @var AccountUser $user */
        $user = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:AccountUser')
            ->getRepository('OroAccountBundle:AccountUser')
            ->findOneBy(['email' => $this->userData['email']]);

        $response = $this->client->requestGrid(
            'frontend-audit-history-grid',
            [
                'frontend-audit-history-grid[object_class]' => 'Oro_Bundle_AccountBundle_Entity_AccountUser',
                'frontend-audit-history-grid[object_id]'    => $user->getId()
            ]
        );

        $result = $this->getJsonResponseContent($response, 200);
        $result = reset($result['data']);

        $result['old'] = $this->clearResult($result['old']);
        $result['new'] = $this->clearResult($result['new']);

        foreach ($result['old'] as $auditRecord) {
            $auditValue = explode(':', $auditRecord, 2);
            $this->assertEmpty(trim($auditValue[1]));
        }

        foreach ($result['new'] as $auditRecord) {
            $auditValue = explode(':', $auditRecord, 2);
            $key = trim($auditValue[0]);
            $value = trim($auditValue[1]);

            if (!array_key_exists($key, $this->userData)) {
                continue;
            }

            $this->assertEquals($this->userData[$key], $value);
        }

        $this->assertEquals('AccountUser AccountUser - ' . LoadAccountUserData::AUTH_USER, trim($result['author']));
    }

    protected function createUser()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_account_frontend_account_user_create'));
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $form = $crawler->selectButton('Save')->form();
        $form['oro_account_frontend_account_user[enabled]'] = $this->userData['enabled'];
        $form['oro_account_frontend_account_user[plainPassword][first]'] = $this->userData['password'];
        $form['oro_account_frontend_account_user[plainPassword][second]'] = $this->userData['password'];
        $form['oro_account_frontend_account_user[firstName]'] = $this->userData['firstName'];
        $form['oro_account_frontend_account_user[lastName]'] = $this->userData['lastName'];
        $form['oro_account_frontend_account_user[email]'] = $this->userData['email'];
        $form['oro_account_frontend_account_user[roles][0]']->tick();

        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Customer User has been saved', $crawler->html());
    }

    /**
     * @param string $result
     * @return array
     */
    protected function clearResult($result)
    {
        $result = preg_replace("/\n+ */", "\n", $result);
        $result = strip_tags($result);
        $result = explode("\n", trim($result, "\n"));

        return array_filter($result);
    }
}
