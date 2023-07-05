<?php

namespace App\Form;

use App\Entity\Course;
use App\Enum\PaymentStatus;
use App\Service\BillingClient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CourseType extends AbstractType
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $entity = $builder->getData();
        if ($entity->getCode() != null) {
            $billingCourse = $this->billingClient->getCourse($entity->getCode());
        } else {
            $billingCourse = [];
        }
        if (!isset($billingCourse['price'])) {
            $billingCourse['price'] = 0;
        }
        if (!isset($billingCourse['type'])) {
            $billingCourse['type'] = 'free';
        }
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
            ->add('type', ChoiceType::class, [
                'label' => 'Тип',
                'choices' => [
                    'бесплатный' => 0,
                    'покупка' => 1,
                    'аренда' => 2,
                ],
                'required' => true,
                'mapped' => false,
                'data' => PaymentStatus::VALUES[$billingCourse['type']],
            ])
            ->add('price', MoneyType::class, [
                'label' => 'Цена',
                'currency' => 'RUB',
                'html5' => true,
                'mapped' => false,
                'empty_data' => 0,
                'attr' => ['min' => 0],
                'data' => $billingCourse['price'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
            'price' => 0.0,
            'type' => PaymentStatus::FREE
        ]);
    }
}
