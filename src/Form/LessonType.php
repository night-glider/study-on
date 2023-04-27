<?php

namespace App\Form;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToString;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class LessonType extends AbstractType
{
    private CourseToString $transformer;

    public function __construct(CourseToString $transformer)
    {
        $this->transformer = $transformer;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'name can not be blank'),
                    new Length(
                        null,
                        null,
                        255,
                        null,
                        null,
                        null,
                        null,
                        'name should be less than 255 characters'
                    )
                ]
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'content can not be blank'),
                ]
            ])
            ->add('nindex', IntegerType::class, [
                'label' => 'Index',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'index can not be blank'),
                    new Range(
                        null,
                        'message should be between {{ min }} до {{ max }}',
                        null,
                        null,
                        null,
                        null,
                        0,
                        null,
                        100,
                    ),
                ]
            ])
            ->add('course', HiddenType::class)
        ;
        $builder
            ->get('course')
            ->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'course' => null,
        ]);
    }
}
