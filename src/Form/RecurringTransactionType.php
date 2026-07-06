<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\RecurringTransaction;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecurringTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'choices' => $options['accounts'],
                'choice_label' => fn (Account $account): string => $account->getName(),
                'label' => 'Compte',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choices' => $options['categories'],
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => false,
            ])
            ->add('description', TextType::class, ['label' => 'Description', 'required' => false])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => ['Dépense' => 'expense', 'Revenu' => 'income'],
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => false,
                'html5' => true,
            ])
            ->add('frequency', ChoiceType::class, [
                'label' => 'Fréquence',
                'choices' => ['Hebdomadaire' => 'weekly', 'Mensuelle' => 'monthly', 'Annuelle' => 'yearly'],
            ])
            ->add('nextRunDate', DateType::class, [
                'label' => 'Prochaine exécution',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('active', CheckboxType::class, ['label' => 'Active', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => RecurringTransaction::class]);
        $resolver->setRequired(['accounts', 'categories']);
    }
}
