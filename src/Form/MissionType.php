<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\Mission;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];

        $builder
            ->add('objet', TextType::class, [
                'label' => 'Objet officiel de la mission',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Audit des services déconcentrés de la région du Bélier',
                ],
            ])
            ->add('destination', TextType::class, [
                'label' => 'Destination (Ville / Localité)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Yamoussoukro',
                ],
            ])
            ->add('pays', TextType::class, [
                'label' => 'Pays',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Côte d\'Ivoire',
                ],
            ])
            ->add('lieuPrecis', TextType::class, [
                'required' => false,
                'label' => 'Lieu précis ou organisme d\'accueil (optionnel)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Préfecture de région / Direction régionale',
                ],
            ])
            ->add('itineraire', TextareaType::class, [
                'required' => false,
                'label' => 'Itinéraire détaillé (optionnel)',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Ex: Abidjan -> Toumodi -> Yamoussoukro -> Abidjan',
                ],
            ])
            ->add('modeTransport', ChoiceType::class, [
                'choices' => Mission::TRANSPORTS,
                'label' => 'Moyen de transport',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('priseEnCharge', ChoiceType::class, [
                'choices' => Mission::PRISES_EN_CHARGE,
                'label' => 'Modalités de prise en charge financière',
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de départ',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('dateFin', DateType::class, [
                'widget' => 'single_text',
                'label' => 'Date de retour',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('budgetEstime', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'label' => 'Budget prévisionnel global (F CFA / Devise)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 450000',
                ],
            ])
            ->add('imputationBudgetaire', TextType::class, [
                'required' => false,
                'label' => 'Ligne / Imputation budgétaire (optionnel)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Ligne 6241 - Frais de mission intérieure',
                ],
            ])
            ->add('chefMission', EntityType::class, [
                'class' => Employe::class,
                'required' => false,
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
                'choice_label' => function (Employe $e) {
                    return $e->getNomComplet() . ($e->getMatricule() ? ' (' . $e->getMatricule() . ')' : '');
                },
                'placeholder' => 'Sélectionner un chef de mission (optionnel)',
                'attr' => [
                    'class' => 'form-select select2',
                ],
                'label' => 'Chef de mission',
            ])
            ->add('agentsParticipants', EntityType::class, [
                'class' => Employe::class,
                'mapped' => false,
                'multiple' => true,
                'required' => false,
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
                'choice_label' => function (Employe $e) {
                    return $e->getNomComplet() . ($e->getMatricule() ? ' (' . $e->getMatricule() . ')' : '');
                },
                'attr' => [
                    'class' => 'form-select select2',
                    'multiple' => 'multiple',
                ],
                'label' => 'Membres de la délégation / Agents participants',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Termes de référence / Objectifs et contexte',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Décrivez les résultats attendus, le contexte et les livrables de la mission...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
            'institution' => null,
        ]);

        $resolver->setAllowedTypes('institution', [Institution::class, 'null']);
    }
}
