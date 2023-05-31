<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\DTO\UserDTO;
use App\Security\User;
use App\Tests\AbstractTest;
use App\Service\BillingClient;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class BillingMock extends BillingClient
{
    private static array $user = [
        'username' => 'user@gmail.com',
        'password' => 'user',
        'roles' => ['ROLE_USER'],
        'balance' => 0.0,
    ];

    private static array $admin = [
        'username' => 'admin@gmail.com',
        'password' => 'admin',
        'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
        'balance' => 0.0,
    ];

    private static string $newToken;

    public function __construct()
    {
        self::$user["token"] = base64_encode(self::$user["username"] . time());
        self::$admin["token"] = base64_encode(self::$admin["username"] . time());
        self::$newToken = base64_encode("new_token" . time());
    }

    public function auth($credentials)
    {
        $users_to_check = [self::$user, self::$admin];

        foreach ($users_to_check as $user) {
            if (
                $credentials["username"] == $user["username"]
                && $credentials["password"] == $user["password"]
            ) {
                return $user['token'];
            }
        }
        throw new CustomUserMessageAuthenticationException('Неправильные логин или пароль');
    }

    public function register($credentials)
    {
        $users_to_check = [self::$user, self::$admin];
        foreach ($users_to_check as $user) {
            if ($credentials["username"] == $user["username"]) {
                throw new CustomUserMessageAuthenticationException('Email уже существует');
            }
        }
        //return ['token' => self::$newToken, 'roles' => ['ROLE_USER']];
        return self::$newToken;
    }

    public function getCurrentUser(string $token)
    {
        $users_to_check = [self::$user, self::$admin];
        foreach ($users_to_check as $user) {
            if ($token == $user["token"]) {
                return new UserDTO($user["username"], $user["roles"], $user['balance']);
            }
        }
        throw new CustomUserMessageAuthenticationException('Некорректный JWT токен');
    }

    public function authClient($client, $username, $password)
    {
        $crawler = $client->request('GET', '/login');

        $form = $crawler->selectButton('Далее')->form();
        $form['email'] = $username;
        $form['password'] = $password;

        $client->submit($form);
    }

    public function authAsAdmin($client)
    {
        self::authClient($client, self::$admin['username'], self::$admin['password']);
    }
}
