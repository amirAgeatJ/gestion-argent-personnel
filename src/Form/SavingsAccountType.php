<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SavingsAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SavingsAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom du compte'])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => ['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CHF' => 'CHF'],
            ])
            ->add('interestRate', NumberType::class, [
                'label' => 'Taux d\'intérêt (%)',
                'html5' => true,
                'required' => false,
                'scale' => 2,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SavingsAccount::class]);
    }
}
