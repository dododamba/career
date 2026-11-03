<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Structure;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StructureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];
        $currentStructureId = $options['current_structure_id'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la structure',
                'attr' => ['placeholder' => 'Ex: Direction des Ressources Humaines'],
            ])
            ->add('code', TextType::class, [
                'label' => 'Code / Sigle',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: DRH'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de structure',
                'choices' => [
                    'Direction Générale' => Structure::TYPE_DIRECTION_GENERALE,
                    'Direction' => Structure::TYPE_DIRECTION,
                    'Département' => Structure::TYPE_DEPARTEMENT,
                    'Division' => Structure::TYPE_DIVISION,
                    'Service rattaché' => Structure::TYPE_SERVICE,
                    'Autre structure' => Structure::TYPE_AUTRE,
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Attributions',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => Structure::STATUT_ACTIVE,
                    'Inactif' => Structure::STATUT_INACTIVE,
                    'Archivé' => Structure::STATUT_ARCHIVED,
                ],
            ])
            ->add('parent', EntityType::class, [
                'class' => Structure::class,
                'label' => 'Structure parente (Rattachement)',
                'required' => false,
                'placeholder' => '— Aucune (Structure de premier niveau) —',
                'choice_label' => function (Structure $s) {
                    $prefix = $s->getCode() ? '[' . $s->getCode() . '] ' : '';
                    return $prefix . $s->getNom();
                },
                'query_builder' => function (EntityRepository $er) use ($institution, $currentStructureId) {
                    $qb = $er->createQueryBuilder('s')
                        ->orderBy('s.nom', 'ASC');

                    if ($institution !== null) {
                        $qb->andWhere('s.institution = :inst')
                           ->setParameter('inst', $institution);
                    }

                    if ($currentStructureId !== null) {
                        $qb->andWhere('s.id != :currentId')
                           ->setParameter('currentId', $currentStructureId);
                    }

                    return $qb;
                },
            ])
            ->add('responsable', EntityType::class, [
                'class' => Employe::class,
                'label' => 'Responsable / Directeur',
                'required' => false,
                'placeholder' => '— Choisir un responsable —',
                'choice_label' => function (Employe $e) {
                    $mat = $e->getMatricule() ? ' (' . $e->getMatricule() . ')' : '';
                    return trim(($e->getNom() ?? '') . ' ' . ($e->getPrenom() ?? '')) . $mat;
                },
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('e')
                        ->orderBy('e.nom', 'ASC');

                    if ($institution !== null) {
                        $qb->andWhere('e.institution = :inst')
                           ->setParameter('inst', $institution);
                    }

                    return $qb;
                },
            ]);

        if ($options['include_institution']) {
            $builder->add('institution', EntityType::class, [
                'class' => Institution::class,
                'choice_label' => 'nom',
                'label' => 'Institution',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Structure::class,
            'institution' => null,
            'current_structure_id' => null,
            'include_institution' => false,
        ]);
    }
}
