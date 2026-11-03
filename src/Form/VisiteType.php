<?php
// App/Form/VisiteType.php
namespace App\Form;

use App\Entity\Employe;
use App\Entity\Visite;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VisiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution']; // Custom option passed from controller
        $builder
            ->add('visiteur', TextType::class, [
                'label' => 'Nom du visiteur',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Nom complet du visiteur'
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Email du visiteur'
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numéro de téléphone'
                ],
            ])
            ->add('arrivee', DateTimeType::class, [
                'label' => 'Date et heure d\'arrivée',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('depart', DateTimeType::class, [
                'label' => 'Date et heure de départ prévue',
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'class' => 'form-control'
                ],
            ])
            ->add('objet', TextType::class, [
                'label' => 'Objet de la visite',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Raison de la visite'
                ],
            ])
            ->add('employe', EntityType::class, [
                'class' => Employe::class,
                'choice_label' => function(Employe $employe) {
                    return $employe->getNomComplet();
                },
                'label' => 'Employé à rencontrer',
                'attr' => [
                    'class' => 'form-select'
                ],
            ])
            ->add('commentaires', TextareaType::class, [
                'label' => 'Commentaires',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Informations supplémentaires'
                ],
            ])
            ->add('badge', TextType::class, [
                'label' => 'Numéro de badge',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Numéro du badge visiteur'
                ],
            ])
            ->add('statut', CheckboxType::class, [
                'label' => 'Visite terminée',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input'
                ],
                'label_attr' => [
                    'class' => 'form-check-label'
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Visite::class,
            'institution' => null,
        ]);
    }
}