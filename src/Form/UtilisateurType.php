<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Bundle\SecurityBundle\Security; 
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Role;

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
        ;

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $builder
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
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder
                ->add('password', PasswordType::class, [
                    'label' => 'Mot de passe',
                    'mapped' => false, 
                    'attr' => ['autocomplete' => 'new-password'],
                    'constraints' => [
                        // 1. NotBlank : Syntaxe nommée
                        new NotBlank(message: 'Veuillez saisir un mot de passe'),

                        // 2. Length : Syntaxe nommée
                        new Length(
                            min: 8, 
                            max: 4096, 
                            minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères'
                        ),

                        // 3. Regex : Syntaxe nommée (c'est ici que tu avais l'erreur)
                        new Regex(
                            pattern: '/(?=.*[A-Z])(?=.*[a-z])(?=.*\d).+/', 
                            message: 'Le mot de passe doit contenir au moins une majuscule, une minuscule et un chiffre.'
                        ),
                    ],
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