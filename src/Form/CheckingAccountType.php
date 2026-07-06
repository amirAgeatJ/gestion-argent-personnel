<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\CheckingAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckingAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom du compte'])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => ['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CHF' => 'CHF'],
            ])
            ->add('overdraftLimit', MoneyType::class, [
                'label' => 'Découvert autorisé',
                'currency' => false,
                'html5' => true,
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CheckingAccount::class]);
    }
}
