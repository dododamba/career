<?php

namespace App\Form;

use App\Entity\Conges;
use App\Entity\Document;
use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\TypeConges;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CongesType extends AbstractType
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
                    'class' => 'form-control select2'
                ],
                'label' => 'Employé'
            ])
            ->add('typeConge', EntityType::class, [
                'class' => TypeConges::class,
                'query_builder' => function (EntityRepository $er) use ($institution) {
                    $qb = $er->createQueryBuilder('t')
                        ->where('t.isActive = :active')
                        ->setParameter('active', true)
                        ->orderBy('t.libelle', 'ASC');

                    if ($institution instanceof Institution) {
                        $qb->andWhere('t.institution = :inst OR t.institution IS NULL')
                            ->setParameter('inst', $institution);
                    }

                    return $qb;
                },
                'choice_label' => function (TypeConges $type) {
                    return $type->getLibelle() . ($type->isJustificatifRequis() ? ' * (justificatif requis)' : '');
                },
                'placeholder' => 'Sélectionner un type de congé',
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Type de congé'
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Date de début'
                ],
                'label' => 'Date de début'
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Date de fin'
                ],
                'label' => 'Date de fin'
            ])
            ->add('motif', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Motif de la demande (optionnel)'
                ],
                'label' => 'Motif'
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
                    return $doc->getTitre() . ($doc->getEmploye() ? ' (' . $doc->getEmploye()->getNomComplet() . ')' : '');
                },
                'placeholder' => 'Sélectionner un justificatif (optionnel)',
                'attr' => [
                    'class' => 'form-control select2'
                ],
                'label' => 'Document justificatif'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Conges::class,
            'institution' => null,
        ]);
    }
}