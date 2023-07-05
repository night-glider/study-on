<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\DTO\UserDTO;
use App\Exception\BillingUnavailableException;
use App\Security\User;
use App\Tests\AbstractTest;
use App\Service\BillingClient;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Util\Exception;
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
        'balance' => 100.0,
    ];

    private static array $admin = [
        'username' => 'admin@gmail.com',
        'password' => 'admin',
        'roles' => ['ROLE_USER', 'ROLE_SUPER_ADMIN'],
        'balance' => 100.0,
    ];

    private static array $courses = [
        [
            "code" => "Godot4beginner",
            "type" => "free"
        ],
        [
            "code" => "unity_beginner",
            "price" => 20,
            "type" => "buy"
        ],
        [
            "code" => "UE5pro",
            "price" => 10,
            "type" => "rent"
        ]
    ];

    private static array $transactions = [
        [
        "id" => 54,
        "type" => "deposit",
        "value" => 100.5,
        "creationDate" => "2023-07-01 05:56:16"
        ],
        [
            "id"  => 58,
            "course" => "UE5pro",
            "type" => "payment",
            "value" => 10,
            "creationDate" => "2023-07-01 05:58:45",
            "expirationDate" => "2077-07-01 05:58:45"
        ]
    ];


    public function __construct()
    {
        self::$user["token"] = $this->generateToken(self::$user["roles"], self::$user["username"]);

        self::$admin["token"] = $this->generateToken(self::$admin["roles"], self::$admin["username"]);
    }

    public function auth($credentials)
    {
        $users_to_check = [self::$user, self::$admin];

        foreach ($users_to_check as $user) {
            if (
                $credentials["username"] == $user["username"]
                && $credentials["password"] == $user["password"]
            ) {
                $result = [
                    "token" => $user["token"],
                    "refresh_token" => $user["username"] . 'refresh_token'
                ];
                return $result;
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
        return $this->generateToken(['ROLE_USER'], $credentials["username"]);
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

    public function refreshToken(string $refreshToken)
    {
    }

    public function getCourse($code)
    {
        foreach (self::$courses as $course) {
            if ($course["code"] == $code) {
                return $course;
            }
        }
        return [
            "code" => $code,
            "type" => "free",
            "price" => 0
        ];
    }

    public function getCourses()
    {
        return self::$courses;
    }

    public function getTransactions($token, $type = null, $code = null, $skip_expired = false)
    {
        if(!$code) {
            return self::$transactions;
        }
        $result = [];
        foreach (self::$transactions as $transaction) {
            if (isset($transaction["code"])) {
                if ($transaction["code"] == $code) {
                    $result[] = $transaction;
                }
            }
        }
        return $result;
    }

    public function payForCourse($token, $code)
    {
        if ($code == "expensive_course") {
            throw new Exception("На счету недостаточно средств");
        }
        return [
            "succes" => true,
            "type" => "buy"
        ];
    }

    private function generateToken(array $roles, string $username): string
    {
        $data = [
            'email' => $username,
            'roles' => $roles,
            'exp' => (new \DateTime('+ 1 hour'))->getTimestamp(),
        ];
        $query = base64_encode(json_encode($data));

        return 'header.' . $query . '.signature';
    }

    public function authAsAdmin($client)
    {
        self::authClient($client, self::$admin['username'], self::$admin['password']);
    }
}
