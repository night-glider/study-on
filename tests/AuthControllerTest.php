<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\DataFixtures\AppFixtures;
use App\Tests\Mock\BillingMock;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends AbstractTest
{
    public function urlProviderIsSuccessful(): \Generator
    {
        yield ['/login'];
        yield ['/register'];
    }

    /**
     * @dataProvider urlProviderIsSuccessful
     */
    public function testPageIsSuccessful($url): void
    {
        $client = $this->getClient();
        $client->request('GET', $url);
        $this->assertResponseOk();
    }

    public function testSuccessfulAuth(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/login');
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Далее');
        $login = $submitBtn->form([
            'email' => "admin@gmail.com",
            'password' => "admin",
        ]);
        $client->submit($login);
        $this->assertResponseRedirect();

    }

    public function testSuccessfulRegister(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/register');
        $this->assertResponseOk();

        $submitBtn = $crawler->selectButton('Далее');

        $form = $submitBtn->form([
            'register[email]' => "new_user@gmail.com",
            'register[password][first]' => "new_user",
            'register[password][second]' => "new_user"
        ]);
        $client->submit($form);
        $this->assertResponseRedirect();
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
