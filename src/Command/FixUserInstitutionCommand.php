<?php

namespace App\Command;

use App\Entity\User;
use App\Entity\Institution;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-user-institution',
    description: 'Corrige et vérifie les relations User-Institution et User-Employe'
)]
class FixUserInstitutionCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les actions sans les exécuter')
            ->addOption('create-institution', null, InputOption::VALUE_NONE, 'Créer une institution par défaut si nécessaire')
            ->addOption('fix-employes', null, InputOption::VALUE_NONE, 'Corriger aussi les relations avec les employés');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $createInstitution = $input->getOption('create-institution');
        $fixEmployes = $input->getOption('fix-employes');

        $io->title('Correction des relations User-Institution');

        if ($isDryRun) {
            $io->note('Mode dry-run activé : aucune modification ne sera effectuée');
        }

        // 1. Vérifier les institutions
        $this->checkInstitutions($io, $createInstitution, $isDryRun);

        // 2. Vérifier les utilisateurs sans institution
        $this->fixUsersWithoutInstitution($io, $isDryRun);

        // 3. Corriger les relations employés si demandé
        if ($fixEmployes) {
            $this->fixEmployeRelations($io, $isDryRun);
        }

        // 4. Statistiques finales
        $this->displayStatistics($io);

        $io->success('Vérification terminée !');
        return Command::SUCCESS;
    }

    private function checkInstitutions(SymfonyStyle $io, bool $createInstitution, bool $isDryRun): void
    {
        $io->section('Vérification des institutions');
        
        $institutions = $this->entityManager->getRepository(Institution::class)->findAll();
        
        if (empty($institutions)) {
            $io->warning('Aucune institution trouvée dans la base de données');
            
            if ($createInstitution) {
                if (!$isDryRun) {
                    $institution = new Institution();
                    $institution->setNom('Institution par défaut');
                    $institution->setDescription('Institution créée automatiquement');
                    $institution->setActif(true);
                    
                    $this->entityManager->persist($institution);
                    $this->entityManager->flush();
                    
                    $io->success('Institution par défaut créée');
                } else {
                    $io->text('→ Créerait une institution par défaut');
                }
            } else {
                $io->error('Utilisez --create-institution pour créer une institution par défaut');
                return;
            }
        } else {
            $io->success(sprintf('Trouvé %d institution(s)', count($institutions)));
            
            foreach ($institutions as $institution) {
                $userCount = $this->entityManager->getRepository(User::class)
                    ->count(['institution' => $institution]);
                
                $io->text(sprintf('- %s: %d utilisateur(s)', 
                    $institution->getNom(), $userCount));
            }
        }
    }

    private function fixUsersWithoutInstitution(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Vérification des utilisateurs sans institution');
        
        $usersWithoutInstitution = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.institution IS NULL')
            ->getQuery()
            ->getResult();
        
        if (empty($usersWithoutInstitution)) {
            $io->success('Tous les utilisateurs ont une institution assignée');
            return;
        }
        
        $io->warning(sprintf('Trouvé %d utilisateur(s) sans institution', count($usersWithoutInstitution)));
        
        // Prendre la première institution disponible
        $defaultInstitution = $this->entityManager->getRepository(Institution::class)
            ->findOneBy(['actif' => true]);
        
        if (!$defaultInstitution) {
            $io->error('Aucune institution active disponible pour assigner les utilisateurs');
            return;
        }
        
        $io->info(sprintf('Attribution à l\'institution: %s', $defaultInstitution->getNom()));
        
        foreach ($usersWithoutInstitution as $user) {
            if (!$isDryRun) {
                $user->setInstitution($defaultInstitution);
                $this->entityManager->persist($user);
            }
            
            $io->text(sprintf('→ %s (%s)', $user->getFullName(), $user->getEmail()));
        }
        
        if (!$isDryRun) {
            $this->entityManager->flush();
            $io->success(sprintf('%d utilisateur(s) assigné(s) à l\'institution', count($usersWithoutInstitution)));
        }
    }

    private function fixEmployeRelations(SymfonyStyle $io, bool $isDryRun): void
    {
        $io->section('Vérification des relations User-Employe');
        
        // Utilisateurs sans employé
        $usersWithoutEmploye = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.employes', 'e')
            ->where('e.id IS NULL')
            ->getQuery()
            ->getResult();
        
        if (!empty($usersWithoutEmploye)) {
            $io->warning(sprintf('%d utilisateur(s) sans employé associé', count($usersWithoutEmploye)));
            
            foreach ($usersWithoutEmploye as $user) {
                $io->text(sprintf('- %s (%s)', $user->getFullName(), $user->getEmail()));
            }
            
            if ($io->confirm('Créer des employés pour ces utilisateurs ?', false)) {
                foreach ($usersWithoutEmploye as $user) {
                    if (!$isDryRun) {
                        $employe = new Employe();
                        $employe->setNom($user->getLastname());
                        $employe->setPrenom($user->getFirstname());
                        $employe->setEmail($user->getEmail());
                        $employe->setUser($user);
                        $employe->setMatricule($this->generateMatricule($user->getInstitution()));
                        $employe->setStatut(true);
                        $employe->setActif(true);
                        $employe->setDateEmbauche(new \DateTime());
                        $employe->setSalaire(0);
                        
                        $this->entityManager->persist($employe);
                    }
                }
                
                if (!$isDryRun) {
                    $this->entityManager->flush();
                    $io->success('Employés créés avec succès');
                }
            }
        }
        
        // Employés sans utilisateur
        $employesSansUser = $this->entityManager->getRepository(Employe::class)
            ->createQueryBuilder('e')
            ->where('e.user IS NULL')
            ->getQuery()
            ->getResult();
        
        if (!empty($employesSansUser)) {
            $io->warning(sprintf('%d employé(s) sans utilisateur associé', count($employesSansUser)));
            
            foreach ($employesSansUser as $employe) {
                $io->text(sprintf('- %s (%s)', $employe->getNomComplet(), $employe->getEmail()));
            }
        }
    }

    private function displayStatistics(SymfonyStyle $io): void
    {
        $io->section('Statistiques finales');
        
        $stats = [
            'Institutions' => $this->entityManager->getRepository(Institution::class)->count([]),
            'Utilisateurs' => $this->entityManager->getRepository(User::class)->count([]),
            'Employés' => $this->entityManager->getRepository(Employe::class)->count([]),
        ];
        
        // Utilisateurs par institution
        $institutions = $this->entityManager->getRepository(Institution::class)->findAll();
        foreach ($institutions as $institution) {
            $userCount = $this->entityManager->getRepository(User::class)
                ->count(['institution' => $institution]);
            $stats[$institution->getNom()] = $userCount . ' utilisateur(s)';
        }
        
        $io->table(['Entité', 'Nombre'], array_map(
            fn($key, $value) => [$key, $value],
            array_keys($stats),
            array_values($stats)
        ));
    }

    private function generateMatricule(?Institution $institution): string
    {
        $prefix = $institution ? strtoupper(substr($institution->getNom(), 0, 3)) : 'EMP';
        $year = date('Y');
        $random = str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        
        return $prefix . $year . $random;
    }
}