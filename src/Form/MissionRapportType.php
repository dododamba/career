<?php

namespace App\Form;

use App\Entity\Document;
use App\Entity\Institution;
use App\Entity\Mission;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MissionRapportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $institution = $options['institution'];

        $builder
            ->add('rapportTexte', TextareaType::class, [
                'required' => false,
                'label' => 'Compte-rendu et rapport de mission',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 8,
                    'placeholder' => 'Rédigez le compte-rendu d\'exécution, les résultats atteints, constats, recommandations...',
                ],
            ])
            ->add('documentRapport', EntityType::class, [
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
                    return $doc->getTitre() . ($doc->getCategorie() ? ' [' . $doc->getCategorie() . ']' : '');
                },
                'placeholder' => 'Sélectionner un document officiel archivé (PDF, Word...)',
                'attr' => [
                    'class' => 'form-select select2',
                ],
                'label' => 'Fichier du rapport de mission (Document RH archivé)',
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
