<?php

namespace App\Entity;

use App\Repository\OrganizationSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizationSettingRepository::class)]
#[ORM\Table(name: 'organization_setting')]
class OrganizationSetting
{
    public const TYPE_MINISTRY = 'ministry';
    public const TYPE_PUBLIC_ADMIN = 'public_admin';
    public const TYPE_PUBLIC_INSTITUTION = 'public_institution';
    public const TYPE_PRIVATE_COMPANY = 'private_company';
    public const TYPE_NGO = 'ngo';
    public const TYPE_OTHER = 'other';

    public const DEFAULT_MODULES = [
        'employe' => true,
        'pointage' => true,
        'conges' => true,
        'absences' => true,
        'missions' => true,
        'actes' => true,
        'visites' => true,
        'statistiques' => true,
        'organisation' => true,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name = 'Administration Centrale';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $shortName = null;

    #[ORM\Column(length: 255)]
    private string $appName = 'Suivi des Carrières';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private string $organizationType = self::TYPE_PUBLIC_ADMIN;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $secondaryLogoPath = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faviconPath = null;

    #[ORM\Column(length: 20)]
    private string $primaryColor = '#002664';

    #[ORM\Column(length: 20)]
    private string $secondaryColor = '#001844';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $accentColor = '#FECB00';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 10)]
    private string $locale = 'fr';

    #[ORM\Column(length: 50)]
    private string $timezone = 'Africa/Ndjamena';

    #[ORM\Column(length: 10)]
    private string $currency = 'XAF';

    #[ORM\Column(length: 20)]
    private string $dateFormat = 'd/m/Y';

    #[ORM\Column(length: 20)]
    private string $timeFormat = 'H:i';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pdfHeader = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $pdfFooter = null;

    /**
     * @var array<string, bool>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $enabledModules = self::DEFAULT_MODULES;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isInstalled = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $installedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->enabledModules = self::DEFAULT_MODULES;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(?string $shortName): static
    {
        $this->shortName = $shortName;
        return $this;
    }

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function setAppName(string $appName): static
    {
        $this->appName = $appName;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getOrganizationType(): string
    {
        return $this->organizationType;
    }

    public function setOrganizationType(string $organizationType): static
    {
        $this->organizationType = $organizationType;
        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }

    public function setLogoPath(?string $logoPath): static
    {
        $this->logoPath = $logoPath;
        return $this;
    }

    public function getSecondaryLogoPath(): ?string
    {
        return $this->secondaryLogoPath;
    }

    public function setSecondaryLogoPath(?string $secondaryLogoPath): static
    {
        $this->secondaryLogoPath = $secondaryLogoPath;
        return $this;
    }

    public function getFaviconPath(): ?string
    {
        return $this->faviconPath;
    }

    public function setFaviconPath(?string $faviconPath): static
    {
        $this->faviconPath = $faviconPath;
        return $this;
    }

    public function getPrimaryColor(): string
    {
        return $this->primaryColor ?: '#002664';
    }

    public function setPrimaryColor(string $primaryColor): static
    {
        $this->primaryColor = $primaryColor;
        return $this;
    }

    public function getSecondaryColor(): string
    {
        return $this->secondaryColor ?: '#001844';
    }

    public function setSecondaryColor(string $secondaryColor): static
    {
        $this->secondaryColor = $secondaryColor;
        return $this;
    }

    public function getAccentColor(): ?string
    {
        return $this->accentColor ?: '#FECB00';
    }

    public function setAccentColor(?string $accentColor): static
    {
        $this->accentColor = $accentColor;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;
        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getDateFormat(): string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(string $dateFormat): static
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getTimeFormat(): string
    {
        return $this->timeFormat;
    }

    public function setTimeFormat(string $timeFormat): static
    {
        $this->timeFormat = $timeFormat;
        return $this;
    }

    public function getPdfHeader(): ?string
    {
        return $this->pdfHeader;
    }

    public function setPdfHeader(?string $pdfHeader): static
    {
        $this->pdfHeader = $pdfHeader;
        return $this;
    }

    public function getPdfFooter(): ?string
    {
        return $this->pdfFooter;
    }

    public function setPdfFooter(?string $pdfFooter): static
    {
        $this->pdfFooter = $pdfFooter;
        return $this;
    }

    /**
     * @return array<string, bool>
     */
    public function getEnabledModules(): array
    {
        return array_merge(self::DEFAULT_MODULES, $this->enabledModules);
    }

    /**
     * @param array<string, bool> $enabledModules
     */
    public function setEnabledModules(array $enabledModules): static
    {
        $this->enabledModules = $enabledModules;
        return $this;
    }

    public function isModuleEnabled(string $moduleKey): bool
    {
        $modules = $this->getEnabledModules();
        return isset($modules[$moduleKey]) ? (bool)$modules[$moduleKey] : true;
    }

    public function isInstalled(): bool
    {
        return $this->isInstalled;
    }

    public function setIsInstalled(bool $isInstalled): static
    {
        $this->isInstalled = $isInstalled;
        if ($isInstalled && !$this->installedAt) {
            $this->installedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getInstalledAt(): ?\DateTimeImmutable
    {
        return $this->installedAt;
    }

    public function setInstalledAt(?\DateTimeImmutable $installedAt): static
    {
        $this->installedAt = $installedAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
