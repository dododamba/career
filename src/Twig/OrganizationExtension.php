<?php

namespace App\Twig;

use App\Service\OrganizationContextService;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class OrganizationExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly OrganizationContextService $organizationContextService,
        private readonly Packages $assetPackages
    ) {
    }

    public function getGlobals(): array
    {
        $org = $this->organizationContextService->getSettings();

        return [
            'organization' => $org,
            'app_name' => $org->getAppName(),
            'app_institution' => $org->getName(),
            'app_country' => $org->getCountry() ?: '',
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('is_module_enabled', [$this, 'isModuleEnabled']),
            new TwigFunction('organization_logo', [$this, 'getOrganizationLogo']),
        ];
    }

    public function isModuleEnabled(string $moduleKey): bool
    {
        return $this->organizationContextService->isModuleEnabled($moduleKey);
    }

    public function getOrganizationLogo(): string
    {
        $org = $this->organizationContextService->getSettings();
        $logo = $org->getLogoPath();

        if ($logo && file_exists(__DIR__ . '/../../public/' . ltrim($logo, '/'))) {
            return $this->assetPackages->getUrl(ltrim($logo, '/'));
        }

        // Fallback générique par défaut
        return $this->assetPackages->getUrl('images/brand/armoirie-96.png');
    }
}
