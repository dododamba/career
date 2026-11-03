<?php

namespace App\Form;

use App\Entity\Fonction;
use App\Entity\Poste;
use App\Entity\Service;
use App\Entity\Structure;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PosteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'] ?? null;

        $builder
            ->add('reference', TextType::class, [
                'label' => 'Référence du poste *',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La référence est obligatoire']),
                    new Assert\Length(['max' => 50]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: PST-DIR-001',
                ],
            ])
            ->add('intitule', TextType::class, [
                'label' => 'Intitulé du poste *',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'intitulé est obligatoire']),
                    new Assert\Length(['max' => 255]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Chef de division des ressources humaines',
                ],
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau de responsabilité',
                'required' => false,
                'choices' => [
                    'Direction & Haute Responsabilité' => Poste::NIVEAU_DIRECTION,
                    'Cadre Supérieur' => Poste::NIVEAU_CADRE_SUPERIEUR,
                    'Cadre' => Poste::NIVEAU_CADRE,
                    'Agent de Maîtrise / Technicien' => Poste::NIVEAU_MAITRISE,
                    'Agent d\'Exécution' => Poste::NIVEAU_EXECUTION,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut du poste *',
                'required' => true,
                'choices' => [
                    'Vacant (Disponible)' => Poste::STATUT_VACANT,
                    'Occupé' => Poste::STATUT_OCCUPE,
                    'Suspendu (Gelé)' => Poste::STATUT_SUSPENDU,
                    'Fermé / Supprimé' => Poste::STATUT_FERME,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
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
                    'class' => 'form-select',
                ],
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'nom',
                'label' => 'Service de rattachement',
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
                    'class' => 'form-select',
                ],
            ])
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
                'label' => 'Fonction / Métier associé',
                'required' => false,
                'placeholder' => 'Sélectionnez une fonction',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description du poste & Missions principales',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Objectifs du poste, responsabilités clés, activités attendues...',
                ],
            ])
            ->add('competences_requises', TextareaType::class, [
                'label' => 'Exigences & Compétences requises',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Niveau d\'études, diplômes requis, compétences techniques et comportementales...',
                ],
            ])
            ->add('date_creation', DateType::class, [
                'label' => 'Date de création du poste *',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('date_fermeture', DateType::class, [
                'label' => 'Date de fermeture éventuelle',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Poste::class,
            'institution' => null,
        ]);

        $resolver->setAllowedTypes('institution', ['null', 'App\Entity\Institution']);
    }
}
