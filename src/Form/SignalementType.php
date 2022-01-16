<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SignalementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('details', null, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-form-group fr-col-12'
                ]
            ])
            ->add($builder->create('signalement', FormType::class, [
                'inherit_data' => true,
                /*'row_attr'=> ['class'=> 'fr-col-12 fr-background-alt--grey fr-rounded'],*/
                'attr' => ['class' => 'fr-grid-row fr-grid-row--gutters'],
                'label'=> false
            ])
                ->add('nbAdultes', NumberType::class, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'html5' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('nbEnfantsM6', NumberType::class, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'html5' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('nbEnfantsP6', NumberType::class, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'html5' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('natureLogement', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('typeLogement', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('superficie', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('loyer', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('dateEntree', DateType::class, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'widget' => 'single_text',
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ]))
            ->add($builder->create('occupant', FormType::class, [
                'inherit_data' => true,
                /*'row_attr'=> ['class'=> 'fr-col-12 fr-background-alt--grey fr-rounded'],*/
                'attr' => ['class' => 'fr-grid-row fr-grid-row--gutters'],
                'label'=> false
            ])
                ->add('nomOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('prenomOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('telOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('mailOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('adresseOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('cpOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('villeOccupant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ]
                ])
                ->add('isAllocataire', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ], 'label' => 'Allocataire MSA/CAF'
                ])
                ->add('numAllocataire', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('montantAllocation', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ]))
            ->add($builder->create('proprietaire', FormType::class, [
                'inherit_data' => true,
                /*'row_attr'=> ['class'=> 'fr-col-12 fr-background-alt--grey fr-rounded'],*/
                'attr' => ['class' => 'fr-grid-row fr-grid-row--gutters'],'label'=> false
            ])
                ->add('nomProprio', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('adresseProprio', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('telProprio', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('mailProprio', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ]))
            ->add($builder->create('logement', FormType::class, [
                'inherit_data' => true,
                /*'row_attr'=> ['class'=> 'fr-col-12 fr-background-alt--grey fr-rounded'],*/
                'attr' => ['class' => 'fr-grid-row fr-grid-row--gutters'],
                'label'=> false
            ])
                ->add('isProprioAverti', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Propriétaire averti'
                ])
                ->add('isBailEnCours', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Bail en cours'
                ])
                ->add('isLogementSocial', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Logement social'
                ])
                ->add('isPreavisDepart', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Préavis de départ'
                ])
                ->add('isRelogement', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Procédure relogement'
                ])
                ->add('isRefusIntervention', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => "Refus d'intervention"
                ])
                ->add('raisonRefusIntervention', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-12'
                    ],
                    'required'=>false
                ])
                ->add('dateVisite', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('isOccupantPresentVisite', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Occupant présent visite',
                    'required'=>false
                ])
                ->add('isSituationHandicap', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Situation de Handicap'
                ])
                ->add('codeProcedure', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ]))
            ->add($builder->create('declarant', FormType::class, [
                'inherit_data' => true,
                /*'row_attr'=> ['class'=> 'fr-col-12 fr-background-alt--grey fr-rounded'],*/
                'attr' => ['class' => 'fr-grid-row fr-grid-row--gutters'],
                'label'=> false
            ])
                ->add('isNotOccupant', ChoiceType::class, [
                    'row_attr' => [
                        'class' => 'fr-radio-group fr-col-2'
                    ],
                    'choices' => [
                        'Oui' => 1,
                        'Non' => 0
                    ],
                    'expanded' => true,
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'label' => 'Déclaration par tiers'
                ])
                ->add('nomDeclarant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('prenomDeclarant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('telDeclarant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('mailDeclarant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ])
                ->add('structureDeclarant', null, [
                    'attr' => [
                        'class' => 'fr-input'
                    ],
                    'label_attr' => [
                        'class' => 'fr-label'
                    ],
                    'row_attr' => [
                        'class' => 'fr-form-group fr-col-2'
                    ],
                    'required'=>false
                ]));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
            'attr' => [
                'class' => 'needs-validation',
                'novalidate'=> true
            ],
        ]);
    }
}
