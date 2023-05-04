<?php

namespace App\Tests;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Tests\AbstractTest;
use App\DataFixtures\AppFixtures;

class LessonControllerTest extends AbstractTest
{
    public function testGetActionsResponseOk(): void
    {
        $client = $this->getClient();
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
        // от списка курсов переходим на страницу создания урока
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // создание урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $form = $crawler->selectButton("Далее")->form();
        // сохраняем id курса
        $courseId = $form['lesson[course]']->getValue();

        // заполняем форму создания урока корректными данными и отправляем
        $form['lesson[name]'] = 'New Last lesson';
        $form['lesson[content]'] = 'Lesson content';
        $form['lesson[nindex]'] = '99';
        $client->submit($form);

        // проверяем редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        $this->assertResponseOk();
        $this->assertSame($crawler->filter('.lesson-ref')->last()->text(), 'New Last lesson');

        $crawler = $client->click($crawler->filter('.lesson-ref')->last()->link());
        $this->assertResponseOk();

        // проверим название и содержание
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'New Last lesson');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'Lesson content');
    }

    public function testLessonFailedCreating(): void
    {
        // от списка курсов переходим на страницу создания урока
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // создание урока
        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
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
        // от списка курсов переходим на страницу редактирования курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->filter('.lesson-ref')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $form = $crawler->selectButton('Обновить')->form();

        // сохраняем id курса
        $courseId = $this->getEntityManager()
            ->getRepository(Course::class)
            ->findOneBy([
                'id' => $form['lesson[course]']->getValue(),
            ])->getId();

        // заполняем форму корректными данными
        $form['lesson[nindex]'] = '99';
        $form['lesson[name]'] = 'Test edit lesson';
        $form['lesson[content]'] = 'Test edit lesson content';
        $client->submit($form);

        // проверяем редирект
        $crawler = $client->followRedirect();
        $this->assertRouteSame('app_course_show', ['id' => $courseId]);
        $this->assertResponseOk();

        // проверяем, что урок отредактирован
        $this->assertSame($crawler->filter('.lesson-ref')->last()->text(), 'Test edit lesson');
        $link = $crawler->filter('.lesson-ref')->last()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        // проверим название и содержание
        $this->assertSame($crawler->filter('.lesson-name')->first()->text(), 'Test edit lesson');
        $this->assertSame($crawler->filter('.lesson-content')->first()->text(), 'Test edit lesson content');
    }

    public function testLessonFailedEditing(): void
    {
        // от списка курсов переходим на страницу редактирования курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->filter('.lesson-ref')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
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
        // от списка курсов переходим на страницу просмотра курса
        $client = $this->getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertResponseOk();

        // на детальную страницу курса
        $link = $crawler->filter('.course-link')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $countBeforeDeleting = $crawler->filter('.lesson-ref')->count();

        // переходим к деталям урока
        $link = $crawler->filter('.lesson-ref')->first()->link();
        $crawler = $client->click($link);
        $this->assertResponseOk();

        $client->submitForm('Удалить');
        $crawler = $client->followRedirect();

        $this->assertCount($countBeforeDeleting - 1, $crawler->filter('.lesson-ref'));
    }

    protected function getFixtures(): array
    {
        return [AppFixtures::class];
    }
}
