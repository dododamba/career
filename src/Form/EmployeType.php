<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\Service;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\EntityRepository;

class EmployeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Récupération de l'institution depuis les options
        $institution = $options['institution'] ?? null;

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le nom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisissez le nom'
                ]
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire']),
                    new Assert\Length([
                        'min' => 2,
                        'max' => 255,
                        'minMessage' => 'Le prénom doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Saisissez le prénom'
                ]
            ])
            ->add('date_naissance', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')
                ]
            ])
            ->add('lieu_naissance', TextType::class, [
                'label' => 'Lieu de naissance',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ville ou localité de naissance'
                ]
            ])
            ->add('sexe', ChoiceType::class, [
                'label' => 'Sexe',
                'required' => false,
                'placeholder' => 'Sélectionnez le sexe',
                'choices' => [
                    'Masculin' => 'M',
                    'Féminin' => 'F',
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('nationalite', TextType::class, [
                'label' => 'Nationalité',
                'required' => false,
                'data' => 'Tchadienne',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nationalité'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'email est obligatoire']),
                    new Assert\Email(['message' => 'Veuillez saisir un email valide'])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'exemple@email.com'
                ]
            ])
            ->add('numero_telephone', TextType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[\+]?[0-9\s\-\(\)]{8,20}$/',
                        'message' => 'Veuillez saisir un numéro de téléphone valide'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '+235 XX XX XX XX'
                ]
            ])
            ->add('adresse', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Adresse complète'
                ]
            ])
            ->add('matricule', TextType::class, [
                'label' => 'Matricule',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le matricule est obligatoire']),
                    new Assert\Length([
                        'min' => 3,
                        'max' => 50,
                        'minMessage' => 'Le matricule doit contenir au moins {{ limit }} caractères',
                        'maxMessage' => 'Le matricule ne peut pas dépasser {{ limit }} caractères'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'EMP001'
                ]
            ])
            ->add('numero', IntegerType::class, [
                'label' => 'Numéro employé',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '001',
                    'min' => 1
                ]
            ])
            ->add('structure', EntityType::class, [
                'class' => \App\Entity\Structure::class,
                'choice_label' => 'nom',
                'label' => 'Structure de rattachement',
                'required' => false,
                'placeholder' => 'Sélectionnez une structure',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('st');
                    if ($institution) {
                        $qb->where('st.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('st.nom', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'nom',
                'label' => 'Service',
                'required' => false,
                'placeholder' => 'Sélectionnez un service',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('s');
                    if ($institution) {
                        $qb->where('s.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('s.nom', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
                'label' => 'Fonction',
                'required' => true,
                'placeholder' => 'Sélectionnez une fonction',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La fonction est obligatoire'])
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('statut_administratif', ChoiceType::class, [
                'label' => 'Statut administratif',
                'required' => false,
                'choices' => [
                    'Contractuel' => Employe::STATUT_ADMIN_CONTRACTUEL,
                    'Fonctionnaire' => Employe::STATUT_ADMIN_FONCTIONNAIRE,
                    'Stagiaire' => Employe::STATUT_ADMIN_STAGIAIRE,
                    'Détaché' => Employe::STATUT_ADMIN_DETACHE,
                    'Autre' => Employe::STATUT_ADMIN_AUTRE,
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('situation_administrative', ChoiceType::class, [
                'label' => 'Situation administrative',
                'required' => false,
                'choices' => [
                    'En activité' => Employe::SITUATION_EN_ACTIVITE,
                    'Détachement' => Employe::SITUATION_DETACHEMENT,
                    'Disponibilité' => Employe::SITUATION_DISPONIBILITE,
                    'Suspension' => Employe::SITUATION_SUSPENSION,
                    'Retraite' => Employe::SITUATION_RETRAITE,
                    'Radié' => Employe::SITUATION_RADIE,
                ],
                'attr' => [
                    'class' => 'form-select'
                ]
            ])
            ->add('corps', TextType::class, [
                'label' => 'Corps',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Administration générale'
                ]
            ])
            ->add('grade', TextType::class, [
                'label' => 'Grade',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Principal, 1ère classe'
                ]
            ])
            ->add('echelon', TextType::class, [
                'label' => 'Échelon',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 1, 2, 3...'
                ]
            ])
            ->add('categorie', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: A1, A2, B, C'
                ]
            ])
            ->add('regime', TextType::class, [
                'label' => 'Régime',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Général, Spécial'
                ]
            ])
            ->add('date_embauche', DateType::class, [
                'label' => 'Date d\'embauche',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'embauche est obligatoire'])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'max' => (new \DateTime())->format('Y-m-d')
                ]
            ])
            ->add('date_prise_service', DateType::class, [
                'label' => 'Date de prise de service',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('salaire', NumberType::class, [
                'label' => 'Salaire',
                'required' => true,
                'scale' => 2,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le salaire est obligatoire']),
                    new Assert\PositiveOrZero(['message' => 'Le salaire doit être positif'])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => '0.00',
                    'step' => '0.01',
                    'min' => '0'
                ]
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut d\'activité',
                'required' => true,
                'choices' => [
                    'Actif' => true,
                    'Inactif' => false
                ],
                'data' => true,
                'attr' => [
                    'class' => 'form-select'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employe::class,
            'institution' => null, // Option pour passer l'institution
        ]);

        $resolver->setAllowedTypes('institution', ['null', 'App\Entity\Institution']);
    }
}