<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\DataFixtures\AppFixtures;
use App\Tests\Mock\BillingMock;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends AbstractTest
{
    public function urlProviderIsSuccessful(): \Generator
    {
        yield ['/'];
        yield ['/courses/'];
        yield ['/courses/new'];
    }

    /**
     * @dataProvider urlProviderIsSuccessful
     */
    public function testPageIsSuccessful($url): void
    {
        $client = $this->getClient();

        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);

        $client->request('GET', $url);
        $this->assertResponseOk();
    }


    public function urlProviderNotFound(): \Generator
    {
        yield ['/not-found/'];
        yield ['/courses/-1'];
        yield ['/abvgd'];
    }

    /**
     * @dataProvider urlProviderNotFound
     */
    public function testPageIsNotFound($url): void
    {
        $client = $this->getClient();
        $client->request('GET', $url);
        $this->assertResponseCode(404);
    }

    public function testGetActionsResponseOk(): void
    {
        $client = $this->getClient();

        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);

        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();
        $course = $this->getEntityManager()->getRepository(Course::class)->findAll()[0];

        // детальная страница курса
        $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseOk();

        // страница редактирования
        $client->request('GET', '/courses/' . $course->getId() . '/edit');
        $this->assertResponseOk();
    }
    public function testSuccessfulCourseCreating(): void
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
        $client->followRedirect();

        $crawler = $client->request('GET', '/courses/new');
        $this->assertResponseOk();

        // заполняем форму создания курса корректными данными и отправляем
        $courseCreatingForm = $crawler->selectButton('Далее')->form([
            'course[code]' => 'unique-code1',
            'course[name]' => 'Course name for test',
            'course[description]' => 'Course description for test',
        ]);
        $client->submit($courseCreatingForm);

        $course = $this->getEntityManager()->getRepository(Course::class)->findOneBy([
            'code' => 'unique-code1',
        ]);

        $this->assertSame('unique-code1', $course->getCode());
        $this->assertSame('Course name for test', $course->getName());
        $this->assertSame('Course description for test', $course->getDescription());
    }

    public function testCourseFailedCreating(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $crawler = $client->request('GET', '/courses/new');
        $this->assertResponseOk();

        $fieldData = [
            'validData' => [
                'course[name]' => 'test_name',
                'course[code]' => 'test_code',
                'course[description]' => 'test_description',
            ],
            'invalidData' => [
                'course[name]' => [
                    '' => 'Название не может быть пустым',
                    str_repeat('h', 260) => 'Название должно быть короче 255 символов'
                ],
                'course[code]' => [
                    '' => 'Код не может быть пустым',
                    str_repeat('h', 260) => 'Код должен быть короче 255 символов'
                ],
                'course[description]' => [
                    str_repeat('h', 1001)  => 'Описание должно быть короче 1000 символов',
                ]
            ],
        ];
        $this->assertFormFieldsValidation($crawler->selectButton("Далее"), $fieldData);
    }


    public function testCourseSuccessfulEditing(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();

        $crawler = $client->request('GET', '/courses/' . $course->getId() . '/edit');
        $this->assertResponseOk();
        $form = $crawler->selectButton('Обновить')->form();

        // заполняем форму корректными данными
        $form['course[code]'] = 'edited';
        $form['course[name]'] = "edited";
        $form['course[description]'] = "edited";
        $client->submit($form);

        // проверяем редирект
        $crawler = $client->followRedirect();
        //dd($crawler->html());
        $this->assertResponseOk();

        $course = $this->getEntityManager()->find(Course::class, $course->getId());
        $this->assertSame("edited", $course->getCode());
        $this->assertSame("edited", $course->getName());
        $this->assertSame("edited", $course->getDescription());
    }

    public function testCourseFailedEditing(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();

        $crawler = $client->request('GET', '/courses/' . $course->getId() . '/edit');
        $this->assertResponseOk();

        $fieldData = [
            'validData' => [
                'course[name]' => 'test_name',
                'course[code]' => 'test_code',
                'course[description]' => 'test_description',
            ],
            'invalidData' => [
                'course[name]' => [
                    '' => 'Название не может быть пустым',
                    str_repeat('h', 260) => 'Название должно быть короче 255 символов'
                ],
                'course[code]' => [
                    '' => 'Код не может быть пустым',
                    str_repeat('h', 260) => 'Код должен быть короче 255 символов'
                ],
                'course[description]' => [
                    str_repeat('h', 1001)  => 'Описание должно быть короче 1000 символов',
                ]
            ],
        ];
        $this->assertFormFieldsValidation($crawler->selectButton("Обновить"), $fieldData);
    }

    public function testCourseDeleting(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();

        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseOk();

        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();

        $this->assertNull($this->getEntityManager()->find(Course::class, $course->getId()));
    }

    public function testCoursePaySuccessful(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
        $client->request('POST', '/courses/' . $course->getId() . "/pay");
        $client->followRedirect();
        $this->assertResponseOk();
    }

    public function testCourseFail(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("expensive_course");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);
        $this->getEntityManager()->flush();
        $client->request('POST', '/courses/' . $course->getId() . "/pay");
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        $this->assertEquals('На счету недостаточно средств', $crawler->filter('.error-message')->text());
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
