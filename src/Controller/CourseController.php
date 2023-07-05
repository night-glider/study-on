<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\CourseType;
use App\Form\LessonType;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Service\BillingClient;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\MissingResourceException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Yaml\Exception\ExceptionInterface;

/**
 * @Route("/courses")
 */
class CourseController extends AbstractController
{
    private BillingClient $billingClient;
    private Security $security;

    public function __construct(BillingClient $billingClient, Security $security)
    {
        $this->billingClient = $billingClient;
        $this->security = $security;
    }
    /**
     * @Route("/", name="app_course_index", methods={"GET"})
     */
    public function index(CourseRepository $courseRepository): Response
    {
        $courses_data = [];
        foreach ($courseRepository->findAll() as $course) {
            $courses_data[$course->getCode()] = [
                "id" => $course->getId(),
                "name" => $course->getName(),
                "description" => $course->getDescription()
            ];
        }

        $transactions = [];
        if ($this->isGranted("ROLE_USER")) {
            $user = $this->security->getUser();
            $transactions = $this->billingClient->getTransactions($user->getApiToken(), 0, null, true);
            foreach ($transactions as $transaction) {
                if (isset($transaction["course"])) {
                    $courses_data[$transaction["course"]]["type"] = "Куплено";
                    if (isset($transaction["expirationDate"])) {
                        $courses_data[$transaction["course"]]["type"] = "Арендовано до " . $transaction["expirationDate"];
                    }
                }
            }
        }

        foreach ($this->billingClient->getCourses() as $course) {
            if (isset($courses_data[$course["code"]]["type"])) {
                continue;
            }
            $courses_data[$course["code"]]["type"] = $course["type"];
            if ($course["type"] == "free") {
                $courses_data[$course["code"]]["type"] = "Бесплатный";
                continue;
            }
            if ($course["type"] == "buy") {
                $courses_data[$course["code"]]["type"] = "Платный ";
            }
            if ($course["type"] == "rent") {
                $courses_data[$course["code"]]["type"] = "Аренда ";
            }

            if ($this->isGranted("ROLE_USER")) {
                $courses_data[$course["code"]]["type"] .= $course["price"] . "руб";
            }
        }


        return $this->render('course/index.html.twig', [
            'courses' => $courses_data,
        ]);
    }

    /**
     * @Route("/new", name="app_course_new", methods={"GET", "POST"})
     */
    public function new(Request $request, CourseRepository $courseRepository): Response
    {
        $course = new Course();
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->billingClient->newCourse(
                $this->security->getUser()->getApiToken(),
                [
                "name" => $form->get("name")->getData(),
                "code" => $form->get("code")->getData(),
                "price" => $form->get("price")->getData(),
                "type" => $form->get("type")->getData(),
                ]
            );
            $courseRepository->add($course, true);

            return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('course/new.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }


    /**
     * @Route("/{id}/new/lesson", name="app_lesson_new", methods={"GET", "POST"})
     */
    public function newLesson(Request $request, Course $course, LessonRepository $lessonRepository): Response
    {
        $lesson = new Lesson();
        $lesson->setCourse($course);
        $form = $this->createForm(LessonType::class, $lesson, [
            'course' => $course,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $lessonRepository->add($lesson, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $course->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
            'course' => $course,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_show", methods={"GET"})
     */
    public function show(Request $request, Course $course): Response
    {
        $user = $this->security->getUser();
        $billingCourse = $this->billingClient->getCourse($course->getCode());
        $billingUser = $this->billingClient->getCurrentUser($user->getApiToken());
        $transactions = $this->billingClient->getTransactions($user->getApiToken(), 0, $course->getCode(), true);

        $course = [
            'id' => $course->getId(),
            'code' => $course->getCode(),
            'name' => $course->getName(),
            'description' => $course->getDescription(),
            'lessons' => $course->getLessons(),
            'type' => $billingCourse['type'],
            'isPaid' => false,
            'price' => 0,
            'disablePayment' => false
        ];

        if($transactions) {
            $course["isPaid"] = true;
        }

        if (isset($billingCourse['price'])) {
            $course['price'] = $billingCourse['price'];
            if ($billingUser->getBalance() < $course['price']) {
                $course["disablePayment"] = true;
            }
        }
        if ($billingCourse['type'] === 'rent') {
            $course['price'] = $billingCourse['price'] . 'р';
        }
        if ($billingCourse['type'] === 'buy') {
            $course['price'] = $billingCourse['price'] . 'р';
        }
        if ($billingCourse['type'] === 'free') {
            $course['price'] = 'бесплатно';
            $course['buy_msg'] = "Получить ";
        }
        if ($billingCourse['type'] === "buy") {
            $course['buy_msg'] = "Купить за ";
        }
        if ($billingCourse['type'] === "rent") {
            $course['buy_msg'] = "Арендовать за ";
        }


        $status = null;
        if ($request->query->get('status') != null) {
            $status = $request->query->get('status');
        }

        return $this->render('course/show.html.twig', [
            'course' => $course,
            'billingUser' => $billingUser,
            'status' => $status,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_course_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->billingClient->editCourse(
                $this->security->getUser()->getApiToken(),
                $course->getCode(),
                [
                    "name" => $form->get("name")->getData(),
                    "code" => $form->get("code")->getData(),
                    "price" => $form->get("price")->getData(),
                    "type" => $form->get("type")->getData(),
                ]
            );

            $courseRepository->add($course, true);

            return $this->redirectToRoute(
                'app_course_show',
                ['id' => $course->getId()],
                Response::HTTP_SEE_OTHER
            );
        }

        return $this->renderForm('course/edit.html.twig', [
            'course' => $course,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_course_delete", methods={"POST"})
     */
    public function delete(Request $request, Course $course, CourseRepository $courseRepository): Response
    {
        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            $courseRepository->remove($course, true);
        }

        return $this->redirectToRoute('app_course_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @Route("/{id}/pay", name="app_course_pay", methods={"POST"})
     */
    public function pay(Request $request, Course $course): Response
    {
        $user = $this->security->getUser();
        $status = null;
        try {
            $responce = $this->billingClient->payForCourse($user->getApiToken(), $course->getCode());
            if ($responce['success']) {
                $status = "OK";
            }
        } catch (Exception $e) {
            $status = $e->getMessage();
        }

        return $this->redirectToRoute('app_course_show', ['id' => $course->getId(), 'status' => $status], Response::HTTP_SEE_OTHER);
    }
}
