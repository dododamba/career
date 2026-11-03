<?php

namespace App\Controller;

use App\Entity\Affectation;
use App\Entity\Document;
use App\Entity\DocumentVersion;
use App\Entity\Employe;
use App\Form\DocumentType;
use App\Form\DocumentVersionType;
use App\MultiTenancy\TenantContextInterface;
use App\Repository\AffectationRepository;
use App\Repository\DocumentRepository;
use App\Repository\DocumentVersionRepository;
use App\Repository\EmployeRepository;
use App\Security\Voter\DocumentVoter;
use App\Service\DocumentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/document')]
#[IsGranted('ROLE_USER')]
final class DocumentController extends AbstractController
{
    #[Route(name: 'app_document_index', methods: ['GET'])]
    public function index(
        Request $request,
        DocumentRepository $documentRepository,
        TenantContextInterface $tenantContext,
        EmployeRepository $employeRepository
    ): Response {
        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();

        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Aucune institution active.');
            return $this->redirectToRoute('app_login');
        }

        $categorie = $request->query->get('categorie');
        $statut = $request->query->get('statut');
        $search = $request->query->get('q');
        $employeId = $request->query->getInt('employe_id');

        $selectedEmploye = null;
        if ($employeId > 0) {
            $selectedEmploye = $employeRepository->find($employeId);
            if ($selectedEmploye && $selectedEmploye->getInstitution()?->getId() !== $institution->getId()) {
                $selectedEmploye = null;
            }
        }

        $documents = $documentRepository->findByInstitution(
            institution: $institution,
            categorie: $categorie,
            statut: $statut,
            search: $search,
            employe: $selectedEmploye
        );

        $stats = $documentRepository->getStatsByInstitution($institution);

