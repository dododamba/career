<?php

namespace App\Form;

use App\Entity\Affectation;
use App\Entity\Employe;
use App\Entity\Fonction;
use App\Entity\Poste;
use App\Entity\Service;
use App\Entity\Structure;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'] ?? null;
        $isAgentPreset = $options['is_agent_preset'] ?? false;

        if (!$isAgentPreset) {
            $builder->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function (Employe $employe) {
                    return sprintf('%s (%s) - %s', $employe->getNomComplet(), $employe->getMatricule() ?? 'Sans matricule', $employe->getPoste()?->getIntitule() ?? 'Sans poste');
                },
                'label' => 'Agent concerné *',
                'required' => true,
                'placeholder' => 'Sélectionnez un agent',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('e')
                        ->where('e.actif = true');
                    if ($institution) {
                        $qb->andWhere('e.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('e.nom', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'agent est obligatoire']),
                ],
                'attr' => ['class' => 'form-select select2'],
            ]);
        }

        $builder
            ->add('type_mouvement', ChoiceType::class, [
                'label' => 'Type de mouvement / Affectation *',
                'required' => true,
                'choices' => [
                    'Affectation initiale' => Affectation::TYPE_AFFECTATION_INITIALE,
                    'Mutation' => Affectation::TYPE_MUTATION,
                    'Promotion' => Affectation::TYPE_PROMOTION,
                    'Changement de poste' => Affectation::TYPE_CHANGEMENT_POSTE,
                    'Détachement' => Affectation::TYPE_DETACHEMENT,
                    'Réintégration' => Affectation::TYPE_REINTEGRATION,
                    'Cessation d\'activité' => Affectation::TYPE_CESSATION,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('poste', EntityType::class, [
                'class' => Poste::class,
                'choice_label' => function (Poste $p) {
                    $struct = $p->getStructure()?->getNom() ?? 'Sans structure';
                    $occ = $p->getOccupantActuel() ? ' (Occupé: ' . $p->getOccupantActuel()->getNomComplet() . ')' : ' [Vacant]';
                    return sprintf('%s - %s %s', $p->getIntitule(), $struct, $occ);
                },
                'label' => 'Poste cible (optionnel)',
                'required' => false,
                'placeholder' => '-- Aucun / Choix manuel ci-dessous --',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('p')
                        ->where('p.statut NOT IN (:fermes)')
                        ->setParameter('fermes', [Poste::STATUT_FERME, Poste::STATUT_SUSPENDU]);
                    if ($institution) {
                        $qb->andWhere('p.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('p.intitule', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
                'help' => 'Si vous sélectionnez un poste, la structure et le service seront pré-remplis automatiquement.',
            ])
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'choice_label' => 'nom',
                'label' => 'Structure de rattachement',
                'required' => false,
                'placeholder' => '-- Sélectionner une structure --',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('s')
                        ->where('s.actif = true');
                    if ($institution) {
                        $qb->andWhere('s.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('s.nom', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('service', EntityType::class, [
                'class' => Service::class,
                'choice_label' => 'nom',
                'label' => 'Service de rattachement',
                'required' => false,
                'placeholder' => '-- Sélectionner un service --',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('srv');
                    if ($institution) {
                        $qb->andWhere('srv.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('srv.nom', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('fonction', EntityType::class, [
                'class' => Fonction::class,
                'choice_label' => 'libelle',
                'label' => 'Fonction exercée',
                'required' => false,
                'placeholder' => '-- Sélectionner une fonction --',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('f');
                    if ($institution) {
                        $qb->andWhere('f.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('f.libelle', 'ASC');
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('date_decision', DateType::class, [
                'label' => 'Date de décision administrative *',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'data' => new \DateTime(),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date_effet', DateType::class, [
                'label' => 'Date d\'effet du mouvement *',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'data' => new \DateTime(),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('date_prise_service', DateType::class, [
                'label' => 'Date effective de prise de service',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
                'help' => 'Laisser vide si la prise de service fera l\'objet d\'une confirmation ultérieure.',
            ])
            ->add('reference_acte', TextType::class, [
                'label' => 'Référence de l\'acte administratif',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Arrêté n° 2026/045/CAB/RH',
                ],
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif du mouvement',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Raison du mouvement, besoins de service, restructuration...',
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Observations & remarques RH',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                ],
            ])
            ->add('activer_immediatement', CheckboxType::class, [
                'label' => 'Activer immédiatement le mouvement et mettre à jour le poste / l\'agent',
                'mapped' => false,
                'required' => false,
                'data' => true,
                'help' => 'Si coché, l\'affectation devient active dès aujourd\'hui et synchronise immédiatement l\'agent et le poste.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Affectation::class,
            'institution' => null,
            'is_agent_preset' => false,
        ]);

        $resolver->setAllowedTypes('institution', ['null', \App\Entity\Institution::class]);
        $resolver->setAllowedTypes('is_agent_preset', ['bool']);
    }
}
