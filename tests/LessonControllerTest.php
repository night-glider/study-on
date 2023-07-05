<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToString;
use App\Tests\AbstractTest;
use App\DataFixtures\AppFixtures;
use App\Tests\Mock\BillingMock;
use phpDocumentor\Reflection\Types\Void_;

class LessonControllerTest extends AbstractTest
{
    public function testUnauthorizedAccess(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authClient($client, "user@gmail.com", "user");
        $lesson = $this->getEntityManager()->getRepository(Lesson::class)->findAll()[0];
        $client->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseCode(403);
    }

    public function testGetActionsResponseOk(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $lessons = $this->getEntityManager()->getRepository(Lesson::class)->findAll();
        foreach ($lessons as $lesson) {
            // детальная страница урока
            $client->request('GET', '/lessons/' . $lesson->getId());
            $this->assertResponseOk();

            // страница редактирования урока
            $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
            $this->assertResponseOk();
        }
    }

    public function testSuccessfulLessonCreating(): void
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

        $client->request('GET', '/courses/' . $course->getId() . '/new/lesson/');
        $crawler = $client->followRedirect();
        $this->assertResponseOk();
        $form = $crawler->selectButton("Далее")->form();

        $form['lesson[name]'] = 'new test lesson';
        $form['lesson[content]'] = 'Lesson content';
        $form['lesson[nindex]'] = '99';
        $client->submit($form);

        // проверяем редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $course->getId()]);
        $this->assertResponseOk();
        $course = $this->getEntityManager()->getRepository(Course::class)->findOneBy([
            'code' => 'test']);
        $lesson = $course->getLessons()[0];
        $this->assertSame("new test lesson", $lesson->getName());
        $this->assertSame(99, $lesson->getNindex());
        $this->assertSame("Lesson content", $lesson->getContent());
    }

    public function testLessonFailedCreating(): void
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
        $client->request('GET', '/courses/' . $course->getId() . '/new/lesson/');
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        $fieldData = [
            'validData' => [
                'lesson[name]' => 'test_name',
                'lesson[content]' => 'test_content',
                'lesson[nindex]' => '99',
            ],
            'invalidData' => [
                'lesson[name]' => [
                    '' => 'Название не может быть пустым',
                    str_repeat('h', 260) => 'Название должно быть короче 255 символов'
                ],
                'lesson[content]' => [
                    '' => 'Контент не может быть пустым'
                ],
                'lesson[nindex]' => [
                    '' => 'Индекс не может быть пустым',
                    '-9' => 'Индекс должен быть в диапазоне от 0 до 100',
                    '101' => 'Индекс должен быть в диапазоне от 0 до 100'
                ]
            ],
        ];
        $this->assertFormFieldsValidation($crawler->selectButton("Далее"), $fieldData);
    }

    public function testLessonSuccessfulEditing(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);

        $lesson = new Lesson();
        $lesson->setCourse($course);
        $lesson->setName("test name");
        $lesson->setContent("test content");
        $lesson->setNindex(99);
        $this->getEntityManager()->persist($lesson);

        $this->getEntityManager()->flush();


        $crawler = $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseOk();
        $form = $crawler->selectButton('Обновить')->form();


        // заполняем форму корректными данными
        $form['lesson[nindex]'] = 1;
        $form['lesson[name]'] = "edited";
        $form['lesson[content]'] = "edited";
        $client->submit($form);

        // проверяем редирект
        $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $lesson->getCourse()->getId()]);
        $this->assertResponseOk();

        #$this->getEntityManager()->refresh($lesson);
        $lesson = $this->getEntityManager()->find(Lesson::class, $lesson->getId());
        $this->assertSame(1, $lesson->getNindex());
        $this->assertSame("edited", $lesson->getName());
        $this->assertSame("edited", $lesson->getContent());
    }

    public function testLessonFailedEditing(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);

        $lesson = new Lesson();
        $lesson->setCourse($course);
        $lesson->setName("test name");
        $lesson->setContent("test content");
        $lesson->setNindex(99);
        $this->getEntityManager()->persist($lesson);

        $this->getEntityManager()->flush();

        $crawler = $client->request('GET', '/lessons/' . $lesson->getId() . '/edit');
        $this->assertResponseOk();

        $fieldData = [
            'validData' => [
                'lesson[name]' => 'test_name',
                'lesson[content]' => 'test_content',
                'lesson[nindex]' => '99',
            ],
            'invalidData' => [
                'lesson[name]' => [
                    '' => 'Название не может быть пустым',
                    str_repeat('h', 260)  => 'Название должно быть короче 255 символов'
                ],
                'lesson[content]' => [
                    '' => 'Контент не может быть пустым'
                ],
                'lesson[nindex]' => [
                    '' => 'Индекс не может быть пустым',
                    '-9' => 'Индекс должен быть в диапазоне от 0 до 100',
                    '101' => 'Индекс должен быть в диапазоне от 0 до 100'
                ]
            ],
        ];
        $this->assertFormFieldsValidation($crawler->selectButton("Обновить"), $fieldData);
    }

    public function testLessonDeleting(): void
    {
        $client = $this->getClient();
        $billingMock = new BillingMock();
        $billingMock->authAsAdmin($client);
        $course = new Course();
        $course->setName("test");
        $course->setCode("test");
        $course->setDescription("test");
        $this->getEntityManager()->persist($course);

        $lesson = new Lesson();
        $lesson->setCourse($course);
        $lesson->setName("test name");
        $lesson->setContent("test content");
        $lesson->setNindex(99);
        $this->getEntityManager()->persist($lesson);

        $this->getEntityManager()->flush();

        $crawler = $client->request('GET', '/lessons/' . $lesson->getId());
        $this->assertResponseOk();

        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();

        $this->assertNull($this->getEntityManager()->find(Lesson::class, $lesson->getId()));
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