        return $this->render('document/index.html.twig', [
            'documents' => $documents,
            'stats' => $stats,
            'currentCategorie' => $categorie,
            'currentStatut' => $statut,
            'search' => $search,
            'selectedEmploye' => $selectedEmploye,
            'institution' => $institution,
        ]);
    }

    #[Route('/nouveau', name: 'app_document_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(
        Request $request,
        TenantContextInterface $tenantContext,
        EmployeRepository $employeRepository,
        AffectationRepository $affectationRepository,
        DocumentService $documentService
    ): Response {
        $this->denyAccessUnlessGranted(DocumentVoter::CREATE, Document::class);

        $institution = $tenantContext->getTenant() ?? $this->getUser()?->getInstitution();
        if (!$institution && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            $this->addFlash('error', 'Institution introuvable.');
            return $this->redirectToRoute('app_document_index');
        }

        // Présélection éventuelle d'un agent
        $presetEmploye = null;
        $employeId = $request->query->getInt('employe_id');
        if ($employeId > 0) {
            $presetEmploye = $employeRepository->find($employeId);
            if ($presetEmploye && $presetEmploye->getInstitution()?->getId() !== $institution->getId()) {
                throw $this->createAccessDeniedException('Accès refusé à cet employé.');
            }
        }

        // Présélection éventuelle d'une affectation
        $presetAffectation = null;
        $affectationId = $request->query->getInt('affectation_id');
        if ($affectationId > 0) {
            $presetAffectation = $affectationRepository->find($affectationId);
            if ($presetAffectation && $presetAffectation->getInstitution()?->getId() !== $institution->getId()) {
                throw $this->createAccessDeniedException('Accès refusé à cette affectation.');
            }
        }

        $document = new Document();
        if ($presetEmploye) {
            $document->setEmploye($presetEmploye);
        }
        if ($presetAffectation) {
            $document->setAffectation($presetAffectation);
            if (!$presetEmploye && $presetAffectation->getEmploye()) {
                $document->setEmploye($presetAffectation->getEmploye());
            }
        }

        $form = $this->createForm(DocumentType::class, $document, [
            'institution' => $institution,
            'is_agent_preset' => ($presetEmploye !== null),
            'is_edit' => false,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('fichier')->getData();
            if (!$file) {
                $this->addFlash('error', 'Veuillez sélectionner un fichier.');
                return $this->render('document/new.html.twig', [
                    'form' => $form,
                    'presetEmploye' => $presetEmploye,
                    'presetAffectation' => $presetAffectation,
                ]);
            }

            $employe = $presetEmploye ?? $form->get('employe')->getData();

            try {
                $createdDoc = $documentService->uploadDocument(
                    file: $file,
                    institution: $institution,
                    titre: $document->getTitre() ?? 'Document',
                    categorie: $document->getCategorie(),
                    niveauConfidentialite: $document->getNiveauConfidentialite(),
                    employe: $employe,
                    affectation: $document->getAffectation(),
                    reference: $document->getReference(),
                    description: $document->getDescription(),
                    dateDocument: $document->getDateDocument(),
                    uploader: $this->getUser(),
                    statut: $document->getStatut()
                );

                $this->addFlash('success', sprintf('Document "%s" téléversé et sécurisé avec succès.', $createdDoc->getTitre()));

                if ($presetEmploye) {
                    return $this->redirectToRoute('app_employe_show', ['id' => $presetEmploye->getId()]);
                }
                if ($presetAffectation) {
                    return $this->redirectToRoute('app_affectation_show', ['id' => $presetAffectation->getId()]);
                }

                return $this->redirectToRoute('app_document_show', ['id' => $createdDoc->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
            }
        }

        return $this->render('document/new.html.twig', [
            'form' => $form,
            'presetEmploye' => $presetEmploye,
            'presetAffectation' => $presetAffectation,
        ]);
    }

    #[Route('/{id}', name: 'app_document_show', methods: ['GET'])]
    public function show(Document $document): Response
    {
        $this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);

        $versionForm = $this->createForm(DocumentVersionType::class, null, [
            'action' => $this->generateUrl('app_document_version_new', ['id' => $document->getId()]),
        ]);

        return $this->render('document/show.html.twig', [
            'document' => $document,
            'versionForm' => $versionForm,
        ]);
    }

    #[Route('/{id}/telecharger', name: 'app_document_download', methods: ['GET'])]
    public function download(Document $document, DocumentService $documentService): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted(DocumentVoter::DOWNLOAD, $document);

        return $documentService->serveDocument($document, asAttachment: true);
    }

    #[Route('/{id}/previsualiser', name: 'app_document_preview', methods: ['GET'])]
    public function preview(Document $document, DocumentService $documentService): BinaryFileResponse
    {
        $this->denyAccessUnlessGranted(DocumentVoter::VIEW, $document);

        return $documentService->serveDocument($document, asAttachment: false);
    }

    #[Route('/{id}/version/{versionId}/telecharger', name: 'app_document_version_download', methods: ['GET'])]
    public function downloadVersion(
        Document $document,
        int $versionId,
        DocumentVersionRepository $versionRepository,
        DocumentService $documentService
    ): BinaryFileResponse {
        $this->denyAccessUnlessGranted(DocumentVoter::DOWNLOAD, $document);

        $version = $versionRepository->find($versionId);
        if (!$version || $version->getDocument()?->getId() !== $document->getId()) {
            throw $this->createNotFoundException('Version introuvable.');
        }

        return $documentService->serveDocument($version, asAttachment: true);
    }

    #[Route('/{id}/version', name: 'app_document_version_new', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function newVersion(
        Request $request,
        Document $document,
        DocumentService $documentService
    ): Response {
        $this->denyAccessUnlessGranted(DocumentVoter::NEW_VERSION, $document);

        $form = $this->createForm(DocumentVersionType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $file */
            $file = $form->get('fichier')->getData();
            if ($file) {
                try {
                    $comm = $form->get('commentaire_version')->getData();
                    $v = $documentService->uploadNewVersion($document, $file, $comm, $this->getUser());
                    $this->addFlash('success', sprintf('Nouvelle version v%d enregistrée avec succès.', $v->getNumeroVersion()));
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors du versement de la version : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Veuillez sélectionner un fichier.');
            }
        }

        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }

    #[Route('/{id}/modifier', name: 'app_document_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(
        Request $request,
        Document $document,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted(DocumentVoter::EDIT, $document);

        $form = $this->createForm(DocumentType::class, $document, [
            'institution' => $document->getInstitution(),
            'is_agent_preset' => false,
            'is_edit' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $document->setUpdatedAt(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Métadonnées du document mises à jour avec succès.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/edit.html.twig', [
            'form' => $form,
            'document' => $document,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_document_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Request $request,
        Document $document,
        DocumentService $documentService
    ): Response {
        $this->denyAccessUnlessGranted(DocumentVoter::DELETE, $document);

        if ($this->isCsrfTokenValid('delete' . $document->getId(), $request->request->get('_token'))) {
            try {
                $documentService->supprimerDocument($document);
                $this->addFlash('success', 'Document supprimé définitivement du coffre-fort.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_document_index');
    }
}
