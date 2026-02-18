<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('numeroCommande')
            ->add('dateCommande')
            ->add('datePrestation')
            ->add('heureLivraison')
            ->add('prixMenu')
            ->add('prixLivraison')
            ->add('nombrePersonne')
            ->add('statut')
            ->add('pretMateriel')
            ->add('restitutionMateriel')
            ->add('utilisateur', EntityType::class, [
                'class' => Utilisateur::class,
                'choice_label' => 'id',
            ])
            ->add('menus', EntityType::class, [
                'class' => Menu::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
        ]);
    }
}
