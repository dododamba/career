<?php

namespace App\Controller;

use App\Entity\TypeInstitution;
use App\Form\TypeInstitutionType;
use App\Repository\TypeInstitutionRepository;
use App\Repository\InstitutionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/type-institution')]
#[IsGranted('ROLE_ADMIN')]
final class TypeInstitutionController extends AbstractController
{
    #[Route(name: 'app_type_institution_index', methods: ['GET'])]
    public function index(
        TypeInstitutionRepository $typeInstitutionRepository,
        InstitutionRepository $institutionRepository
    ): Response {
        $types = $typeInstitutionRepository->findAll();
        
        // Ajouter le compte d'institutions pour chaque type
        $typesWithCount = [];
        foreach ($types as $type) {
            $typesWithCount[] = [
                'type' => $type,
                'institution_count' => count($institutionRepository->findBy(['typeInstitution' => $type]))
            ];
        }
        
        return $this->render('type_institution/index.html.twig', [
            'type_institutions' => $types,
            'types_institution' => $typesWithCount,
        ]);
    }

    #[Route('/new', name: 'app_type_institution_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // Gestion pour formulaire inline depuis la page institution
        if ($request->isMethod('POST') && $request->request->has('type_institution')) {
            $typeData = $request->request->all('type_institution');
            
            $typeInstitution = new TypeInstitution();
            $typeInstitution->setNom($typeData['nom']);
            
            // Génération automatique du slug si non fourni
            $slug = $typeData['slug'] ?? '';
            if (empty($slug)) {
                $slug = $slugger->slug($typeData['nom'])->lower();
            }
            $typeInstitution->setSlug($slug);
            
            $entityManager->persist($typeInstitution);
            $entityManager->flush();
            
            $this->addFlash('success', 'Type d\'institution créé avec succès!');
            return $this->redirectToRoute('app_institution_index');
        }
        
        // Gestion standard avec formulaire Symfony
        $typeInstitution = new TypeInstitution();
        $form = $this->createForm(TypeInstitutionType::class, $typeInstitution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération automatique du slug si non défini
            if (!$typeInstitution->getSlug()) {
                $slug = $slugger->slug($typeInstitution->getNom())->lower();
                $typeInstitution->setSlug($slug);
            }
            
            $entityManager->persist($typeInstitution);
            $entityManager->flush();

            $this->addFlash('success', 'Type d\'institution créé avec succès!');
            return $this->redirectToRoute('app_type_institution_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_institution/new.html.twig', [
            'type_institution' => $typeInstitution,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_institution_show', methods: ['GET'])]
    public function show(
        TypeInstitution $typeInstitution,
        InstitutionRepository $institutionRepository
    ): Response {
        // Récupérer toutes les institutions de ce type
        $institutions = $institutionRepository->findBy(['typeInstitution' => $typeInstitution]);
        
        return $this->render('type_institution/show.html.twig', [
            'type_institution' => $typeInstitution,
            'institutions' => $institutions,
            'institution_count' => count($institutions),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_institution_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        TypeInstitution $typeInstitution, 
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $form = $this->createForm(TypeInstitutionType::class, $typeInstitution);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Régénérer le slug si le nom a changé et que le slug n'a pas été modifié manuellement
            if (!$typeInstitution->getSlug()) {
                $slug = $slugger->slug($typeInstitution->getNom())->lower();
                $typeInstitution->setSlug($slug);
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Type d\'institution modifié avec succès!');
            return $this->redirectToRoute('app_type_institution_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_institution/edit.html.twig', [
            'type_institution' => $typeInstitution,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_institution_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        TypeInstitution $typeInstitution, 
        EntityManagerInterface $entityManager,
        InstitutionRepository $institutionRepository
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$typeInstitution->getId(), $request->request->get('_token'))) {
            // Vérifier s'il y a des institutions liées à ce type
            $institutions = $institutionRepository->findBy(['type_institution' => $typeInstitution]);
            
            if (count($institutions) > 0) {
                $this->addFlash('danger', 'Impossible de supprimer ce type car il est utilisé par ' . count($institutions) . ' institution(s).');
            } else {
                $entityManager->remove($typeInstitution);
                $entityManager->flush();
                $this->addFlash('success', 'Type d\'institution supprimé avec succès!');
            }
        } else {
            $this->addFlash('danger', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_type_institution_index', [], Response::HTTP_SEE_OTHER);
    }
}