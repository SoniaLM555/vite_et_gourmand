<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\Regime;
use App\Entity\Theme;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('description')
            ->add('nombrePersonneMin')
            ->add('prixParPersonne')
            ->add('quantiteRestante')
            ->add('theme', EntityType::class, [
                'class' => Theme::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('regimes', EntityType::class, [
                'class' => Regime::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            
            ->add('plats', EntityType::class, [
                'class' => Plat::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class,
        ]);
    }
}
