<?php

namespace App\Service;

use App\Entity\Institution;
use App\Entity\OrganizationSetting;
use App\Entity\User;
use App\Repository\OrganizationSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class OrganizationContextService
{
    private ?OrganizationSetting $cachedSetting = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly OrganizationSettingRepository $repository,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    /**
     * Récupère la configuration centrale de l'organisation.
     * Si aucune configuration n'existe et que la base contient déjà des utilisateurs/institutions,
     * elle est automatiquement initialisée à partir de l'existant.
     */
    public function getSettings(): OrganizationSetting
    {
        if ($this->cachedSetting !== null) {
            return $this->cachedSetting;
        }

        try {
            $setting = $this->repository->getPrimarySetting();

            if ($setting !== null) {
                $this->cachedSetting = $setting;
                return $setting;
            }

            // Détection d'une installation existante vs vierge
            $userCount = (int)$this->entityManager->getRepository(User::class)
                ->createQueryBuilder('u')
                ->select('COUNT(u.id)')
                ->getQuery()
                ->getSingleScalarResult();

            $institutionCount = (int)$this->entityManager->getRepository(Institution::class)
                ->createQueryBuilder('i')
                ->select('COUNT(i.id)')
                ->getQuery()
                ->getSingleScalarResult();

            if ($userCount > 0 || $institutionCount > 0) {
                // Installation existante : on initialise automatiquement
                $primaryInstitution = $this->entityManager->getRepository(Institution::class)->findOneBy([], ['id' => 'ASC']);

                $setting = new OrganizationSetting();
                $setting->setIsInstalled(true);
                $setting->setInstalledAt(new \DateTimeImmutable());
                $setting->setUpdatedAt(new \DateTimeImmutable());

                if ($primaryInstitution) {
                    $setting->setName($primaryInstitution->getNom() ?: 'Ministère du Commerce et de l\'Industrie');
                    $setting->setShortName($primaryInstitution->getCode() ?: 'MCI');
                    $setting->setEmail($primaryInstitution->getEmail());
                    $setting->setPhone($primaryInstitution->getTelephone());
                    $setting->setAddress($primaryInstitution->getAdresse());
                    $setting->setWebsite($primaryInstitution->getSiteWeb());
                    $setting->setDescription($primaryInstitution->getDescription());
                    if ($primaryInstitution->getLogo()) {
                        $setting->setLogoPath($primaryInstitution->getLogo());
                    }
                } else {
                    $setting->setName('Ministère du Commerce et de l\'Industrie');
                    $setting->setShortName('MCI');
                }

                $setting->setAppName('Suivi des Carrières');
                $setting->setCountry('République du Tchad');
                $setting->setCity('N\'Djamena');
                $setting->setPrimaryColor('#002664');
                $setting->setSecondaryColor('#001844');
                $setting->setAccentColor('#FECB00');

                $this->entityManager->persist($setting);
                $this->entityManager->flush();

                $this->cachedSetting = $setting;
                return $setting;
            }

            // Installation vierge non encore initialisée
            $uninstalled = new OrganizationSetting();
            $uninstalled->setIsInstalled(false);
            $this->cachedSetting = $uninstalled;
            return $uninstalled;

        } catch (\Throwable $e) {
            $this->logger?->warning('OrganizationContextService: impossible de charger les paramètres: ' . $e->getMessage());
            $fallback = new OrganizationSetting();
            $fallback->setIsInstalled(true);
            return $fallback;
        }
    }

    /**
     * Vérifie si l'application est considérée comme installée.
     */
    public function isInstalled(): bool
    {
        return $this->getSettings()->isInstalled();
    }

    /**
     * Vérifie si un module métier spécifique est activé.
     */
    public function isModuleEnabled(string $moduleKey): bool
    {
        return $this->getSettings()->isModuleEnabled($moduleKey);
    }

    /**
     * Enregistre ou met à jour la configuration centrale.
     */
    public function saveSettings(OrganizationSetting $setting): void
    {
        $setting->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($setting);
        $this->entityManager->flush();
        $this->cachedSetting = $setting;
    }

    /**
     * Réinitialise le cache en mémoire (utile pour les tests et workers).
     */
    public function resetCache(): void
    {
        $this->cachedSetting = null;
    }
}
