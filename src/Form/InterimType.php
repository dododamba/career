<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Interim;
use App\Entity\Poste;
use App\Entity\Structure;
use App\Repository\EmployeRepository;
use App\Repository\PosteRepository;
use App\Repository\StructureRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class InterimType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];

        $builder
            ->add('titulaire', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => fn (Employe $e) => sprintf('%s - %s (%s)', $e->getMatricule() ?? 'N/A', $e->getNomComplet(), $e->getFonction()?->getLibelle() ?? 'Agent'),
                'query_builder' => function (EmployeRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('e')
                        ->where('e.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('e.nom', 'ASC')
                        ->addOrderBy('e.prenom', 'ASC');

                    if ($institution) {
                        $qb->andWhere('e.institution = :institution')
                            ->setParameter('institution', $institution);
                    }

                    return $qb;
                },
                'placeholder' => '-- Sélectionner le titulaire remplacé --',
                'label' => 'Titulaire du poste',
                'constraints' => [new NotBlank(['message' => 'Le titulaire remplacé est obligatoire.'])],
                'attr' => ['class' => 'form-select select2-enable'],
            ])
            ->add('interimaire', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => fn (Employe $e) => sprintf('%s - %s (%s)', $e->getMatricule() ?? 'N/A', $e->getNomComplet(), $e->getFonction()?->getLibelle() ?? 'Agent'),
                'query_builder' => function (EmployeRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('e')
                        ->where('e.actif = :actif')
                        ->setParameter('actif', true)
                        ->orderBy('e.nom', 'ASC')
                        ->addOrderBy('e.prenom', 'ASC');

                    if ($institution) {
                        $qb->andWhere('e.institution = :institution')
                            ->setParameter('institution', $institution);
                    }

                    return $qb;
                },
                'placeholder' => '-- Sélectionner l\'agent désigné pour l\'intérim --',
                'label' => 'Intérimaire désigné',
                'constraints' => [new NotBlank(['message' => 'L\'intérimaire est obligatoire.'])],
                'attr' => ['class' => 'form-select select2-enable'],
            ])
            ->add('typeDelegation', ChoiceType::class, [
                'choices' => array_flip(Interim::TYPES_DELEGATION),
                'label' => 'Type de délégation',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('poste', EntityType::class, [
                'class' => Poste::class,
                'choice_label' => fn (Poste $p) => sprintf('%s (%s)', $p->getTitre(), $p->getStructure()?->getNom() ?? 'Institution'),
                'query_builder' => function (PosteRepository $pr) use ($institution) {
                    $qb = $pr->createQueryBuilder('p')
                        ->where('p.statut != :ferme')
                        ->setParameter('ferme', Poste::STATUT_FERME)
                        ->orderBy('p.intitule', 'ASC');

                    if ($institution) {
                        $qb->andWhere('p.institution = :institution')
                            ->setParameter('institution', $institution);
                    }

                    return $qb;
                },
                'required' => false,
                'placeholder' => '-- Déduire automatiquement du titulaire --',
                'label' => 'Poste faisant l\'objet de l\'intérim (optionnel)',
                'attr' => ['class' => 'form-select select2-enable'],
            ])
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'choice_label' => 'nom',
                'query_builder' => function (StructureRepository $sr) use ($institution) {
                    $qb = $sr->createQueryBuilder('s')
                        ->orderBy('s.nom', 'ASC');

                    if ($institution) {
                        $qb->andWhere('s.institution = :institution')
                            ->setParameter('institution', $institution);
                    }

                    return $qb;
                },
                'required' => false,
                'placeholder' => '-- Déduire automatiquement du poste/titulaire --',
                'label' => 'Structure de rattachement (optionnel)',
                'attr' => ['class' => 'form-select select2-enable'],
            ])
            ->add('perimetre', ChoiceType::class, [
                'choices' => array_flip(Interim::POUVOIRS_LABELS),
                'multiple' => true,
                'expanded' => true,
                'label' => 'Périmètre des pouvoirs délégués',
                'help' => 'Sélectionnez les prérogatives accordées à l\'intérimaire pendant la période.',
                'required' => false,
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de début de l\'intérim',
                'constraints' => [new NotBlank(['message' => 'La date de début est obligatoire.'])],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de fin de l\'intérim',
                'constraints' => [new NotBlank(['message' => 'La date de fin est obligatoire.'])],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('motif', TextareaType::class, [
                'label' => 'Motif et justification de l\'intérim',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Ex : Départ en congé annuel du titulaire, mission officielle à l\'étranger, arrêt temporaire...',
                ],
                'constraints' => [new NotBlank(['message' => 'Le motif de l\'intérim est obligatoire.'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Interim::class,
            'institution' => null,
        ]);

        $resolver->setAllowedTypes('institution', ['null', Institution::class]);
    }
}
