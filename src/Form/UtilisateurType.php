<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security; 

class UtilisateurType extends AbstractType
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', null, [
                'label' => 'Adresse email'
            ])
            ->add('nom', null, [
                'label' => 'Nom de famille'
            ])
            ->add('prenom', null, [
                'label' => 'Prénom'
            ])
            ->add('telephone', null, [
                'label' => 'Numéro de téléphone'
            ])
            ->add('pays', null, [
                'label' => 'Pays'
            ])
            
            ->add('codePostal', null, [
                'label' => 'Code Postal'
            ])
            
            ->add('ville', null, [
                'label' => 'Ville de livraison'
            ])
            ->add('adressePostale', null, [
                'label' => 'Adresse postale'
            ])
        ;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder
                ->add('isVerified', null, [
                    'label' => 'Compte email vérifié (Lien cliqué)'
                ])
                ->add('roleObjet', null, [
                    'label' => 'Rôle attribué sur le site'
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
