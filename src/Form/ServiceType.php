<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Service;
use App\Entity\Structure;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ServiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];

        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du service',
                'attr' => ['placeholder' => 'Ex: Service Recrutement & Carrière'],
            ])
            ->add('code', TextType::class, [
                'label' => 'Code / Sigle',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: SRC'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description / Attributions',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Actif' => Service::STATUT_ACTIVE,
                    'Inactif' => Service::STATUT_INACTIVE,
                    'Archivé' => Service::STATUT_ARCHIVED,
                ],
            ])
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'label' => 'Structure de rattachement',
                'required' => false,
                'placeholder' => '— Aucune (Rattaché directement à l\'institution) —',
                'choice_label' => function (Structure $s) {
                    $prefix = $s->getCode() ? '[' . $s->getCode() . '] ' : '';
                    return $prefix . $s->getNom();
                },
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('s')
                        ->orderBy('s.nom', 'ASC');

                    if ($institution !== null) {
                        $qb->andWhere('s.institution = :inst')
                           ->setParameter('inst', $institution);
                    }

                    return $qb;
                },
            ])
            ->add('responsable', EntityType::class, [
                'class' => Employe::class,
                'label' => 'Chef de service / Responsable',
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
            'data_class' => Service::class,
            'institution' => null,
            'include_institution' => false,
        ]);
    }
}
