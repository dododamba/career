<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\OccupationPoste;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class PosteAffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'] ?? null;

        $builder
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function (Employe $employe) {
                    return sprintf('%s (%s) - %s', $employe->getNomComplet(), $employe->getMatricule(), $employe->getFonction()?->getLibelle() ?? 'Sans fonction');
                },
                'label' => 'Agent à affecter *',
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
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un agent à affecter']),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('date_debut', DateType::class, [
                'label' => 'Date d\'effet / Prise de fonction sur le poste *',
                'required' => true,
                'widget' => 'single_text',
                'html5' => true,
                'data' => new \DateTime(),
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La date de prise de fonction est obligatoire']),
                ],
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('motif', ChoiceType::class, [
                'label' => 'Motif d\'affectation *',
                'required' => true,
                'choices' => [
                    'Recrutement initial' => OccupationPoste::MOTIF_RECRUTEMENT,
                    'Promotion interne' => OccupationPoste::MOTIF_PROMOTION,
                    'Mutation / Mobilité' => OccupationPoste::MOTIF_MUTATION,
                    'Intérim / Délégation' => OccupationPoste::MOTIF_INTERIM,
                    'Réorganisation de service' => OccupationPoste::MOTIF_REORGANISATION,
                    'Autre motif' => OccupationPoste::MOTIF_AUTRE,
                ],
                'data' => OccupationPoste::MOTIF_RECRUTEMENT,
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('commentaire', TextareaType::class, [
                'label' => 'Observations & Références administratives',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Références de la note de service ou arrêté d\'affectation, contexte...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'institution' => null,
        ]);

        $resolver->setAllowedTypes('institution', ['null', 'App\Entity\Institution']);
    }
}
