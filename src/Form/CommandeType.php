<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\Menu;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombrePersonne', IntegerType::class, ['label' => 'Nombre de convives (personnes)','attr' => ['min' => 1]])
            ->add('quantite', IntegerType::class, ['label' => 'Nombre de buffets/menus souhaités','data' => 1,'attr' => ['min' => 1]])
            ->add('datePrestation', TextType::class, ['label' => 'Date de la prestation (JJ/MM/AAAA)'])
            ->add('heureLivraison', TextType::class, ['label' => 'Heure souhaitée de livraison (Ex: 12h30)'])
            ->add('pretMateriel', CheckboxType::class, ['label' => 'Souhaitez-vous le prêt de matériel (gratuit, soumis à restitution sous 10 jours) ?','required' => false]);

        if (isset($options['is_admin']) && $options['is_admin'] === true) {
                    $builder
                        ->add('statut', ChoiceType::class, ['label' => 'Statut', 'choices' => [
                            'En attente de validation' => 'En attente',
                            'Accepter la commande' => 'Accepté',
                            'En cours de préparation' => 'En préparation',
                            'Commande livrée' => 'Livré',
                            'En attente retour matériel' => 'En attente matériel',
                            'Annuler la commande' => 'Annulé'],
                            'attr' => ['class' => 'form-select']])
                        ->add('restitutionMateriel', CheckboxType::class, ['required' => false])
                        ->add('confirmation_contact', CheckboxType::class, ['mapped' => false,'required' => false])
                        ->add('motif_annulation', TextType::class, ['mapped' => false,'required' => false]);
                    }

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Commande::class,'is_admin' => false]);
    }
}
