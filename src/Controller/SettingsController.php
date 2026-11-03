<?php

namespace App\Controller;

use App\Entity\OrganizationSetting;
use App\Service\OrganizationContextService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'app_admin_settings', methods: ['GET', 'POST'])]
    public function index(Request $request, OrganizationContextService $contextService): Response
    {
        $setting = $contextService->getSettings();

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_csrf_token');
            if (!$this->isCsrfTokenValid('admin_settings', $token)) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_admin_settings');
            }

            $data = $request->request->all('settings');

            if (!empty($data['name'])) {
                $setting->setName(trim($data['name']));
            }
            $setting->setShortName(!empty($data['shortName']) ? trim($data['shortName']) : null);
            if (!empty($data['appName'])) {
                $setting->setAppName(trim($data['appName']));
            }
            $setting->setOrganizationType($data['organizationType'] ?? OrganizationSetting::TYPE_PUBLIC_ADMIN);
            $setting->setDescription(!empty($data['description']) ? trim($data['description']) : null);
            $setting->setAddress(!empty($data['address']) ? trim($data['address']) : null);
            $setting->setCity(!empty($data['city']) ? trim($data['city']) : null);
            $setting->setCountry(!empty($data['country']) ? trim($data['country']) : null);
            $setting->setEmail(!empty($data['email']) ? trim($data['email']) : null);
            $setting->setPhone(!empty($data['phone']) ? trim($data['phone']) : null);
            $setting->setWebsite(!empty($data['website']) ? trim($data['website']) : null);

            // Couleurs
            if (!empty($data['primaryColor'])) {
                $setting->setPrimaryColor($data['primaryColor']);
            }
            if (!empty($data['secondaryColor'])) {
                $setting->setSecondaryColor($data['secondaryColor']);
            }
            if (!empty($data['accentColor'])) {
                $setting->setAccentColor($data['accentColor']);
            }

            // Régionaux
            $setting->setLocale($data['locale'] ?? 'fr');
            $setting->setTimezone($data['timezone'] ?? 'Africa/Ndjamena');
            $setting->setCurrency($data['currency'] ?? 'XAF');
            $setting->setDateFormat($data['dateFormat'] ?? 'd/m/Y');
            $setting->setTimeFormat($data['timeFormat'] ?? 'H:i');

            // Documents
            $setting->setPdfHeader(!empty($data['pdfHeader']) ? trim($data['pdfHeader']) : null);
            $setting->setPdfFooter(!empty($data['pdfFooter']) ? trim($data['pdfFooter']) : null);

            // Modules activés
            $modulesRaw = $request->request->all('modules');
            $modules = [];
            foreach (array_keys(OrganizationSetting::DEFAULT_MODULES) as $moduleKey) {
                $modules[$moduleKey] = isset($modulesRaw[$moduleKey]) && $modulesRaw[$moduleKey] === '1';
            }
            // Au moins le module employé reste activé pour le bon fonctionnement RH
            $modules['employe'] = true;
            $setting->setEnabledModules($modules);

            // Gestion de l'upload du logo
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
                    $setting->setLogoPath('uploads/settings/' . $filename);
                }
            }

            $contextService->saveSettings($setting);

            $this->addFlash('success', 'Les paramètres de l\'organisation et l\'identité ERP ont été enregistrés avec succès.');
            return $this->redirectToRoute('app_admin_settings');
        }

        return $this->render('settings/index.html.twig', [
            'setting' => $setting,
        ]);
    }
}
