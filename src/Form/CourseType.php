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
                'label' => 'Code',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'code can not be blank'),
                    new Length(
                        null,
                        null,
                        255,
                        null,
                        null,
                        null,
                        null,
                        'code should be less than 255 characters'
                    )
                ]
            ])
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
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'constraints' => [
                    new Length(
                        null,
                        null,
                        1000,
                        null,
                        null,
                        null,
                        null,
                        'description should be less than 1000 characters'
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
