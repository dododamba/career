<?php

namespace App\Controller;

use App\Entity\Institution;
use App\Entity\OrganizationSetting;
use App\Entity\User;
use App\Service\OrganizationContextService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends AbstractController
{
    #[Route('/install', name: 'app_install_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OrganizationContextService $organizationContextService,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        // Sécurité stricte : si l'application est déjà installée, interdire l'assistant
        if ($organizationContextService->isInstalled()) {
            $this->addFlash('info', 'L\'application est déjà installée et configurée.');
            return $this->redirectToRoute('app_login');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('install_wizard', $token)) {
                $error = 'Jeton de sécurité invalide. Veuillez rafraîchir la page et réessayer.';
            } else {
                $orgData = $request->request->all('organization');
                $adminData = $request->request->all('admin');

                $orgName = trim($orgData['name'] ?? '');
                $appName = trim($orgData['appName'] ?? '') ?: 'Suivi des Carrières';
                $adminEmail = trim($adminData['email'] ?? '');
                $adminFirstname = trim($adminData['firstname'] ?? '');
                $adminLastname = trim($adminData['lastname'] ?? '');
                $adminPassword = $adminData['password'] ?? '';
                $adminPasswordConfirm = $adminData['password_confirm'] ?? '';

                if (empty($orgName)) {
                    $error = 'Le nom de l\'organisation est obligatoire.';
                } elseif (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Une adresse email valide pour l\'administrateur est obligatoire.';
                } elseif (strlen($adminPassword) < 8) {
                    $error = 'Le mot de passe de l\'administrateur doit contenir au moins 8 caractères.';
                } elseif ($adminPassword !== $adminPasswordConfirm) {
                    $error = 'Les deux mots de passe saisis ne correspondent pas.';
                } else {
                    // Traitement du logo uploadé si présent
                    $logoPath = null;
                    /** @var UploadedFile|null $logoFile */
                    $logoFile = $request->files->get('logo');
                    if ($logoFile instanceof UploadedFile && $logoFile->isValid()) {
                        $allowedMimes = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
                        if (in_array($logoFile->getMimeType(), $allowedMimes, true) && $logoFile->getSize() <= 4 * 1024 * 1024) {
                            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/settings';
                            if (!is_dir($uploadsDir)) {
                                mkdir($uploadsDir, 0755, true);
                            }
                            $ext = $logoFile->guessExtension() ?: 'png';
                            $filename = 'logo_' . uniqid() . '.' . $ext;
                            $logoFile->move($uploadsDir, $filename);
                            $logoPath = 'uploads/settings/' . $filename;
                        }
                    }

                    // 1. Création de l'Institution principale
                    $institution = new Institution();
                    $institution->setNom($orgName);
                    $institution->setCode(strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $orgData['shortName'] ?? $orgName), 0, 8)));
                    $institution->setEmail($orgData['email'] ?? null);
                    $institution->setTelephone($orgData['phone'] ?? null);
                    $institution->setAdresse($orgData['address'] ?? null);
                    $institution->setSiteWeb($orgData['website'] ?? null);
                    $institution->setDescription($orgData['description'] ?? null);
                    if ($logoPath) {
                        $institution->setLogo($logoPath);
                    }
                    $entityManager->persist($institution);

                    // 2. Création du premier compte Administrateur Principal
                    $admin = new User();
                    $admin->setFirstname($adminFirstname ?: 'Administrateur');
                    $admin->setLastname($adminLastname ?: 'Principal');
                    $admin->setEmail($adminEmail);
                    $admin->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);
                    $admin->setInstitution($institution);
                    $admin->setIsVerified(true);
                    $admin->setStatus('ACTIVE');

                    $hashedPassword = $passwordHasher->hashPassword($admin, $adminPassword);
                    $admin->setPassword($hashedPassword);
                    $entityManager->persist($admin);

                    // 3. Création et verrouillage de la Configuration Centrale
                    $setting = $organizationContextService->getSettings();
                    $setting->setName($orgName);
                    $setting->setShortName($orgData['shortName'] ?? null);
                    $setting->setAppName($appName);
                    $setting->setOrganizationType($orgData['organizationType'] ?? OrganizationSetting::TYPE_PUBLIC_ADMIN);
                    $setting->setDescription($orgData['description'] ?? null);
                    $setting->setAddress($orgData['address'] ?? null);
                    $setting->setCity($orgData['city'] ?? null);
                    $setting->setCountry($orgData['country'] ?? null);
                    $setting->setEmail($orgData['email'] ?? null);
                    $setting->setPhone($orgData['phone'] ?? null);
                    $setting->setWebsite($orgData['website'] ?? null);

                    if ($logoPath) {
                        $setting->setLogoPath($logoPath);
                    }
                    if (!empty($orgData['primaryColor'])) {
                        $setting->setPrimaryColor($orgData['primaryColor']);
                    }
                    if (!empty($orgData['secondaryColor'])) {
                        $setting->setSecondaryColor($orgData['secondaryColor']);
                    }
                    if (!empty($orgData['accentColor'])) {
                        $setting->setAccentColor($orgData['accentColor']);
                    }

                    $setting->setLocale($orgData['locale'] ?? 'fr');
                    $setting->setTimezone($orgData['timezone'] ?? 'Africa/Ndjamena');
                    $setting->setCurrency($orgData['currency'] ?? 'XAF');
                    $setting->setDateFormat($orgData['dateFormat'] ?? 'd/m/Y');
                    $setting->setTimeFormat($orgData['timeFormat'] ?? 'H:i');

                    // Verrouillage de l'installation
                    $setting->setIsInstalled(true);
                    $setting->setInstalledAt(new \DateTimeImmutable());

                    $entityManager->persist($setting);
                    $entityManager->flush();

                    $organizationContextService->resetCache();

                    $this->addFlash('success', 'Félicitations ! Votre environnement ERP a été configuré avec succès. Vous pouvez maintenant vous connecter avec vos identifiants administrateur.');
                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('install/index.html.twig', [
            'error' => $error,
        ]);
    }
}
