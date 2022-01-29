<?php

namespace App\Form;

use App\Entity\Signalement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
                'label' => 'Décrivez le ou les problème(s) rencontré(s)',
                'help' => "Proposer une rapide description du ou des problème(s) en 10 caractères minimum.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('isProprioAverti', ChoiceType::class, [
                'choice_attr' => function ($choice, $key, $value) {
                    $attr['class'] = 'fr-radio';
                    'Oui' === $key ? $attr['data-fr-toggle-show'] = "signalement-methode-contact" : $attr['data-fr-toggle-hide'] = "signalement-methode-contact";
                    return $attr;
                },
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Avez-vous informé le propriétaire ou gestionnaire de ces nuisances ?',
                'help' => 'Le cas échéant merci de précisez la ou les méthodes de contact.',
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('modeContactProprio', ChoiceType::class, [
                'choices' => [
                    'Courrier recommandé' => 'recommandé',
                    'Courriel' => 'courriel',
                    'SMS' => 'sms',
                    'Message téléphonique' => 'message',
                ],
                'expanded' => true,
                'multiple' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'required' => false,
                'label' => 'Comment avez-vous averti le propriétaire ou gestionnaire ?',
            ])
            ->add('nbAdultes', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select'
                ],
                'choices' => [1, 2, 3, 4, '4+'],
                'choice_label' => function ($choice, $key, $value) {
                    if (1 === $choice)
                        return $value . ' Adulte';
                    elseif ('4+' === $choice)
                        return 'Plus de 4 Adultes';
                    return $value . ' Adultes';
                },
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-select-group'
                ], 'label' => "Nombre d'adultes",
                'placeholder' => '--- Selectionnez ---'
            ])
            ->add('nbEnfantsM6', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select'
                ],
                'choices' => [1, 2, 3, 4, '4+'],
                'choice_label' => function ($choice, $key, $value) {
                    if (1 === $choice)
                        return $value . ' Enfant';
                    elseif ('4+' === $choice)
                        return 'Plus de 4 Enfants';
                    return $value . ' Enfants';
                },
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-select-group'
                ], 'label' => "Nombre d'enfants de moins de 6 ans",
                'required' => false,
                'placeholder' => '--- Selectionnez ---'
            ])
            ->add('nbEnfantsP6', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select'
                ],
                'choices' => [1, 2, 3, 4, '4+'],
                'choice_label' => function ($choice, $key, $value) {
                    if (1 === $choice)
                        return $value . ' Enfant';
                    elseif ('4+' === $choice)
                        return 'Plus de 4 Enfants';
                    return $value . ' Enfants';
                },
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-select-group'
                ], 'label' => "Nombre d'enfants de plus de 6 ans",
                'required' => false,
                'placeholder' => '--- Selectionnez ---'
            ])
            ->add('isAllocataire', ChoiceType::class, [
                'choices' => [
                    'CAF' => 'CAF',
                    'MSA' => 'MSA',
                    'Non' => 0
                ],
                'choice_attr' => function ($choice, $key, $value) {
                    $attr['class'] = 'fr-radio';
                    'Non' !== $key ? $attr['data-fr-toggle-show'] = "signalement-num-alloc-bloc" : $attr['data-fr-toggle-hide'] = "signalement-num-alloc-bloc";
                    return $attr;
                },
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Recevez-vous une allocation logement de la CAF ou de la MSA ?',
                'help' => "Le cas échéant, merci de renseigner votre numéro d'allocataire.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('numAllocataire', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-form-group fr-col-2'
                ],
                'label' => "Numéro d'allocataire",
                'help' => "Merci de renseigner votre numéro d'allocataire tel qu'il apparait sur vos documents.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('natureLogement', ChoiceType::class, [
                'choices' => [
                    'Maison' => 'MAISON',
                    'Appartement' => 'APPARTEMENT',
                    'Autre' => 'AUTRE'
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Nature du logement concerné',
                'help' => "Cette information nous permet d'informer le ou les service(s) concerné(s).",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('typeLogement', ChoiceType::class, [
                'attr' => [
                    'class' => 'fr-select'
                ],
                'choices' => [
                    'Chambre' => 'CHAMBRE',
                    'Studio' => 'STUDIO',
                    'T1' => 'T1',
                    'T2' => 'T2',
                    'T3' => 'T3',
                    'T4' => 'T4',
                    'T5' => 'T5',
                    'T6' => 'T6',
                    'Plus' => 'PLUS',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Typologie",
                'placeholder' => '--- Selectionnez ---'
            ])
            ->add('superficie', NumberType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'min' => '1',
                    'step' => '0.01',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Superficie estimée (m²)",
                'html5' => true
            ])
            ->add('loyer', NumberType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'min' => '1',
                    'step' => '0.01',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Loyer mensuel (€)",
                'html5' => true
            ])
            ->add('isBailEnCours', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Le bail est-il en cours ?',
                'help' => "Que la bail soit en cours ou non, merci de renseigner la date d'entrée dans le logement.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('dateEntree', DateType::class, [
                'attr' => [
                    'class' => 'fr-input fr-fi-calendar-line fr-input-wrap'
                ],
                'widget' => 'single_text',
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-form-group fr-col-2'
                ],
                'label' => "Date d'entrée dans le logement",
                'help' => "La date d'entrée dans le logement doit être renseignée au format (JJ/MM/AAAA).",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('isLogementSocial', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Le logement est-il un logement social ?',
                'help' => "Cette information nous aide à optimiser le temps de traitement de votre signalement.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('isPreavisDepart', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Avez-vous déposé un préavis de départ pour ce logement ?',
                'help' => "Cette information nous aide également à optimiser le temps de traitement de votre signalement.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('isRelogement', ChoiceType::class, [
                'choices' => [
                    'Oui' => 1,
                    'Non' => 0
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => 'Avez-vous engagé une démarche de relogement ?',
                'help' => "Cette information nous aide également à optimiser le temps de traitement de votre signalement.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('isRefusIntervention', ChoiceType::class, [
                'choice_attr' => function ($choice, $key, $value) {
                    $attr['class'] = 'fr-radio';
                    'Non' === $key ? $attr['data-fr-toggle-show'] = "signalement-raison-refus" : $attr['data-fr-toggle-hide'] = "signalement-raison-refus";
                    return $attr;
                },
                'choices' => [
                    'Oui' => 0,
                    'Non' => 1
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Si cela est nécessaire, l'occupant accepte la visite d’un personne qualifiée et la mise en place de travaux d'amélioration ?",
                'help' => "Le cas échéant, expliquez rapidement pourquoi vous ne souhaitez pas d'une visite de votre domicile, en 10 caractères minimum",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('raisonRefusIntervention', TextareaType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Précisez la raison de votre refus",
                'help' => "Précisez la raison de votre refus, en 10 caractères minimum",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('nomOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Nom de l'occupant"
            ])
            ->add('prenomOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Prénom de l'occupant"
            ])
            ->add('telOccupant', TelType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "N° téléphone de l'occupant"
            ])
            ->add('mailOccupant', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Courriel de l'occupant"
            ])
            ->add('adresseOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'data-fr-adresse-autocomplete' => "true"
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Adresse du logement",
                'help' => "Commencez à entrer votre adresse et cliquez sur l'une des suggestion. Si vous ne trouvez pas votre adresse entrez-la manuellement",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('cpOccupant', NumberType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'pattern' => "[0-9]{5}"
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Code postal du logement",
                'html5' => true
            ])
            ->add('villeOccupant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Ville du logement"
            ])
            ->add('etageOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Etage",
                'required' => false
            ])
            ->add('escalierOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Escalier",
                'required' => false
            ])
            ->add('numAppartOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "N° d'appartement",
                'required' => false
            ])
            ->add('adresseAutreOccupant', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Autre",
                'required' => false
            ])
            ->add('nomProprio', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label required'
                ],
                'label' => "Nom ou raison sociale du proriétaire",
                'required' => true
            ])
            ->add('adresseProprio', TextType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Adresse du proriétaire",
                'required' => false
            ])
            ->add('telProprio', TelType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "N° de téléphone du proriétaire",
                'required' => false
            ])
            ->add('mailProprio', EmailType::class, [
                'row_attr' => [
                    'class' => 'fr-input-group'
                ],
                'attr' => [
                    'class' => 'fr-input',
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Courriel du proriétaire",
                'required' => false
            ])
            ->add('isNotOccupant', ChoiceType::class, [
                'choice_attr' => function ($choice, $key, $value) {
                    $attr['class'] = 'fr-radio';
                    "Oui" === $key ?
                        $attr = [
                            "data-fr-toggle-hide" => "signalement-pas-occupant|signalement-consentement-tiers-bloc",
                            "data-fr-toggle-show" => "signalement-occupant|signalement-infos-proprio|signalement-consentement-tiers-bloc",
                            "data-fr-toggle-unrequire" => "signalement-consentement-tiers|signalement_adresseProprio|signalement_telProprio|signalement_mailProprio|signalement_etageOccupant|signalement_escalierOccupant|signalement_numAppartOccupant|signalement_adresseAutreOccupant|signalement_lienDeclarantOccupant_0",
                            "data-fr-toggle-require" => "signalement_mailOccupant|signalement_telOccupant",
                        ]
                        :
                        $attr = [
                            "data-fr-toggle-show" => "signalement-consentement-tiers-bloc|signalement-occupant|signalement-pas-occupant|signalement-infos-proprio",
                            "data-fr-toggle-unrequire" => "signalement_adresseProprio|signalement_telProprio|signalement_mailProprio|signalement_nomProprio|signalement_structureDeclarant|signalement_mailOccupant|signalement_telOccupant|signalement_etageOccupant|signalement_escalierOccupant|signalement_numAppartOccupant|signalement_adresseAutreOccupant",
                        ];
                    return $attr;
                },
                'choices' => [
                    'Oui' => 0,
                    'Non' => 1
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Êtes-vous l'occupant du logement ?",
                'help' => "Si vous déposez ce signalement pour le compte de quelqu'un d'autres, merci de nous le faire savoir.",
                'help_attr' => [
                    'class' => 'fr-hint-text'
                ]
            ])
            ->add('nomDeclarant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Nom déclarant"
            ])
            ->add('prenomDeclarant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Prénom déclarant"
            ])
            ->add('telDeclarant', TelType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "N° de téléphone déclarant"
            ])
            ->add('mailDeclarant', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input fr-fi-mail-line fr-input-wrap'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Courriel déclarant"
            ])
            ->add('lienDeclarantOccupant', ChoiceType::class, [
                'choices' => [
                    'Proche' => "PROCHE",
                    'Professionnel' => "PROFESSIONNEL",
                    'Tuteur / Tutrice' => "TUTEUR",
                    'Voisin / Voisine' => "VOISIN",
                    'Autre' => "AUTRE",
                ],
                'expanded' => true,
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'label' => "Lien avec l'occupant",
            ])
            ->add('structureDeclarant', TextType::class, [
                'attr' => [
                    'class' => 'fr-input'
                ],
                'label_attr' => [
                    'class' => 'fr-label'
                ],
                'row_attr' => [
                    'class' => 'fr-form-group'
                ],
                'label' => 'Structure déclarant',
                'required' => false
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Signalement::class,
            'allow_file_upload' => true,
            'allow_extra_fields' => true,
            'attr' => [
                'class' => 'needs-validation',
                'novalidate' => true
            ],
        ]);
    }
}
