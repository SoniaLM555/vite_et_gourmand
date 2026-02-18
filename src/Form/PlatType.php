<?php

namespace App\Form;

use App\Entity\Allergene;
use App\Entity\Menu;
use App\Entity\Plat;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class PlatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('image', FileType::class, [
                'label' => 'Photo du plat',
                'mapped' => false,
                'required' => false, 
                'constraints' => [
                    new File(
                        maxSize : '2M',
                        extensions : ['jpg', 'jpeg', 'png'],
                        extensionsMessage : 'Format JPG ou PNG uniquement',
                    )
                ],
            ])
            ->add('description')
            ->add('allergenes', EntityType::class, [
                'class' => Allergene::class,
                'choice_label' => 'nom',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('menus', EntityType::class, [
                'class' => Menu::class,
                'choice_label' => 'titre',
                'multiple' => true,
                'expanded' => true,
                'mapped' => false, 
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Plat::class,
        ]);
    }
}
