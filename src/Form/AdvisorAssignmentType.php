<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\AdvisorAssignment;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdvisorAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('advisor', EntityType::class, [
                'class' => User::class,
                'choices' => $options['advisors'],
                'choice_label' => 'fullName',
                'label' => 'Conseiller',
            ])
            ->add('client', EntityType::class, [
                'class' => User::class,
                'choices' => $options['clients'],
                'choice_label' => 'fullName',
                'label' => 'Client',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AdvisorAssignment::class]);
        $resolver->setRequired(['advisors', 'clients']);
    }
}
