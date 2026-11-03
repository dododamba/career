<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001211406 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE interim_logs (id INT AUTO_INCREMENT NOT NULL, interim_id INT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, action_label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_A630EC4A76ED395 (user_id), INDEX idx_interim_log_interim (interim_id), INDEX idx_interim_log_action (action), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE interims (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, titulaire_id INT NOT NULL, interimaire_id INT NOT NULL, poste_id INT DEFAULT NULL, structure_id INT DEFAULT NULL, valide_par_id INT DEFAULT NULL, acte_id INT DEFAULT NULL, cree_par_id INT DEFAULT NULL, reference VARCHAR(50) NOT NULL, type_delegation VARCHAR(50) NOT NULL, perimetre JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', date_debut DATE NOT NULL, date_fin DATE NOT NULL, motif LONGTEXT NOT NULL, statut SMALLINT DEFAULT 0 NOT NULL, date_validation DATETIME DEFAULT NULL, commentaire_validation LONGTEXT DEFAULT NULL, date_fin_effective DATE DEFAULT NULL, motif_fin LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2F01497FA0905086 (poste_id), INDEX IDX_2F01497F2534008B (structure_id), INDEX IDX_2F01497F6AF12ED9 (valide_par_id), INDEX IDX_2F01497FA767B8C7 (acte_id), INDEX IDX_2F01497FFC29C013 (cree_par_id), INDEX idx_interim_institution (institution_id), INDEX idx_interim_titulaire (titulaire_id), INDEX idx_interim_interimaire (interimaire_id), INDEX idx_interim_statut (statut), INDEX idx_interim_date_debut (date_debut), INDEX idx_interim_date_fin (date_fin), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE interim_logs ADD CONSTRAINT FK_A630EC429C96BD8 FOREIGN KEY (interim_id) REFERENCES interims (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interim_logs ADD CONSTRAINT FK_A630EC4A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497F10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497FA10273AA FOREIGN KEY (titulaire_id) REFERENCES employe (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497F5CA839FE FOREIGN KEY (interimaire_id) REFERENCES employe (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497FA0905086 FOREIGN KEY (poste_id) REFERENCES poste (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497F2534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497F6AF12ED9 FOREIGN KEY (valide_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497FA767B8C7 FOREIGN KEY (acte_id) REFERENCES acte (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE interims ADD CONSTRAINT FK_2F01497FFC29C013 FOREIGN KEY (cree_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE interim_logs DROP FOREIGN KEY FK_A630EC429C96BD8');
        $this->addSql('ALTER TABLE interim_logs DROP FOREIGN KEY FK_A630EC4A76ED395');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497F10405986');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497FA10273AA');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497F5CA839FE');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497FA0905086');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497F2534008B');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497F6AF12ED9');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497FA767B8C7');
        $this->addSql('ALTER TABLE interims DROP FOREIGN KEY FK_2F01497FFC29C013');
        $this->addSql('DROP TABLE interim_logs');
        $this->addSql('DROP TABLE interims');
    }
}
