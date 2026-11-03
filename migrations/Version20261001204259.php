<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001204259 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mission (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, chef_mission_id INT DEFAULT NULL, acte_id INT DEFAULT NULL, document_rapport_id INT DEFAULT NULL, valide_par_id INT DEFAULT NULL, cree_par_id INT DEFAULT NULL, reference VARCHAR(50) NOT NULL, objet VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, destination VARCHAR(255) NOT NULL, pays VARCHAR(100) DEFAULT \'Côte d\'\'Ivoire\' NOT NULL, lieu_precis VARCHAR(255) DEFAULT NULL, itineraire LONGTEXT DEFAULT NULL, mode_transport VARCHAR(50) DEFAULT \'VEHICULE_SERVICE\' NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, duree_jours INT DEFAULT 1 NOT NULL, budget_estime NUMERIC(12, 2) DEFAULT NULL, imputation_budgetaire VARCHAR(100) DEFAULT NULL, prise_en_charge VARCHAR(50) DEFAULT \'TOTALEMENT_INSTITUTION\' NOT NULL, statut SMALLINT DEFAULT 0 NOT NULL, rapport_texte LONGTEXT DEFAULT NULL, date_rapport DATETIME DEFAULT NULL, date_validation DATETIME DEFAULT NULL, commentaire_validation LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9067F23C5907F2C3 (chef_mission_id), INDEX IDX_9067F23CA767B8C7 (acte_id), INDEX IDX_9067F23CBC386710 (document_rapport_id), INDEX IDX_9067F23C6AF12ED9 (valide_par_id), INDEX IDX_9067F23CFC29C013 (cree_par_id), INDEX idx_mission_statut (statut), INDEX idx_mission_dates (date_debut, date_fin), INDEX idx_mission_institution (institution_id), INDEX idx_mission_reference (reference), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mission_log (id INT AUTO_INCREMENT NOT NULL, mission_id INT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, action_label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_145AFFBCBE6CAE90 (mission_id), INDEX IDX_145AFFBCA76ED395 (user_id), INDEX idx_mission_log_action (action), INDEX idx_mission_log_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE mission_participant (id INT AUTO_INCREMENT NOT NULL, mission_id INT NOT NULL, employe_id INT NOT NULL, acte_individuel_id INT DEFAULT NULL, role VARCHAR(50) DEFAULT \'PARTICIPANT\' NOT NULL, indemnite_journaliere NUMERIC(10, 2) DEFAULT NULL, frais_transport NUMERIC(10, 2) DEFAULT NULL, total_frais NUMERIC(10, 2) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B00021F0BE6CAE90 (mission_id), INDEX IDX_B00021F01B65292 (employe_id), INDEX IDX_B00021F0105F3AA4 (acte_individuel_id), INDEX idx_mission_participant_role (role), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C5907F2C3 FOREIGN KEY (chef_mission_id) REFERENCES employe (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23CA767B8C7 FOREIGN KEY (acte_id) REFERENCES acte (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23CBC386710 FOREIGN KEY (document_rapport_id) REFERENCES document (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C6AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23CFC29C013 FOREIGN KEY (cree_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission_log ADD CONSTRAINT FK_145AFFBCBE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_log ADD CONSTRAINT FK_145AFFBCA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE mission_participant ADD CONSTRAINT FK_B00021F0BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_participant ADD CONSTRAINT FK_B00021F01B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE mission_participant ADD CONSTRAINT FK_B00021F0105F3AA4 FOREIGN KEY (acte_individuel_id) REFERENCES acte (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C10405986');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C5907F2C3');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23CA767B8C7');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23CBC386710');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C6AF12ED9');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23CFC29C013');
        $this->addSql('ALTER TABLE mission_log DROP FOREIGN KEY FK_145AFFBCBE6CAE90');
        $this->addSql('ALTER TABLE mission_log DROP FOREIGN KEY FK_145AFFBCA76ED395');
        $this->addSql('ALTER TABLE mission_participant DROP FOREIGN KEY FK_B00021F0BE6CAE90');
        $this->addSql('ALTER TABLE mission_participant DROP FOREIGN KEY FK_B00021F01B65292');
        $this->addSql('ALTER TABLE mission_participant DROP FOREIGN KEY FK_B00021F0105F3AA4');
        $this->addSql('DROP TABLE mission');
        $this->addSql('DROP TABLE mission_log');
        $this->addSql('DROP TABLE mission_participant');
    }
}
