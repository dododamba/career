<?php

namespace App\Entity;

// Ensure imports in the file below
namespace App\Service;

use App\Entity\Employe;
use App\Entity\Institution;
use App\Entity\OrganisationHistory;
use App\Entity\Service;
use App\Entity\Structure;
use App\Entity\User;
use App\Repository\EmployeRepository;
use App\Repository\OrganisationHistoryRepository;
use App\Repository\ServiceRepository;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class OrganisationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StructureRepository $structureRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly EmployeRepository $employeRepository,
        private readonly OrganisationHistoryRepository $historyRepository,
        private readonly Security $security
    ) {
    }

    /**
     * Valide qu'un rattachement parent/enfant ne crée pas de cycle hiérarchique
     * et respecte le même tenant.
     *
     * @throws \InvalidArgumentException
     */
    public function validateParentStructure(Structure $child, ?Structure $parent): void
    {
        if ($parent === null) {
            return;
        }

        // 1. Même tenant
        if ($child->getInstitution() && $parent->getInstitution() && 
            $child->getInstitution()->getId() !== $parent->getInstitution()->getId()) {
            throw new \InvalidArgumentException("La structure parente doit appartenir à la même institution.");
        }

        // 2. Pas de self-parenting
        if ($child->getId() !== null && $child->getId() === $parent->getId()) {
            throw new \InvalidArgumentException("Une structure ne peut pas être sa propre parente.");
        }

        // 3. Détection de cycle (le parent ne doit pas avoir le child comme ancêtre)
        $current = $parent;
        $visited = [];
        while ($current !== null) {
            if ($child->getId() !== null && $current->getId() === $child->getId()) {
                throw new \InvalidArgumentException("Cycle hiérarchique détecté : la structure parente sélectionnée est déjà une descendante de cette structure.");
            }

            if (isset($visited[$current->getId()])) {
                throw new \InvalidArgumentException("Cycle détecté dans la hiérarchie existante.");
            }
            if ($current->getId() !== null) {
                $visited[$current->getId()] = true;
            }

            $current = $current->getParent();
        }
    }

    /**
     * Valide que le responsable appartient bien au même tenant
     *
     * @throws \InvalidArgumentException
     */
    public function validateResponsable(Institution $institution, ?Employe $responsable): void
    {
        if ($responsable === null) {
            return;
        }

        if ($responsable->getInstitution() && $responsable->getInstitution()->getId() !== $institution->getId()) {
            throw new \InvalidArgumentException("Le responsable doit appartenir à la même institution.");
        }
    }

    /**
     * Enregistre un événement dans l'historique d'organisation
     */
    public function logChange(
        Institution $institution,
        string $entityType,
        int $entityId,
        ?string $entityName,
        string $action,
        ?array $details = null,
        ?User $user = null
    ): OrganisationHistory {
        $history = new OrganisationHistory();
        $history->setInstitution($institution);
        $history->setEntityType($entityType);
        $history->setEntityId($entityId);
        $history->setEntityName($entityName);
        $history->setAction($action);
        $history->setDetails($details);

        if ($user === null) {
            $currentUser = $this->security->getUser();
            if ($currentUser instanceof User) {
                $user = $currentUser;
            }
        }
        $history->setChangedBy($user);

        $this->entityManager->persist($history);
        $this->entityManager->flush();

        return $history;
    }

    /**
     * Construit l'arbre complet de l'organigramme pour une institution
     */
    public function buildOrganigrammeTree(Institution $institution): array
    {
        $allStructures = $this->structureRepository->findTreeByInstitution($institution);
        $directServices = $this->serviceRepository->findBy(['institution' => $institution, 'structure' => null], ['nom' => 'ASC']);

        // Formatage du responsable de l'institution
        $instResp = $institution->getResponsable();

        $tree = [
            'id' => 'inst_' . $institution->getId(),
            'entityId' => $institution->getId(),
            'name' => $institution->getNom() ?? 'Institution',
            'type' => 'INSTITUTION',
            'typeLabel' => $institution->getTypeInstitution()?->getNom() ?? 'Institution',
            'code' => $institution->getCode() ?? 'INST-' . $institution->getId(),
            'statut' => $institution->getStatus(),
            'responsable' => $instResp ? [
                'id' => $instResp->getId(),
                'nomComplet' => trim(($instResp->getNom() ?? '') . ' ' . ($instResp->getPrenom() ?? '')),
                'fonction' => $instResp->getFonction()?->getLibelle() ?? 'Responsable',
                'matricule' => $instResp->getMatricule(),
            ] : null,
            'children' => [],
            'directServices' => [],
            'totalStructures' => count($allStructures),
            'totalServices' => $this->serviceRepository->count(['institution' => $institution]),
            'totalEmployes' => $this->employeRepository->count(['institution' => $institution]),
        ];

        // Indexation des structures par id
        $structNodes = [];
        foreach ($allStructures as $structure) {
            $resp = $structure->getResponsable();
            $servicesList = [];
            foreach ($structure->getServices() as $srv) {
                $srvResp = $srv->getResponsable();
                $servicesList[] = [
                    'id' => 'srv_' . $srv->getId(),
                    'entityId' => $srv->getId(),
                    'name' => $srv->getNom(),
                    'code' => $srv->getCode(),
                    'statut' => $srv->getStatut(),
                    'type' => 'SERVICE',
                    'responsable' => $srvResp ? [
                        'id' => $srvResp->getId(),
                        'nomComplet' => trim(($srvResp->getNom() ?? '') . ' ' . ($srvResp->getPrenom() ?? '')),
                        'fonction' => $srvResp->getFonction()?->getLibelle() ?? 'Chef de service',
                        'matricule' => $srvResp->getMatricule(),
                    ] : null,
                    'employesCount' => $srv->getEmployes()->count(),
                ];
            }

            $structNodes[$structure->getId()] = [
                'id' => 'struct_' . $structure->getId(),
                'entityId' => $structure->getId(),
                'name' => $structure->getNom(),
                'code' => $structure->getCode(),
                'type' => $structure->getType(),
                'typeLabel' => $this->formatTypeLabel($structure->getType()),
                'statut' => $structure->getStatut(),
                'parentId' => $structure->getParent()?->getId(),
                'responsable' => $resp ? [
                    'id' => $resp->getId(),
                    'nomComplet' => trim(($resp->getNom() ?? '') . ' ' . ($resp->getPrenom() ?? '')),
                    'fonction' => $resp->getFonction()?->getLibelle() ?? 'Directeur / Responsable',
                    'matricule' => $resp->getMatricule(),
                ] : null,
                'services' => $servicesList,
                'children' => [],
            ];
        }

        // Assemblage de l'arborescence des structures
        $rootStructures = [];
        foreach ($structNodes as $id => &$node) {
            $parentId = $node['parentId'];
            if ($parentId !== null && isset($structNodes[$parentId])) {
                $structNodes[$parentId]['children'][] = &$node;
            } else {
                $rootStructures[] = &$node;
            }
        }
        unset($node);

        $tree['children'] = $rootStructures;

        // Services directs rattachés à l'institution (sans structure parente)
        foreach ($directServices as $ds) {
            $dsResp = $ds->getResponsable();
            $tree['directServices'][] = [
                'id' => 'srv_' . $ds->getId(),
                'entityId' => $ds->getId(),
                'name' => $ds->getNom(),
                'code' => $ds->getCode(),
                'statut' => $ds->getStatut(),
                'type' => 'SERVICE',
                'responsable' => $dsResp ? [
                    'id' => $dsResp->getId(),
                    'nomComplet' => trim(($dsResp->getNom() ?? '') . ' ' . ($dsResp->getPrenom() ?? '')),
                    'fonction' => $dsResp->getFonction()?->getLibelle() ?? 'Chef de service',
                    'matricule' => $dsResp->getMatricule(),
                ] : null,
                'employesCount' => $ds->getEmployes()->count(),
            ];
        }

        return $tree;
    }

    /**
     * Calcule la vue d'ensemble et les statistiques 360° pour une institution
     */
    public function getOrganisationOverview(Institution $institution): array
    {
        $structuresCount = $this->structureRepository->countByInstitution($institution);
        $structuresByType = $this->structureRepository->countByType($institution);
        $servicesCount = $this->serviceRepository->count(['institution' => $institution]);
        $employesCount = $this->employeRepository->count(['institution' => $institution]);

        $recentHistory = $this->historyRepository->findByInstitution($institution, 15);

        return [
            'structuresCount' => $structuresCount,
            'structuresByType' => $structuresByType,
            'servicesCount' => $servicesCount,
            'employesCount' => $employesCount,
            'recentHistory' => $recentHistory,
        ];
    }

    public function formatTypeLabel(string $type): string
    {
        return match ($type) {
            Structure::TYPE_DIRECTION_GENERALE => 'Direction Générale',
            Structure::TYPE_DIRECTION => 'Direction',
            Structure::TYPE_DEPARTEMENT => 'Département',
            Structure::TYPE_DIVISION => 'Division',
            Structure::TYPE_SERVICE => 'Service rattaché',
            default => 'Autre structure',
        };
    }
}
