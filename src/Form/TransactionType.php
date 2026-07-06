<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Formulaire dynamique : la liste des catégories proposées dépend du type de transaction
 * choisi (revenu/dépense), et est recalculée à la soumission via les Form Events
 * (PRE_SET_DATA pour l'affichage initial, PRE_SUBMIT si l'utilisateur change le type).
 */
class TransactionType extends AbstractType
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'choices' => $options['accounts'],
                'choice_label' => fn (Account $account): string => sprintf('%s (%s)', $account->getName(), $account->getTypeLabel()),
                'label' => 'Compte',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Dépense' => 'expense',
                    'Revenu' => 'income',
                    'Virement' => 'transfer',
                ],
            ])
            ->add('transferToAccount', EntityType::class, [
                'class' => Account::class,
                'choices' => $options['accounts'],
                'choice_label' => fn (Account $account): string => sprintf('%s (%s)', $account->getName(), $account->getTypeLabel()),
                'label' => 'Compte de destination',
                'required' => false,
                'placeholder' => '-- uniquement pour un virement --',
            ])
            ->add('amount', MoneyType::class, [
                'label' => 'Montant',
                'currency' => false,
                'html5' => true,
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => ['EUR' => 'EUR', 'USD' => 'USD', 'GBP' => 'GBP', 'CHF' => 'CHF'],
            ])
            ->add('description', TextType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('occurredAt', DateTimeType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choices' => $options['tags'],
                'choice_label' => 'name',
                'label' => 'Étiquettes',
                'multiple' => true,
                'required' => false,
            ])
        ;

        $addCategoryField = function (FormInterface $form, User $user, ?string $type): void {
            $form->add('category', EntityType::class, [
                'class' => Category::class,
                'choices' => $type !== null && $type !== 'transfer'
                    ? $this->categoryRepository->findAvailableForUser($user, $type)
                    : [],
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'required' => false,
                'placeholder' => '-- Choisir une catégorie --',
            ]);
        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($addCategoryField, $user): void {
                $transaction = $event->getData();
                $type = $transaction instanceof Transaction ? $transaction->getType() : null;
                $addCategoryField($event->getForm(), $user, $type);
            },
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($addCategoryField, $user): void {
                $data = $event->getData();
                $type = is_array($data) ? ($data['type'] ?? null) : null;
                $addCategoryField($event->getForm(), $user, $type);
            },
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transaction::class,
        ]);
        $resolver->setRequired(['user', 'accounts', 'tags']);
    }
}
