<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email',EmailType::class,[
                'attr'=>[
                    'class'=> 'fr-input'
                ]
            ])
            ->add('roles',ChoiceType::class,[
                'attr'=>[
                    'class'=> 'fr-select'
                ],
                'choices'=>[
                    'Administrateur'=>'ROLE_ADMIN_PARTENAIRE',
                    'Utilisateur'=>'ROLE_USER_PARTENAIRE'
                ],
                'placeholder'=> '--- Selectionnez ---'
            ])
            ->add('nom',TextType::class,[
                'attr'=>[
                    'class'=> 'fr-input'
                ]
            ])
            ->add('prenom',TextType::class,[
                'attr'=>[
                    'class'=> 'fr-input'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
