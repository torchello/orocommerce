<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Controller\Frontend;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData as LoadLoginAccountUserData;
use Oro\Bundle\AccountBundle\Entity\AccountUser;

/**
 * @dbIsolation
 */
class AjaxAccountUserControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadLoginAccountUserData::AUTH_USER, LoadLoginAccountUserData::AUTH_PW)
        );
        $this->client->useHashNavigation(true);
        $this->loadFixtures(
            [
                'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadAccountUserRoleData'
            ]
        );
    }

    public function testGetAccountIdAction()
    {
        /** @var AccountUser $user */
        $user = $this->getUserRepository()->findOneBy(['email' => 'account.user2@test.com']);
        $this->assertNotNull($user);
        $id = $user->getId();
        $this->client->request(
            'GET',
            $this->getUrl('oro_account_frontend_account_user_get_account', ['id' => $id])
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);
        $data = json_decode($result->getContent(), true);
        $this->assertArrayHasKey('accountId', $data);
        $accountId = $user->getAccount() ? $user->getAccount()->getId() : null;
        $this->assertEquals($data['accountId'], $accountId);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @return ObjectRepository
     */
    protected function getUserRepository()
    {
        return $this->getObjectManager()->getRepository('OroAccountBundle:AccountUser');
    }
}
