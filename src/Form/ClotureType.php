<?php

namespace App\Form;

use App\Entity\Cloture;
use App\Entity\MotifCloture;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClotureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('motif',EntityType::class,[
                'class'=>MotifCloture::class,
                'choice_label'=> 'label',
                'row_attr'=> [
                    'class'=> 'fr-select-group'
                ],
                'attr'=> [
                    'class'=> 'fr-select'
                ],
                'help'=>'Choisissez un motif de cloture parmis la liste ci-dessous.',
                'help_attr'=> [
                    'class'=> 'fr-hint-text'
                ]
            ])
            ->add('type',HiddenType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Cloture::class,
            'attr'=>[
                'id'=>'cloture_form'
            ],
        ]);
    }
}
