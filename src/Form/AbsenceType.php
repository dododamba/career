<?php

namespace App\Form;

use App\Entity\Absence;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\TypeAbsence;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AbsenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];

        $builder
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('e')
                        ->orderBy('e.nom', 'ASC')
                        ->addOrderBy('e.prenom', 'ASC');

                    if ($institution instanceof Institution) {
                        $qb->andWhere('e.institution = :inst')
                            ->setParameter('inst', $institution);
                    }

                    return $qb;
                },
                'choice_label' => function (Employe $employe) {
                    return $employe->getNomComplet() . ($employe->getMatricule() ? ' (' . $employe->getMatricule() . ')' : '');
                },
                'placeholder' => 'Sélectionner un employé',
                'attr' => [
                    'class' => 'form-select select2',
                ],
                'label' => 'Employé',
            ])
            ->add('typeAbsence', EntityType::class, [
                'class' => TypeAbsence::class,
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('t')
                        ->where('t.actif = :active')
                        ->setParameter('active', true)
                        ->orderBy('t.libelle', 'ASC');

                    if ($institution instanceof Institution) {
                        $qb->andWhere('t.institution = :inst OR t.institution IS NULL')
                            ->setParameter('inst', $institution);
                    }

                    return $qb;
                },
                'choice_label' => function (TypeAbsence $type) {
                    $extra = [];
                    if ($type->isJustificatifRequis()) {
                        $extra[] = 'justificatif requis';
                    }
                    if (!$type->isRemuneree()) {
                        $extra[] = 'non rémunérée';
                    }
                    if ($type->getDureeMax()) {
                        $extra[] = sprintf('max %s %s', $type->getDureeMax(), strtolower($type->getUnite()));
                    }

                    return $type->getLibelle() . (!empty($extra) ? ' (' . implode(', ', $extra) . ')' : '');
                },
                'placeholder' => 'Sélectionner un type d\'absence',
                'attr' => [
                    'class' => 'form-select',
                ],
                'label' => 'Type d\'absence',
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Date de début',
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Date de fin',
            ])
            ->add('heureDebut', TimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Heure de début (optionnel)',
            ])
            ->add('heureFin', TimeType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Heure de fin (optionnel)',
            ])
            ->add('unite', ChoiceType::class, [
                'choices' => [
                    'Jours' => TypeAbsence::UNITE_JOURS,
                    'Heures' => TypeAbsence::UNITE_HEURES,
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
                'label' => 'Unité',
            ])
            ->add('duree', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Calculé automatiquement si vide',
                    'step' => '0.5',
                ],
                'label' => 'Durée (calculée automatiquement si vide)',
            ])
            ->add('motif', TextareaType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Précisez le motif de l\'autorisation d\'absence...',
                ],
                'label' => 'Motif détaillé',
            ])
            ->add('document', EntityType::class, [
                'class' => Document::class,
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('d')
                        ->orderBy('d.titre', 'ASC');

                    if ($institution instanceof Institution) {
                        $qb->andWhere('d.institution = :inst')
                            ->setParameter('inst', $institution);
                    }

                    return $qb;
                },
                'choice_label' => function (Document $doc) {
                    return $doc->getTitre() . ($doc->getCategorie() ? ' [' . $doc->getCategorie() . ']' : '');
                },
                'placeholder' => 'Aucun justificatif sélectionné',
                'attr' => [
                    'class' => 'form-select select2',
                ],
                'label' => 'Pièce justificative (Document RH)',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Absence::class,
            'institution' => null,
        ]);

        $resolver->setAllowedTypes('institution', [Institution::class, 'null']);
    }
}
