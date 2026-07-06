<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\SavingsAccount;
use App\Entity\SavingsGoal;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SavingsGoalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom de l\'objectif'])
            ->add('targetAmount', MoneyType::class, [
                'label' => 'Montant cible',
                'currency' => false,
                'html5' => true,
            ])
            ->add('deadline', DateType::class, [
                'label' => 'Échéance',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
            ])
            ->add('account', EntityType::class, [
                'class' => SavingsAccount::class,
                'choices' => $options['savingsAccounts'],
                'choice_label' => 'name',
                'label' => 'Compte épargne associé',
                'required' => false,
                'placeholder' => '-- Aucun --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SavingsGoal::class]);
        $resolver->setRequired(['savingsAccounts']);
    }
}
