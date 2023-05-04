<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\DataFixtures\AppFixtures;
use Symfony\Component\HttpFoundation\Response;

class CourseControllerTest extends AbstractTest
{
    public function testGetActionsResponseOk(): void
    {
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $courses = $this->getEntityManager()->getRepository(Course::class)->findAll();
        foreach ($courses as $course) {
            // детальная страница курса
            $client->request('GET', '/courses/' . $course->getId());
            $this->assertResponseOk();

            // страница редактирования
            $client->request('GET', '/courses/' . $course->getId() . '/edit');
            $this->assertResponseOk();
        }
    }
    public function testSuccessfulCourseCreating(): void
    {
        // от списка курсов переходим на страницу создания курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $link = $crawler->selectLink('Добавить')->link();
        $crawler = $client->click($link);
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

        // проверяем редирект
        $this->assertSame($client->getResponse()->headers->get('location'), '/courses/');
        $crawler = $client->followRedirect();
        $this->assertResponseOk();

        $crawler = $client->request('GET', '/courses/' . $course->getId());
        $this->assertResponseOk();

        // проверяем корректность отображения данных
        $this->assertSame($crawler->filter('.course-name')->text(), $course->getName());
        $this->assertSame($crawler->filter('.course-description')->text(), $course->getDescription());
    }

    public function testCourseWithEmptyCodeCreating(): void
    {
        // от списка курсов переходим на страницу создания курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');

        $link = $crawler->selectLink('Добавить')->link();
        $crawler = $client->click($link);
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
        // от списка курсов переходим на страницу редактирования курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');

        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // на детальной странице курса
        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $form = $crawler->selectButton('Обновить')->form();

        // сохраняем id редактируемого курса
        $courseId = $this->getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy(['code' => $form['course[code]']->getValue()])->getId();

        // заполняем форму корректными данными
        $form['course[code]'] = 'successEdit';
        $form['course[name]'] = 'Course name for test';
        $form['course[description]'] = 'Description course for test';
        $client->submit($form);

        // проверяем редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // проверяем изменение данных
        $this->assertSame($crawler->filter('.course-name')->text(), 'Course name for test');
        $this->assertSame($crawler->filter('.course-description')->text(), 'Description course for test');
    }

    public function testCourseWithEmptyCodeEditing(): void
    {
        // от списка курсов переходим на страницу создания курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');

        $link = $crawler->selectLink('Добавить')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // заполняем форму создания курса корректными данными и отправляем
        $courseCreatingForm = $crawler->selectButton('Далее')->form([
            'course[code]' => 'unique-code1',
            'course[name]' => 'Course name for test',
            'course[description]' => 'Course description for test',
        ]);
        $client->submit($courseCreatingForm);

        $course_id = $this->getEntityManager()->getRepository(
            Course::class
        )->findOneBy(['code' => 'unique-code1'])->getId();

        // со страницы списка курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/' . $course_id);
        $this->assertResponseOk();

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $submitButton = $crawler->selectButton('Обновить');
        $form = $submitButton->form();

        // пробуем сохранить курс без кода
        $form['course[code]'] = '';
        $form['course[name]'] = 'Course name for test';
        $form['course[description]'] = 'Description course for test';
        $client->submit($form);
        $this->assertResponseCode(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Проверяем наличие сообщения об ошибке
        $this->assertSelectorTextContains(
            '.invalid-feedback.d-block',
            'Код не может быть пустым'
        );
    }

    public function testCourseDeleting(): void
    {
        // страница со списком курсов
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');

        $countBeforeDeleting = $crawler->filter('.course-link')->count();

        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();

        $this->assertCount($countBeforeDeleting - 1, $crawler->filter('.course-link'));
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
