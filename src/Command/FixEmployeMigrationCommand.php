<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-employe-migration',
    description: 'Corrige les problèmes de migration pour la relation Employe-User'
)]
class FixEmployeMigrationCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche les requêtes sans les exécuter')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force l\'exécution même si des erreurs surviennent');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $force = $input->getOption('force');

        $io->title('Correction de la migration Employe-User');

        if ($isDryRun) {
            $io->note('Mode dry-run: les requêtes seront affichées mais pas exécutées');
        }

        try {
            // 1. Vérifier la structure actuelle
            $io->section('1. Vérification de la structure actuelle');
            $this->checkCurrentStructure($io);

            // 2. Ajouter la colonne user_id si nécessaire
            $io->section('2. Ajout de la colonne user_id');
            $this->addUserIdColumn($io, $isDryRun);

            // 3. Créer l'index
            $io->section('3. Création de l\'index');
            $this->createUserIdIndex($io, $isDryRun);

            // 4. Ajouter la contrainte de clé étrangère
            $io->section('4. Ajout de la contrainte de clé étrangère');
            $this->addForeignKeyConstraint($io, $isDryRun);

            // 5. Nettoyer les anciennes contraintes institution
            $io->section('5. Nettoyage des anciennes contraintes');
            $this->cleanupOldConstraints($io, $isDryRun, $force);

            if (!$isDryRun) {
                $io->success('Migration corrigée avec succès!');
            } else {
                $io->info('Dry-run terminé. Exécutez sans --dry-run pour appliquer les changements.');
            }

        } catch (\Exception $e) {
            $io->error('Erreur lors de la correction: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function checkCurrentStructure(SymfonyStyle $io): void
    {
        $sql = "DESCRIBE employe";
        $columns = $this->connection->fetchAllAssociative($sql);
        
        $io->table(['Field', 'Type', 'Null', 'Key'], array_map(function($col) {
            return [$col['Field'], $col['Type'], $col['Null'], $col['Key']];
        }, $columns));

        // Vérifier les contraintes existantes
        $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'employe' 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
        
        $constraints = $this->connection->fetchAllAssociative($sql);
        
        if (!empty($constraints)) {
            $io->table(['Constraint', 'Column', 'Referenced Table'], array_map(function($const) {
                return [$const['CONSTRAINT_NAME'], $const['COLUMN_NAME'], $const['REFERENCED_TABLE_NAME']];
            }, $constraints));
        } else {
            $io->note('Aucune contrainte de clé étrangère trouvée');
        }
    }

    private function addUserIdColumn(SymfonyStyle $io, bool $isDryRun): void
    {
        // Vérifier si la colonne existe déjà
        $sql = "SELECT COUNT(*) as count FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'employe' 
                AND column_name = 'user_id'";
        
        $result = $this->connection->fetchAssociative($sql);
        
        if ($result['count'] > 0) {
            $io->note('La colonne user_id existe déjà');
            return;
        }

        $sql = "ALTER TABLE employe ADD COLUMN user_id INT DEFAULT NULL";
        
        if ($isDryRun) {
            $io->text("SQL: $sql");
        } else {
            $this->connection->executeStatement($sql);
            $io->success('Colonne user_id ajoutée');
        }
    }

    private function createUserIdIndex(SymfonyStyle $io, bool $isDryRun): void
    {
        // Vérifier si l'index existe déjà
        $sql = "SELECT COUNT(*) as count FROM information_schema.statistics 
                WHERE table_schema = DATABASE() 
                AND table_name = 'employe' 
                AND index_name = 'IDX_F804D3B9A76ED395'";
        
        $result = $this->connection->fetchAssociative($sql);
        
        if ($result['count'] > 0) {
            $io->note('L\'index IDX_F804D3B9A76ED395 existe déjà');
            return;
        }

        $sql = "CREATE INDEX IDX_F804D3B9A76ED395 ON employe (user_id)";
        
        if ($isDryRun) {
            $io->text("SQL: $sql");
        } else {
            $this->connection->executeStatement($sql);
            $io->success('Index IDX_F804D3B9A76ED395 créé');
        }
    }

    private function addForeignKeyConstraint(SymfonyStyle $io, bool $isDryRun): void
    {
        // Vérifier si la contrainte existe déjà
        $sql = "SELECT COUNT(*) as count FROM information_schema.key_column_usage 
                WHERE table_schema = DATABASE() 
                AND table_name = 'employe' 
                AND constraint_name = 'FK_F804D3B9A76ED395'";
        
        $result = $this->connection->fetchAssociative($sql);
        
        if ($result['count'] > 0) {
            $io->note('La contrainte FK_F804D3B9A76ED395 existe déjà');
            return;
        }

        $sql = "ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9A76ED395 
                FOREIGN KEY (user_id) REFERENCES `user` (id)";
        
        if ($isDryRun) {
            $io->text("SQL: $sql");
        } else {
            $this->connection->executeStatement($sql);
            $io->success('Contrainte FK_F804D3B9A76ED395 ajoutée');
        }
    }

    private function cleanupOldConstraints(SymfonyStyle $io, bool $isDryRun, bool $force): void
    {
        // Chercher les contraintes liées à institution
        $sql = "SELECT CONSTRAINT_NAME, COLUMN_NAME 
                FROM information_schema.key_column_usage 
                WHERE table_schema = DATABASE() 
                AND table_name = 'employe' 
                AND REFERENCED_TABLE_NAME = 'institution'";
        
        $constraints = $this->connection->fetchAllAssociative($sql);
        
        foreach ($constraints as $constraint) {
            $constraintName = $constraint['CONSTRAINT_NAME'];
            $columnName = $constraint['COLUMN_NAME'];
            
            $io->note("Contrainte trouvée: $constraintName pour la colonne $columnName");
            
            if ($force || $io->confirm("Supprimer la contrainte $constraintName ?", false)) {
                $sql = "ALTER TABLE employe DROP FOREIGN KEY $constraintName";
                
                if ($isDryRun) {
                    $io->text("SQL: $sql");
                } else {
                    try {
                        $this->connection->executeStatement($sql);
                        $io->success("Contrainte $constraintName supprimée");
                    } catch (\Exception $e) {
                        $io->warning("Impossible de supprimer la contrainte $constraintName: " . $e->getMessage());
                    }
                }
            }
        }

        // Supprimer la colonne institution_id si elle existe et si demandé
        $sql = "SELECT COUNT(*) as count FROM information_schema.columns 
                WHERE table_schema = DATABASE() 
                AND table_name = 'employe' 
                AND column_name = 'institution_id'";
        
        $result = $this->connection->fetchAssociative($sql);
        
        if ($result['count'] > 0) {
            if ($force || $io->confirm('Supprimer la colonne institution_id ?', false)) {
                $sql = "ALTER TABLE employe DROP COLUMN institution_id";
                
                if ($isDryRun) {
                    $io->text("SQL: $sql");
                } else {
                    try {
                        $this->connection->executeStatement($sql);
                        $io->success('Colonne institution_id supprimée');
                    } catch (\Exception $e) {
                        $io->warning('Impossible de supprimer la colonne institution_id: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}