<?php
// Voici un script que vous pouvez utiliser pour vérifier et corriger les associations User-Employe
// Placez ceci dans un fichier de commande ou exécutez-le depuis un contrôleur avec la sécurité appropriée

namespace App\Command;

use App\Entity\User;
use App\Entity\Employe;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixUserEmployeRelationsCommand extends Command
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this->setName('app:fix-user-employe-relations')
            ->setDescription('Corrige les relations user-employe dans la base de données');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Début de la correction des relations User-Employe...');
        
        // 1. Trouver les Users sans Employe
        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();
        
        $usersWithoutEmploye = [];
        foreach ($users as $user) {
            if ($user->getEmploye() === null) {
                $usersWithoutEmploye[] = $user;
                $output->writeln('Utilisateur #' . $user->getId() . ' (' . $user->getEmail() . ') n\'a pas d\'employé');
            }
        }
        
        // 2. Trouver les Employes sans User
        $employeRepo = $this->entityManager->getRepository(Employe::class);
        $employes = $employeRepo->findAll();
        
        $employesWithoutUser = [];
        foreach ($employes as $employe) {
            if ($employe->getUser() === null) {
                $employesWithoutUser[] = $employe;
                $output->writeln('Employé #' . $employe->getId() . ' (' . $employe->getNom() . ' ' . $employe->getPrenom() . ') n\'a pas d\'utilisateur');
            }
        }
        
        // 3. Essayer de faire correspondre par email (champ commun)
        $matches = 0;
        foreach ($usersWithoutEmploye as $user) {
            foreach ($employesWithoutUser as $key => $employe) {
                if ($user->getEmail() === $employe->getEmail()) {
                    $output->writeln('Correspondance trouvée: Utilisateur #' . $user->getId() . ' avec Employé #' . $employe->getId() . ' par email');
                    
                    // Les associer
                    $user->setEmploye($employe);
                    $employe->setUser($user);
                    
                    // Supprimer de la liste "sans"
                    unset($employesWithoutUser[$key]);
                    $matches++;
                    break;
                }
            }
        }
        
        // 4. Essayer de faire correspondre par nom si disponible
        foreach ($usersWithoutEmploye as $user) {
            if (empty($employesWithoutUser)) break;
            
            $firstName = $user->getFirstname();
            $lastName = $user->getLastname();
            
            if ($firstName && $lastName) {
                foreach ($employesWithoutUser as $key => $employe) {
                    if (strtolower($firstName) === strtolower($employe->getPrenom()) && 
                        strtolower($lastName) === strtolower($employe->getNom())) {
                        $output->writeln('Correspondance trouvée: Utilisateur #' . $user->getId() . ' avec Employé #' . $employe->getId() . ' par nom');
                        
                        // Les associer
                        $user->setEmploye($employe);
                        $employe->setUser($user);
                        
                        // Supprimer de la liste "sans"
                        unset($employesWithoutUser[$key]);
                        $matches++;
                        break;
                    }
                }
            }
        }
        
        // 5. Enregistrer les modifications
        $this->entityManager->flush();
        
        $output->writeln('Correspondances trouvées et corrigées : ' . $matches . ' relations User-Employe');
        $output->writeln('Utilisateurs restants sans correspondance : ' . count($usersWithoutEmploye));
        $output->writeln('Employés restants sans correspondance : ' . count($employesWithoutUser));
        
        return Command::SUCCESS;
    }
}