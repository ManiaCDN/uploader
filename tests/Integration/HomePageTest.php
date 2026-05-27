<?php

namespace App\Tests\Integration;

class HomePageTest extends IntegrationTestCase
{
    public function testHomePageDisplayed() {
        $this->client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'No welcome set yet');
    }

    public function testHomePageTextIsDisplayedAfterBeingSet() {
        $this->givenLoggedInAdminuser();

        // get CSRF token for writing the settings
        $this->client->request('GET', '/admin/settings');
        $csrfToken = $this->client->getCrawler()->filter('input[name="token"]')->attr('value');

        // update the settings
        $this->client->request('POST', '/admin/settings', [
            'welcome_message' => 'my welcome message',
            'token' => $csrfToken,
            'submit' => 'Update',
        ]);

        // now display with the set settings
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'my welcome message');
    }
}
