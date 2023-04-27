<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $newCourse = new Course();
        $newCourse->setName("Unity для начинающих");
        $newCourse->setCode("unity_beginner");
        $newCourse->setDescription(null);
        $manager->persist($newCourse);

        $newLesson = new Lesson();
        $newLesson->setNindex(1);
        $newLesson->setName("Введение");
        $newLesson->setContent("Unity сделан, чтобы дать вам возможности для создания лучших интерактивных развлечений или мультимедиа. Данное руководство создано, чтобы помочь вам узнать, как использовать Unity, от базовых до продвинутых приемов. Его можно читать от начала до конца, или использовать в качестве справочника.");
        $newCourse->addLesson($newLesson);
        $manager->persist($newLesson);

        $newLesson = new Lesson();
        $newLesson->setNindex(2);
        $newLesson->setName("Скриптинг");
        $newLesson->setContent("Скриптинг - необходимая составляющая всех игр. Даже самые простые игры нуждаются в скриптах для реакции на действия игрока и организации событий геймплея. Кроме того, скрипты могут быть использованы для создания графических эффектов, управления физическим поведением объектов или реализации пользовательской ИИ системы для персонажей игры.
            Для изучения скриптинга требуется время и усилия. Цель этого раздела - не научить вас писать код с нуля, а объяснить основные понятия, используемые для скриптинга в Unity.");
        $newCourse->addLesson($newLesson);
        $manager->persist($newLesson);

        $newCourse = new Course();
        $newCourse->setName("Godot 4 для начинающих");
        $newCourse->setCode("Godot4beginner");
        $newCourse->setDescription("Godot 4 для самых маленьких");
        $manager->persist($newCourse);

        $newLesson = new Lesson();
        $newLesson->setNindex(1);
        $newLesson->setName("Введение");
        $newLesson->setContent("Игровой движок - это сложный инструмент, поэтому трудно рассказать о Godot в нескольких словах. Вот краткая характеристика, которую вы можете использовать, если потребуется быстро описать Godot Engine.
            Godot Engine - это многофункциональный кроссплатформенный игровой движок с унифицированным интерфейсом для создания как 2D-, так и 3D-игр. Он предоставляет полный набор общих инструментов, чтобы пользователи могли сосредоточиться на создании игр без необходимости изобретать колесо. Игры могут быть импортированы в один клик на множество платформ, включая основные настольные платформы (Linux, macOS, Windows), а также мобильные (Android, iOS) и веб-платформы (HTML5).
            Godot абсолютно бесплатен и имеет открытый исходный код в соответствии с разрешительной лицензией MIT. Никаких условий, никаких отчислений, ничего. Игры пользователей принадлежат им, вплоть до последней строчки кода движка. Разработка Godot полностью независима и ведется сообществом, что дает пользователям возможность помочь сформировать движок в соответствии со своими ожиданиями. Поддерживается некоммерческой организацией Software Freedom Conservancy.");
        $newCourse->addLesson($newLesson);
        $manager->persist($newLesson);

        $newLesson = new Lesson();
        $newLesson->setNindex(2);
        $newLesson->setName("Языки");
        $newLesson->setContent("Официально поддерживаемые языки для Godot это GDScript, Visual Scripting, C# и C++. Смотрите подкатегории для каждого языка в секции Скриптинг. Если вы только начинаете разбираться с Godot или с разработкой игр в целом, то рекомендуем изучить язык GDScript, поскольку он является родным языком для Godot. Как правило, в долгосрочной перспективе скриптовые языки программирования менее эффективны, чем языки низкого уровня, но для прототипирования, создания минимально жизнеспособных продуктов (minimum viable product, MVP) и уменьшения времени выхода на рынок (Time-To-Market, TTM) GDScript предоставляет достаточно быстрый, дружественный и действенный путь разработки.");
        $newCourse->addLesson($newLesson);
        $manager->persist($newLesson);

        $newLesson = new Lesson();
        $newLesson->setNindex(3);
        $newLesson->setName("Лицензия");
        $newLesson->setContent("Godot создан и распространяется по MIT License. У него нет единственного владельца, так как каждый автор, предоставляющий код в проект, делает это под той же лицензией и сохраняет право собственности на внесенный вклад.
            Лицензия — это юридические требования к вам (или вашей компании) по использованию и распространению программного обеспечения (и производных проектов, в том числе игр, разработанных при помощи ПО). Ваша игра или проект могут иметь иную лицензию, но она должна быть совместима с изначальной.
            Предупреждение
            Не забывай указывать в титрах использованные сторонние ресурсы, такие как текстуры, модели, звуки и шрифты.
            Бесплатные ресурсы зачастую имеют лицензии, что обязывают указание их использования. Проверяйте лицензии прежде чем использовать что-то в вашем проекте.
");
        $newCourse->addLesson($newLesson);
        $manager->persist($newLesson);

        $newCourse = new Course();
        $newCourse->setName("UE5 для профи");
        $newCourse->setCode("UE5pro");
        $newCourse->setDescription("Если ты профи, то зачем тебе этот курс?");
        $manager->persist($newCourse);

        $newLesson = new Lesson();
        $newLesson->setNindex(1);
        $newLesson->setName("Введение");
        $newLesson->setContent("Спасибо за деньги. Ты уже профи. Мне нечему тебя учить. Иди отсюда.");
        $newCourse->addLesson($newLesson);
        $manager->persist($newLesson);
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
