<?php

namespace App\Form;

use App\Entity\Employe;
use App\Entity\Pointage;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PointageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('date', DateType::class, [
            'widget' => 'single_text',
            'html5' => true,
            'required' => true,
            'label' => 'Date',
        ])
        ->add('heureArrivee', TimeType::class, [
            'widget' => 'single_text',
            'html5' => true,
            'required' => false,
            'label' => 'Heure d\'arrivée',
        ])
        ->add('heureDepart', TimeType::class, [
            'widget' => 'single_text',
            'html5' => true,
            'required' => false,
            'label' => 'Heure de départ',
        ])
        ->add('estPresent', CheckboxType::class, [
            'required' => false,
            'label' => 'Présent ?',
        ])
        ->add('motifAbsence', TextareaType::class, [
            'required' => false,
            'label' => 'Motif d\'absence',
        ])
        ->add('commentaire', TextareaType::class, [
            'required' => false,
            'label' => 'Commentaire',
            'attr' => ['rows' => 3],
        ])
        ->add('status', TextType::class, [
            'required' => false,
            'label' => 'Statut',
        ])
        ->add('employe', EntityType::class, [
            'class' => Employe::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('e')
                    ->where('e.actif = :actif')
                    ->setParameter('actif', true)
                    ->orderBy('e.nom', 'ASC');
            },
            'choice_label' => function(Employe $employe) {
                return $employe->getNom() . ' ' . $employe->getPrenom();
            },
            'placeholder' => 'Choisir un employé',
            'required' => true,
            'label' => 'Employé',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Pointage::class,
        ]);
    }
}