<?php

namespace App\Form;

use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'] ?? null;
        $isAgentPreset = $options['is_agent_preset'] ?? false;
        $isEdit = $options['is_edit'] ?? false;

        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre ou désignation du document *',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : Contrat de travail initial, Arrêté de titularisation...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le titre est obligatoire.']),
                    new Assert\Length(['max' => 255]),
                ],
            ])
            ->add('categorie', ChoiceType::class, [
                'label' => 'Catégorie de pièce RH *',
                'required' => true,
                'choices' => [
                    'Pièce d\'identité & État civil' => Document::CATEGORIE_IDENTITE,
                    'Contrat & Engagement' => Document::CATEGORIE_CONTRAT,
                    'Diplôme, Certification & CV' => Document::CATEGORIE_DIPLOME_CV,
                    'Acte administratif (Arrêté, Décret)' => Document::CATEGORIE_ACTE_ADMIN,
                    'Mouvement & Affectation (PV, Note)' => Document::CATEGORIE_AFFECTATION,
                    'Évaluation & Notation' => Document::CATEGORIE_EVALUATION,
                    'Dossier Médical & Aptitude' => Document::CATEGORIE_MEDICAL,
                    'Dossier Disciplinaire' => Document::CATEGORIE_DISCIPLINE,
                    'Formation continue' => Document::CATEGORIE_FORMATION,
                    'Autre pièce justificative' => Document::CATEGORIE_AUTRE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('niveau_confidentialite', ChoiceType::class, [
                'label' => 'Niveau de confidentialité *',
                'required' => true,
                'choices' => [
                    'Confidentiel (Agent & RH)' => Document::CONFIDENTIALITE_CONFIDENTIEL,
                    'Strictement Restreint (Direction RH)' => Document::CONFIDENTIALITE_SECRET_RH,
                    'Interne RH (Tout le service RH)' => Document::CONFIDENTIALITE_PUBLIC_INTERNE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('reference', TextType::class, [
                'label' => 'Numéro / Référence de la pièce',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex : DOC-2026/012, CNI-849204...',
                ],
            ])
            ->add('date_document', DateType::class, [
                'label' => 'Date du document / Date d\'émission',
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => ['class' => 'form-control'],
            ]);

        if (!$isAgentPreset) {
            $builder->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function (Employe $e) {
                    return sprintf('%s (%s) - %s', $e->getNomComplet(), $e->getMatricule() ?? 'Sans matricule', $e->getStructure()?->getNom() ?? 'Sans structure');
                },
                'label' => 'Agent concerné (optionnel)',
                'required' => false,
                'placeholder' => '-- Document institutionnel ou général --',
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

        $builder
            ->add('affectation', EntityType::class, [
                'class' => Affectation::class,
                'choice_label' => function (Affectation $aff) {
                    $agent = $aff->getEmploye() ? $aff->getEmploye()->getNomComplet() : 'Sans agent';
                    return sprintf('%s - %s (%s)', $aff->getReference(), $aff->getTypeMouvementLabel(), $agent);
                },
                'label' => 'Mouvement / Affectation lié(e) (optionnel)',
                'required' => false,
                'placeholder' => '-- Aucun mouvement lié --',
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('a');
                    if ($institution) {
                        $qb->andWhere('a.institution = :institution')
                           ->setParameter('institution', $institution);
                    }
                    return $qb->orderBy('a.date_effet', 'DESC');
                },
                'attr' => ['class' => 'form-select'],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut du document *',
                'required' => true,
                'choices' => [
                    'Validé / Conforme' => Document::STATUT_VALIDE,
                    'En attente de vérification' => Document::STATUT_EN_ATTENTE,
                    'Archivé' => Document::STATUT_ARCHIVE,
                    'Rejeté / Non conforme' => Document::STATUT_REJETE,
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Observations ou description du contenu',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Notes RH, contexte de délivrance ou remarques...',
                ],
            ]);

        if (!$isEdit) {
            $builder->add('fichier', FileType::class, [
                'label' => 'Fichier numérique à téléverser *',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un fichier à téléverser.']),
                    new Assert\File([
                        'maxSize' => '25M',
                        'maxSizeMessage' => 'Le fichier dépasse la taille maximale de 25 Mo.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.csv',
                ],
                'help' => 'Formats acceptés : PDF, Images (PNG, JPG), Word (DOC, DOCX), Excel (XLS, XLSX), Texte. Taille maximale : 25 Mo.',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'institution' => null,
            'is_agent_preset' => false,
            'is_edit' => false,
        ]);

        $resolver->setAllowedTypes('institution', ['null', Institution::class]);
        $resolver->setAllowedTypes('is_agent_preset', ['bool']);
        $resolver->setAllowedTypes('is_edit', ['bool']);
    }
}
