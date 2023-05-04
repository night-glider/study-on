<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Код',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(null, 'Код не может быть пустым'),
                    new Length(
                        null,
                        null,
                        255,
                        null,
                        null,
                        null,
                        null,
                        'Код должен быть короче 255 символов'
                    )
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Название',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new NotBlank(null, 'Название не может быть пустым'),
                    new Length(
                        null,
                        null,
                        255,
                        null,
                        null,
                        null,
                        null,
                        'Название должно быть короче 255 символов'
                    )
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Length(
                        null,
                        null,
                        1000,
                        null,
                        null,
                        null,
                        null,
                        'Описание должно быть короче 1000 символов'
                    )
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
