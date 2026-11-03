<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DocumentVersionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fichier', FileType::class, [
                'label' => 'Nouvelle version du fichier *',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner le nouveau fichier.']),
                    new Assert\File([
                        'maxSize' => '25M',
                        'maxSizeMessage' => 'Le fichier dépasse la taille maximale de 25 Mo.',
                    ]),
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xls,.xlsx,.odt,.ods,.txt,.csv',
                ],
                'help' => 'La nouvelle version remplacera le fichier actif tout en conservant l\'historique précédent.',
            ])
            ->add('commentaire_version', TextareaType::class, [
                'label' => 'Motif ou commentaire de mise à jour',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Ex : Document signé et complété, correction d\'une erreur matérielle...',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
