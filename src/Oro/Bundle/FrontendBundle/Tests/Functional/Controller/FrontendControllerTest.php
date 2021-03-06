<?php

namespace Oro\Bundle\FrontendBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class FrontendControllerTest extends WebTestCase
{
    const FRONTEND_THEME_CONFIG_KEY = 'oro_frontend.frontend_theme';
    const DEFAULT_THEME = '';

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->setTheme(self::DEFAULT_THEME);

        $this->loadFixtures([
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData',
        ]);
    }

    protected function tearDown()
    {
        $this->setTheme(self::DEFAULT_THEME);
    }

    public function testIndexPage()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $this->assertNotContains($this->getBackendPrefix(), $crawler->html());
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function testThemeSwitch()
    {
        // Switch to layout theme
        $configManager = $this->getContainer()->get('oro_config.manager');
        $this->assertEmpty($configManager->get(self::FRONTEND_THEME_CONFIG_KEY));
        $layoutTheme = 'default';
        $this->setTheme($layoutTheme);

        $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check that backend theme was not affected
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_user_security_login'),
            [],
            [],
            $this->generateNoHashNavigationHeader()
        );
        $this->assertEquals('Login', $crawler->filter('h2.title')->html());

        // Check that after selecting of layout there is an ability to switch to oro theme
        $this->setTheme(self::DEFAULT_THEME);

        $this->client->request('GET', $this->getUrl('oro_frontend_root'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return string
     */
    protected function getBackendPrefix()
    {
        return $this->getContainer()->getParameter('web_backend_prefix');
    }

    /**
     * @param string $theme
     */
    protected function setTheme($theme)
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set(self::FRONTEND_THEME_CONFIG_KEY, $theme);
        $configManager->flush();
    }
}
