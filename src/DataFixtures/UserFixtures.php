<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Institution;
use App\Entity\Employe;
use App\Entity\Service;
use App\Entity\Fonction;
use App\Entity\Pointage;
use App\Entity\TypeConges;
use App\Entity\SoldeConge;
use App\Entity\Conges;
use App\Entity\CongesLog;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Créer des institutions
        $institutions = [];
        $institutionNames = [
            'Ministère de la Santé',
            'Université de N\'Djamena',
            'Hôpital Central de N\'Djamena',
            'Direction des Ressources Humaines',
            'Ministère de l\'Éducation',
            'Centre Hospitalier Régional'
        ];

        foreach ($institutionNames as $index => $name) {
            $institution = $manager->getRepository(Institution::class)->findOneBy(['nom' => $name]);
            if (!$institution) {
                $institution = new Institution();
                $institution->setNom($name);
                $institution->setDescription('Description de ' . $name . ' - Institution spécialisée dans son domaine.');
                $manager->persist($institution);
            }
            $institutions[] = $institution;
        }

        // 2. Créer des utilisateurs avec des institutions assignées
        $users = [];
        
        // Utilisateur principal (admin)
        $adminUser = $manager->getRepository(User::class)->findOneBy(['email' => 'dominiquedamba@fake.io']);
        if (!$adminUser) {
            $adminUser = new User();
            $adminUser->setEmail('dominiquedamba@fake.io');
            $adminUser->setFirstname('Dominique');
            $adminUser->setLastname('Damba');
            $adminUser->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);
            $adminUser->setPassword($this->passwordHasher->hashPassword($adminUser, '@password'));
            $adminUser->setInstitution($institutions[0]);
            $adminUser->setIsVerified(true);
            $manager->persist($adminUser);
        } else {
            $adminUser->setRoles(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);
            $adminUser->setInstitution($institutions[0]);
            $adminUser->setIsVerified(true);
        }
        $users[] = $adminUser;

        // Créer des utilisateurs supplémentaires
        $prenoms = ['Jean', 'Marie', 'Paul', 'Claire', 'Ahmed', 'Fatima', 'Pierre', 'Amina', 'David', 'Sarah', 'Michel', 'Aïcha', 'François', 'Khadija', 'Nicolas'];
        $noms = ['Oumar', 'Hissein', 'Mahamat', 'Abakar', 'Saleh', 'Hassan', 'Ali', 'Abdel', 'Youssef', 'Ibrahim', 'Adam', 'Moussa', 'Idriss', 'Haroun', 'Zakaria'];
        
        for ($i = 0; $i < 15; $i++) {
            $prenom = $prenoms[$i % count($prenoms)];
            $nom = $noms[$i % count($noms)];
            $email = strtolower($prenom . '.' . $nom . ($i + 1) . '@fake.io');
            
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
            if (!$user) {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstname($prenom);
                $user->setLastname($nom);
                $user->setRoles(['ROLE_USER']);
                $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
                $user->setInstitution($institutions[$i % count($institutions)]);
                $user->setIsVerified($i % 5 !== 0);
                $manager->persist($user);
            }
            $users[] = $user;
        }

        // 3. Créer des fonctions
        $fonctions = [];
        $fonctionLibelles = [
            'Directeur Général',
            'Directeur Adjoint',
            'Chef de Service',
            'Manager',
            'Secrétaire',
            'Technicien',
            'Médecin',
            'Infirmier',
            'Comptable',
            'Agent RH',
            'Chauffeur',
            'Agent de sécurité'
        ];

        foreach ($fonctionLibelles as $libelle) {
            $fonction = $manager->getRepository(Fonction::class)->findOneBy(['libelle' => $libelle]);
            if (!$fonction) {
                $fonction = new Fonction();
                $fonction->setLibelle($libelle);
                $fonction->setDescription('Description du poste de ' . $libelle);
                $fonction->setSlug(strtolower(str_replace(' ', '-', $libelle)));
                $manager->persist($fonction);
            }
            $fonctions[] = $fonction;
        }

        // 4. Créer des services pour chaque institution
        $services = [];
        $serviceNoms = [
            'Administration',
            'Ressources Humaines',
            'Comptabilité',
            'Service Technique',
            'Service Médical',
            'Urgences',
            'Pédiatrie',
            'Chirurgie',
            'Maintenance',
            'Sécurité'
        ];

        foreach ($institutions as $index => $institution) {
            $nombreServices = 3 + ($index % 2);
            
            for ($i = 0; $i < $nombreServices; $i++) {
                $nom = $serviceNoms[($index * 3 + $i) % count($serviceNoms)];
                $service = $manager->getRepository(Service::class)->findOneBy(['nom' => $nom, 'institution' => $institution]);
                if (!$service) {
                    $service = new Service();
                    $service->setNom($nom);
                    $service->setDescription('Description du service ' . $nom . ' de ' . $institution->getNom());
                    $service->setInstitution($institution);
                    $manager->persist($service);
                }
                $services[] = $service;
            }
        }

        // 5. Créer des employés liés aux utilisateurs
        $telephones = ['22 11 11 11', '66 22 22 22', '77 33 33 33', '99 44 44 44'];
        $adresses = ['Avenue Charles de Gaulle, N\'Djamena', 'Quartier Chagoua, N\'Djamena', 'Avenue Mobutu, N\'Djamena', 'Quartier Ridina, N\'Djamena'];
        
        $employesCreated = [];
        $employeIndex = 0;
        
        foreach ($users as $userIndex => $user) {
            $nombreEmployes = 1 + ($userIndex % 2);
            
            for ($j = 0; $j < $nombreEmployes; $j++) {
                $matricule = 'EMP' . str_pad($userIndex * 10 + $j + 1, 4, '0', STR_PAD_LEFT);
                $employe = $manager->getRepository(Employe::class)->findOneBy(['matricule' => $matricule]);
                
                if (!$employe) {
                    $employe = new Employe();
                    if ($j === 0) {
                        $employe->setNom($user->getLastname());
                        $employe->setPrenom($user->getFirstname());
                        $employe->setEmail($user->getEmail());
                    } else {
                        $employe->setNom('Nom' . ($userIndex + $j));
                        $employe->setPrenom('Prenom' . ($userIndex + $j));
                        $employe->setEmail('employe' . ($userIndex + $j) . '@fake.io');
                    }
                    
                    $employe->setUser($user);
                    $employe->setDateNaissance(new \DateTime('1980-01-01'));
                    $employe->setNumeroTelephone($telephones[$userIndex % count($telephones)]);
                    $employe->setSalaire(500000 + ($userIndex * 50000));
                    $employe->setStatut(true);
                    $employe->setDateEmbauche(new \DateTime('2020-01-01'));
                    $employe->setAdresse($adresses[$userIndex % count($adresses)]);
                    $employe->setMatricule($matricule);
                    $employe->setNumero($userIndex * 10 + $j + 1);
                    $employe->setActif($userIndex % 10 !== 0);
                    
                    $employe->setFonction($fonctions[$userIndex % count($fonctions)]);
                    
                    $servicesInstitution = array_filter($services, function($s) use ($user) {
                        return $s->getInstitution() === $user->getInstitution();
                    });
                    
                    if (!empty($servicesInstitution)) {
                        $servicesList = array_values($servicesInstitution);
                        $employe->setService($servicesList[$userIndex % count($servicesList)]);
                    }
                    
                    $manager->persist($employe);
                }
                
                $employesCreated[] = $employe;
                $employeIndex++;
            }
        }

        // Flush pour sauvegarder les employés avant de créer les pointages
        $manager->flush();

        // 6. Créer des pointages pour les employés
        $statusOptions = ['Présent', 'Absent', 'En retard', 'Parti tôt'];
        $motifsAbsence = ['Maladie', 'Formation', 'Congé', 'Mission', 'Personnel'];
        $commentaires = [
            'Pointage normal',
            'Arrivé en retard - embouteillage',
            'Parti plus tôt - rendez-vous médical',
            'Journée complète de travail',
            'Formation externe',
            'Mission à l\'extérieur'
        ];

        $dateDebut = new \DateTime('-30 days');
        
        foreach ($employesCreated as $index => $employe) {
            if ($index >= 5) break;
            
            for ($jour = 0; $jour < 20; $jour++) {
                $datePointage = clone $dateDebut;
                $datePointage->modify('+' . $jour . ' days');
                
                if ($datePointage->format('N') >= 6) {
                    continue;
                }
                
                $pointage = new Pointage();
                $pointage->setDate($datePointage);
                $pointage->setDateEntree(clone $datePointage);
                $pointage->setEmploye($employe);
                
                $estPresent = ($jour % 7) !== 0;
                $pointage->setEstPresent($estPresent);
                
                if ($estPresent) {
                    $heureArrivee = clone $datePointage;
                    $heureBase = 7 + ($jour % 3);
                    $minutes = ($jour * 15) % 60;
                    $heureArrivee->setTime($heureBase, $minutes);
                    $pointage->setHeureArrivee($heureArrivee);
                    
                    $heureDepart = clone $heureArrivee;
                    $heureDepart->modify('+8 hours');
                    $heureDepart->modify('+' . (($jour * 10) % 60) . ' minutes');
                    $pointage->setHeureDepart($heureDepart);
                    
                    $dateSortie = clone $pointage->getDateEntree();
                    $dateSortie->setTime($heureDepart->format('H'), $heureDepart->format('i'));
                    $pointage->setDateSortie($dateSortie);
                    
                    if ($heureBase <= 8) {
                        $pointage->setStatus('Présent');
                    } else {
                        $pointage->setStatus('En retard');
                    }
                    
                    $pointage->setCommentaire($commentaires[$jour % count($commentaires)]);
                } else {
                    $pointage->setStatus('Absent');
                    $pointage->setMotifAbsence($motifsAbsence[$jour % count($motifsAbsence)]);
                    $pointage->setCommentaire('Absence justifiée');
                }
                
                $manager->persist($pointage);
            }
        }

        // 7. Créer quelques pointages pour aujourd'hui
        $aujourd = new \DateTime('today');
        for ($i = 0; $i < min(3, count($employesCreated)); $i++) {
            $employe = $employesCreated[$i];
            
            $pointageAujourd = new Pointage();
            $pointageAujourd->setDate($aujourd);
            $pointageAujourd->setDateEntree(new \DateTime());
            $pointageAujourd->setEmploye($employe);
            $pointageAujourd->setEstPresent(true);
            
            $heureArrivee = new \DateTime('today 08:' . (15 * $i) . ':00');
            $pointageAujourd->setHeureArrivee($heureArrivee);
            $pointageAujourd->setStatus($i === 0 ? 'Présent' : 'En retard');
            $pointageAujourd->setCommentaire('Pointage du jour');
            
            $manager->persist($pointageAujourd);
        }

        // 8. Créer des types de congés
        $typesConges = [];
        $typesCongesData = [
            ['libelle' => 'Congés payés', 'description' => 'Congés annuels payés', 'isDeductible' => true, 'couleur' => '#28a745', 'isActive' => true],
            ['libelle' => 'Congé maladie', 'description' => 'Congé pour raisons médicales', 'isDeductible' => false, 'couleur' => '#dc3545', 'isActive' => true],
            ['libelle' => 'Congé maternité', 'description' => 'Congé de maternité', 'isDeductible' => false, 'couleur' => '#fd7e14', 'isActive' => true],
            ['libelle' => 'Congé paternité', 'description' => 'Congé de paternité', 'isDeductible' => false, 'couleur' => '#20c997', 'isActive' => true],
            ['libelle' => 'Congé sans solde', 'description' => 'Congé sans rémunération', 'isDeductible' => true, 'couleur' => '#6c757d', 'isActive' => true],
            ['libelle' => 'Formation', 'description' => 'Congé de formation professionnelle', 'isDeductible' => false, 'couleur' => '#007bff', 'isActive' => true],
            ['libelle' => 'Mission', 'description' => 'Mission professionnelle', 'isDeductible' => false, 'couleur' => '#17a2b8', 'isActive' => true],
        ];

        foreach ($typesCongesData as $typeData) {
            $typeConge = $manager->getRepository(TypeConges::class)->findOneBy(['libelle' => $typeData['libelle']]);
            if (!$typeConge) {
                $typeConge = new TypeConges();
                $typeConge->setLibelle($typeData['libelle']);
                $typeConge->setDescription($typeData['description']);
                $typeConge->setIsDeductible($typeData['isDeductible']);
                $typeConge->setCouleur($typeData['couleur']);
                $typeConge->setIsActive($typeData['isActive']);
                $manager->persist($typeConge);
            }
            $typesConges[] = $typeConge;
        }

        // 9. Créer des soldes de congés pour chaque employé
        foreach ($employesCreated as $employe) {
            foreach ($typesConges as $typeConge) {
                if ($typeConge->isIsDeductible()) {
                    $existingSolde = $manager->getRepository(SoldeConge::class)->findOneBy(['employe' => $employe, 'typeConge' => $typeConge]);
                    if (!$existingSolde) {
                        $soldeConge = new SoldeConge();
                        $soldeConge->setEmploye($employe);
                        $soldeConge->setTypeConge($typeConge);
                        
                        if ($typeConge->getLibelle() === 'Congés payés') {
                            $soldeConge->setSolde('25.00');
                        } else {
                            $soldeConge->setSolde('10.00');
                        }
                        
                        $soldeConge->setUpdatedAt(new \DateTime());
                        $manager->persist($soldeConge);
                    }
                }
            }
        }

        // 10. Créer quelques demandes de congés
        $statusConges = [0, 1, 2];
        $motifs = [
            'Congés d\'été en famille',
            'Repos médical prescrit',
            'Voyage personnel',
            'Événement familial',
            'Formation continue',
            'Congé sans solde personnel',
            'Mission à l\'étranger'
        ];

        for ($i = 0; $i < 20; $i++) {
            $conge = new Conges();
            
            $employe = $employesCreated[$i % count($employesCreated)];
            $conge->setEmploye($employe);
            
            $typeConge = $typesConges[$i % count($typesConges)];
            $conge->setTypeConge($typeConge);
            
            $dateDebutConge = new \DateTime();
            $dateDebutConge->modify('-' . rand(0, 300) . ' days');
            $dateDebutConge->modify('+' . rand(1, 60) . ' days');
            
            $duree = rand(1, 15);
            $dateFinConge = clone $dateDebutConge;
            $dateFinConge->modify('+' . $duree . ' days');
            
            $conge->setDateDebut($dateDebutConge);
            $conge->setDateFin($dateFinConge);
            $conge->setDateDiff($duree);
            $conge->setMotif($motifs[$i % count($motifs)]);
            $conge->setType($typeConge->getLibelle());
            
            if ($i < 3) {
                $statut = 0;
            } else {
                if (rand(1, 10) <= 6) $statut = 1;
                else if (rand(1, 10) <= 3) $statut = 0;
                else $statut = 2;
            }
            $conge->setStatut($statut);
            
            $createdAt = clone $dateDebutConge;
            $createdAt->modify('-' . rand(7, 30) . ' days');
            $conge->setCreatedAt($createdAt);
            
            $updatedAt = clone $createdAt;
            if ($statut !== 0) {
                $updatedAt->modify('+' . rand(1, 7) . ' days');
            }
            $conge->setUpdatedAt($updatedAt);
            
            $manager->persist($conge);
            
            // 11. Créer des logs pour chaque demande de congé
            $logCreation = new CongesLog();
            $logCreation->setConges($conge);
            $logCreation->setAction('create');
            $logCreation->setActionLabel('Demande créée');
            $logCreation->setDescription('Nouvelle demande de congé soumise');
            $logCreation->setUser($employe->getUser());
            $logCreation->setCreatedAt($createdAt);
            
            $manager->persist($logCreation);
            
            if ($statut !== 0) {
                $logTraitement = new CongesLog();
                $logTraitement->setConges($conge);
                
                if ($statut === 1) {
                    $logTraitement->setAction('approve');
                    $logTraitement->setActionLabel('Demande approuvée');
                    $logTraitement->setDescription('La demande de congé a été approuvée par le manager');
                } else {
                    $logTraitement->setAction('reject');
                    $logTraitement->setActionLabel('Demande rejetée');
                    $logTraitement->setDescription('La demande de congé a été rejetée - planification insuffisante');
                }
                
                $logTraitement->setUser($users[0]);
                $logTraitement->setCreatedAt($updatedAt);
                
                $manager->persist($logTraitement);
            }
        }

        // Flush final pour sauvegarder toutes les données
        $manager->flush();
    }
}