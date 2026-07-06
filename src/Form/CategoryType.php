<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => ['Dépense' => 'expense', 'Revenu' => 'income'],
            ])
            ->add('icon', TextType::class, [
                'label' => 'Icône (emoji)',
                'required' => false,
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choices' => $options['availableParents'],
                'choice_label' => 'name',
                'label' => 'Catégorie parente',
                'required' => false,
                'placeholder' => '-- Aucune --',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Category::class]);
        $resolver->setRequired(['availableParents']);
    }
}
