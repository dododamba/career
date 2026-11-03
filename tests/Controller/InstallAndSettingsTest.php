<?php

namespace App\Tests\Controller;

use App\Entity\Institution;
use App\Entity\OrganizationSetting;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InstallAndSettingsTest extends WebTestCase
{
    private function createAdminContext(EntityManagerInterface $em): array
    {
        $institution = new Institution();
        $institution->setNom('Test ERP Institution ' . uniqid());
        $institution->setCreatedAt(new \DateTimeImmutable());
        $em->persist($institution);

        $adminUser = new User();
        $adminUser->setEmail('erp_admin_' . uniqid() . '@example.com');
        $adminUser->setPassword('$2y$13$dummyhashedpasswordfortest1234567890');
        $adminUser->setRoles(['ROLE_ADMIN']);
        $adminUser->setFirstname('Admin');
        $adminUser->setLastname('ERP');
        $adminUser->setCreatedAt(new \DateTimeImmutable());
        $adminUser->setInstitution($institution);
        $em->persist($adminUser);

        $em->flush();

        return [$institution, $adminUser];
    }

    public function testInstallRouteRedirectsWhenInstalled(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        // Ensure an installed setting exists
        $repo = $em->getRepository(OrganizationSetting::class);
        $setting = $repo->findOneBy([]);
        if (!$setting) {
            $setting = new OrganizationSetting();
            $setting->setName('Org Test');
            $setting->setIsInstalled(true);
            $em->persist($setting);
            $em->flush();
        } else {
            $setting->setIsInstalled(true);
            $em->flush();
        }

        $client->request('GET', '/install');

        // Should redirect to login when installed
        $this->assertResponseRedirects('/login');
    }

    public function testSettingsAccessDeniedForAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin/settings');

        // Redirects to /login
        $this->assertResponseRedirects('/login');
    }

    public function testSettingsAccessibleForAdmin(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        [$institution, $adminUser] = $this->createAdminContext($em);

        $client->loginUser($adminUser);

        $crawler = $client->request('GET', '/admin/settings');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Paramètres & Identité de l\'Organisation');

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('Identité Visuelle', $content);
        $this->assertStringContainsString('Paramètres Régionaux', $content);
        $this->assertStringContainsString('Modules ERP', $content);
        $this->assertStringContainsString('Documents & Impressions', $content);

        // Cleanup
        $userToRemove = $em->find(User::class, $adminUser->getId());
        if ($userToRemove) {
            $em->remove($userToRemove);
        }
        $instToRemove = $em->find(Institution::class, $institution->getId());
        if ($instToRemove) {
            $em->remove($instToRemove);
        }
        $em->flush();
    }

    public function testSaveSettingsUpdatesConfiguration(): void
    {
        $client = static::createClient();
        /** @var EntityManagerInterface $em */
        $em = static::getContainer()->get('doctrine.orm.entity_manager');

        [$institution, $adminUser] = $this->createAdminContext($em);

        $client->loginUser($adminUser);

        // Fetch settings page to get CSRF token
        $crawler = $client->request('GET', '/admin/settings');
        $this->assertResponseIsSuccessful();

        $token = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        // Submit modified settings
        $uniqueAppName = 'ERP Nova ' . uniqid();
        $client->request('POST', '/admin/settings', [
            '_csrf_token' => $token,
            'settings' => [
                'appName' => $uniqueAppName,
                'name' => 'Acme Corporation International',
                'shortName' => 'Acme Corp',
                'organizationType' => 'ENTREPRISE_PRIVEE',
                'email' => 'contact@acme-corp.test',
                'phone' => '+33 1 23 45 67 89',
                'address' => '42 Avenue de l\'Innovation',
                'city' => 'Paris',
                'country' => 'France',
                'website' => 'https://acme-corp.test',
                'primaryColor' => '#1e3a8a',
                'secondaryColor' => '#0f172a',
                'accentColor' => '#f59e0b',
                'locale' => 'fr',
                'timezone' => 'Europe/Paris',
                'currency' => 'EUR',
                'dateFormat' => 'd/m/Y',
                'timeFormat' => 'H:i',
            ],
            'modules' => [
                'conges' => '1',
                'pointages' => '1',
                'missions' => '1',
            ],
        ]);

        $this->assertResponseRedirects('/admin/settings');
        $client->followRedirect();

        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString('enregistrés avec succès', $content);
        $this->assertStringContainsString($uniqueAppName, $content);

        // Cleanup
        $userToRemove = $em->find(User::class, $adminUser->getId());
        if ($userToRemove) {
            $em->remove($userToRemove);
        }
        $instToRemove = $em->find(Institution::class, $institution->getId());
        if ($instToRemove) {
            $em->remove($instToRemove);
        }
        $em->flush();
    }
}
