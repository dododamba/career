<?php

namespace App\Form;

use App\Entity\Acte;
use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Poste;
use App\Entity\Structure;
use App\Entity\TypeActe;
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

class ActeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'] ?? null;
        $isAgentPreset = $options['is_agent_preset'] ?? false;
        $currentActeId = $options['current_acte_id'] ?? null;

        $builder
            ->add('type_acte', EntityType::class, [
                'class' => TypeActe::class,
                'choice_label' => function (TypeActe $t) {
                    return sprintf('%s (%s)', $t->getLibelle(), $t->getCode());
                },
                'label' => 'Nature / Type d\'acte *',
                'required' => true,
                'placeholder' => 'Sélectionnez un type d\'acte',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('t')
                        ->where('t.actif = true');
                    if ($institution) {
                        $qb->andWhere('t.institution = :institution OR t.institution IS NULL')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('t.libelle', 'ASC');
                },
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le type d\'acte est obligatoire']),
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('numero', TextType::class, [
                'label' => 'Numéro officiel / Référence de l\'acte *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : N° 2026/042/MESRS/DRH/SA',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le numéro d\'acte est obligatoire']),
                    new Assert\Length(['max' => 100]),
                ],
            ])
            ->add('date_signature', DateType::class, [
                'label' => 'Date de signature *',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de signature est obligatoire']),
                ],
            ])
            ->add('date_effet', DateType::class, [
                'label' => 'Date d\'effet / Prise d\'effet *',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date d\'effet est obligatoire']),
                ],
            ])
            ->add('date_publication', DateType::class, [
                'label' => 'Date de notification / publication',
                'widget' => 'single_text',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut juridique de l\'acte *',
                'required' => true,
                'choices' => [
                    'Signé (en vigueur)' => Acte::STATUT_SIGNE,
                    'Projet (en cours d\'élaboration)' => Acte::STATUT_PROJET,
                    'Notifié / Publié' => Acte::STATUT_NOTIFIE,
                    'Modifié' => Acte::STATUT_MODIFIE,
                    'Abrogé' => Acte::STATUT_ABROGE,
                    'Annulé' => Acte::STATUT_ANNULE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('objet', TextType::class, [
                'label' => 'Objet de l\'acte *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Portant nomination et affectation de M. Jean DUPONT au poste de Chef de Service...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L\'objet de l\'acte est obligatoire']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('signataire_qualite', TextType::class, [
                'label' => 'Qualité de l\'autorité signataire *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Le Ministre de l\'Enseignement Supérieur, Le Recteur, Le Directeur Général...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La qualité du signataire est obligatoire']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('signataire_nom', TextType::class, [
                'label' => 'Nom de l\'autorité signataire',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Prof. Amadou KONATE',
                ],
            ])
            ->add('contenu_resume', TextareaType::class, [
                'label' => 'Résumé analytique, visas ou dispositif',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Vu la Constitution ; Vu la loi n°... ; Article 1er : Il est nommé...',
                ],
            ]);

        // Agent
        if (!$isAgentPreset) {
            $builder->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function (Employe $e) {
                    return sprintf('%s (%s)', $e->getNomComplet(), $e->getMatricule() ?? 'Sans matricule');
                },
                'label' => 'Agent concerné (si acte individuel)',
                'required' => false,
                'placeholder' => '-- Acte collectif / Institutionnel (aucun agent) --',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('e')
                        ->where('e.actif = true');
                    if ($institution) {
                        $qb->andWhere('e.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('e.nom', 'ASC');
                },
                'attr' => ['class' => 'form-select select2'],
            ]);
        }

        // Structure
        $builder->add('structure', EntityType::class, [
            'class' => Structure::class,
            'choice_label' => function (Structure $s) {
                return sprintf('%s (%s)', $s->getNom(), $s->getCode() ?? 'Structure');
            },
            'label' => 'Structure administrative rattachée',
            'required' => false,
            'placeholder' => '-- Aucune structure rattachée --',
            'query_builder' => function (EntityRepository $er) use ($institution) {
                $qb = $er->createQueryBuilder('s')
                    ->where('s.statut = :statut')
                    ->setParameter('statut', Structure::STATUT_ACTIVE);
                if ($institution) {
                    $qb->andWhere('s.institution = :institution')
                       ->setParameter('institution', $institution);
                }
                return $qb->orderBy('s.nom', 'ASC');
            },
            'attr' => ['class' => 'form-select select2'],
        ]);

        // Poste
        $builder->add('poste', EntityType::class, [
            'class' => Poste::class,
            'choice_label' => function (Poste $p) {
                $struct = $p->getStructure()?->getNom() ?? 'Sans structure';
                return sprintf('%s (%s)', $p->getIntitule(), $struct);
            },
            'label' => 'Poste rattaché (si nomination)',
            'required' => false,
            'placeholder' => '-- Aucun poste rattaché --',
            'query_builder' => function (EntityRepository $er) use ($institution) {
                $qb = $er->createQueryBuilder('p')
                    ->where('p.statut != :ferme')
                    ->setParameter('ferme', Poste::STATUT_FERME);
                if ($institution) {
                    $qb->andWhere('p.institution = :institution')
                       ->setParameter('institution', $institution);
                }
                return $qb->orderBy('p.intitule', 'ASC');
            },
            'attr' => ['class' => 'form-select select2'],
        ]);

        // Affectation (mouvement de carrière)
        $builder->add('affectation', EntityType::class, [
            'class' => Affectation::class,
            'choice_label' => function (Affectation $aff) {
                $agent = $aff->getEmploye() ? $aff->getEmploye()->getNomComplet() : 'Inconnu';
                $poste = $aff->getPoste() ? $aff->getPoste()->getIntitule() : 'Poste non spécifié';
                return sprintf('%s - %s (%s, %s)', $agent, $poste, $aff->getTypeMouvementLibelle(), $aff->getDateEffet()?->format('d/m/Y') ?? '-');
            },
            'label' => 'Mouvement de carrière lié (Module 06)',
            'required' => false,
            'placeholder' => '-- Aucun mouvement associé --',
            'query_builder' => function (EntityRepository $er) use ($institution) {
                $qb = $er->createQueryBuilder('aff');
                if ($institution) {
                    $qb->andWhere('aff.institution = :institution')
                       ->setParameter('institution', $institution);
                }
                return $qb->orderBy('aff.date_effet', 'DESC');
            },
            'attr' => ['class' => 'form-select select2'],
        ]);

        // Document numérisé (Module 07)
        $builder->add('document', EntityType::class, [
            'class' => Document::class,
            'choice_label' => function (Document $doc) {
                return sprintf('%s - %s (%s)', $doc->getReference() ?? 'Sans réf.', $doc->getTitre(), $doc->getCategorieLibelle());
            },
            'label' => 'Document numérisé joint (Module 07)',
            'required' => false,
            'placeholder' => '-- Aucun document joint --',
            'query_builder' => function (EntityRepository $er) use ($institution) {
                $qb = $er->createQueryBuilder('d')
                    ->where('d.statut = :statut')
                    ->setParameter('statut', Document::STATUT_VALIDE);
                if ($institution) {
                    $qb->andWhere('d.institution = :institution')
                       ->setParameter('institution', $institution);
                }
                return $qb->orderBy('d.createdAt', 'DESC');
            },
            'attr' => ['class' => 'form-select select2'],
        ]);

        // Acte parent (si modification ou abrogation)
        $builder->add('acte_parent', EntityType::class, [
            'class' => Acte::class,
            'choice_label' => function (Acte $p) {
                return sprintf('%s : %s (%s)', $p->getNumero(), $p->getObjet(), $p->getDateSignature()?->format('d/m/Y') ?? '-');
            },
            'label' => 'Acte antérieur modifié ou abrogé (optionnel)',
            'required' => false,
            'placeholder' => '-- Aucun acte parent --',
            'query_builder' => function (EntityRepository $er) use ($institution, $currentActeId) {
                $qb = $er->createQueryBuilder('a');
                if ($institution) {
                    $qb->andWhere('a.institution = :institution')
                       ->setParameter('institution', $institution);
                }
                if ($currentActeId) {
                    $qb->andWhere('a.id != :curId')
                       ->setParameter('curId', $currentActeId);
                }
                return $qb->orderBy('a.date_signature', 'DESC');
            },
            'attr' => ['class' => 'form-select select2'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Acte::class,
            'institution' => null,
            'is_agent_preset' => false,
            'current_acte_id' => null,
        ]);
    }
}
