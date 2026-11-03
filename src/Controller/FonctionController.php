<?php

namespace App\Controller;

use App\Entity\Fonction;
use App\Form\FonctionType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\FonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/fonction')]
#[IsGranted('ROLE_USER')]
final class FonctionController extends AbstractController
{
    #[Route(name: 'app_fonction_index', methods: ['GET'])]
    public function index(FonctionRepository $fonctionRepository): Response
    {
        return $this->render('fonction/index.html.twig', [
            'fonctions' => $fonctionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_fonction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, TenantContextInterface $tenantContext): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $fonction = new Fonction();
        $form = $this->createForm(FonctionType::class, $fonction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $fonction->setInstitution($tenantContext->requireTenant());
            }

            $entityManager->persist($fonction);
            $entityManager->flush();

            return $this->redirectToRoute('app_fonction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fonction/new.html.twig', [
            'fonction' => $fonction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fonction_show', methods: ['GET'])]
    public function show(Fonction $fonction): Response
    {
        $this->checkFonctionAccess($fonction);

        return $this->render('fonction/show.html.twig', [
            'fonction' => $fonction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_fonction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Fonction $fonction, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->checkFonctionModificationAccess($fonction);

        $form = $this->createForm(FonctionType::class, $fonction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_fonction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('fonction/edit.html.twig', [
            'fonction' => $fonction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_fonction_delete', methods: ['POST'])]
    public function delete(Request $request, Fonction $fonction, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->checkFonctionModificationAccess($fonction);

        if ($this->isCsrfTokenValid('delete'.$fonction->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($fonction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_fonction_index', [], Response::HTTP_SEE_OTHER);
    }

    private function checkFonctionAccess(Fonction $fonction): void
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        $inst = $fonction->getInstitution();
        if ($inst === null) {
            // Global functions can be viewed by all
            return;
        }

        $userInst = $this->getUser()?->getInstitution();
        if ($userInst === null || $userInst->getId() !== $inst->getId()) {
            throw new AccessDeniedHttpException('Accès interdit : cette fonction appartient à une autre institution.');
        }
    }

    private function checkFonctionModificationAccess(Fonction $fonction): void
    {
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        $inst = $fonction->getInstitution();
        if ($inst === null) {
            // Global system functions can only be modified/deleted by super admins
            throw new AccessDeniedHttpException('Seul un Super Administrateur peut modifier ou supprimer une fonction globale du système.');
        }

        $userInst = $this->getUser()?->getInstitution();
        if ($userInst === null || $userInst->getId() !== $inst->getId()) {
            throw new AccessDeniedHttpException('Accès interdit : cette fonction appartient à une autre institution.');
        }
    }
}
