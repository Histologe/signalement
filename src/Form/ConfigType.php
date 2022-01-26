<?php

namespace App\Form;

use App\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomTerritoire', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
            ->add('logotype', FileType::class, [
                'attr' => [
                    'class' => 'fr-upload'
                ],
                'row_attr'=>[
                    'class'=> 'fr-upload-group fr-mb-5v'
                ],
                'data_class'=>null
            ])
            ->add('urlTerritoire', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
            ->add('nomDpo', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
            ->add('mailDpo', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
            ->add('nomResponsable', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
            ->add('mailResponsable', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
            ->add('adresseDpo', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'row_attr'=>[
                    'class'=> 'fr-input-group'
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
            'attr'=>[
                'id' => 'config-form'
            ]
        ]);
    }
}
