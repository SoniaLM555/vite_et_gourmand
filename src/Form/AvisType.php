<?php

namespace App\Form;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvisType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isAdmin = $options['is_admin'];
        if ($isAdmin) {
            $builder->add('numeroCommandeSaisi', TextType::class, [
                    'label' => 'Numéro de la commande',
                    'mapped' => false,
                    'required' => true,
                    'attr' => [
                        'class' => 'form-control',
                        'placeholder' => 'Ex: CMD-12345'
                    ]
            ]);
        }
        $builder
            ->add('note', null, [
                'label' => 'Votre note (sur 5)',
                'attr' => [ 'min' => 1,  'max' => 5,  'class' => 'form-control', 'placeholder' => 'Ex: 5'
                ]
            ])
            ->add('commentaire', null, [
                'label' => 'Votre commentaire',
                'attr' => [
                    'class' => 'form-control',  'rows' => 4, 'placeholder' => 'Partagez votre expérience avec Julie et José...'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Avis::class,
            'is_admin' => false,
        ]);
    }
}
