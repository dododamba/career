<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001202125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, employe_id INT NOT NULL, type_absence_id INT NOT NULL, document_id INT DEFAULT NULL, acte_id INT DEFAULT NULL, approuve_par_id INT DEFAULT NULL, cree_par_id INT DEFAULT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, heure_debut TIME DEFAULT NULL, heure_fin TIME DEFAULT NULL, duree NUMERIC(6, 2) NOT NULL, unite VARCHAR(20) DEFAULT \'JOURS\' NOT NULL, motif LONGTEXT NOT NULL, statut SMALLINT DEFAULT 0 NOT NULL, date_decision DATETIME DEFAULT NULL, commentaire_decision LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_765AE0C930FCF5AA (type_absence_id), INDEX IDX_765AE0C9C33F7837 (document_id), INDEX IDX_765AE0C9A767B8C7 (acte_id), INDEX IDX_765AE0C95ED9CBB3 (approuve_par_id), INDEX IDX_765AE0C9FC29C013 (cree_par_id), INDEX idx_absence_statut (statut), INDEX idx_absence_dates (date_debut, date_fin), INDEX idx_absence_institution (institution_id), INDEX idx_absence_employe (employe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE absence_log (id INT AUTO_INCREMENT NOT NULL, absence_id INT NOT NULL, user_id INT DEFAULT NULL, action VARCHAR(50) NOT NULL, action_label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_99C161C42DFF238F (absence_id), INDEX IDX_99C161C4A76ED395 (user_id), INDEX idx_absence_log_action (action), INDEX idx_absence_log_created_at (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_absence (id INT AUTO_INCREMENT NOT NULL, institution_id INT DEFAULT NULL, code VARCHAR(50) NOT NULL, libelle VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, unite VARCHAR(20) DEFAULT \'JOURS\' NOT NULL, duree_max NUMERIC(6, 2) DEFAULT NULL, quota_annuel_max NUMERIC(6, 2) DEFAULT NULL, justificatif_requis TINYINT(1) DEFAULT 0 NOT NULL, remuneree TINYINT(1) DEFAULT 1 NOT NULL, couleur VARCHAR(7) DEFAULT \'#17a2b8\', actif TINYINT(1) DEFAULT 1 NOT NULL, INDEX idx_type_absence_code (code), INDEX idx_type_absence_institution (institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C910405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C91B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C930FCF5AA FOREIGN KEY (type_absence_id) REFERENCES type_absence (id)');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9C33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9A767B8C7 FOREIGN KEY (acte_id) REFERENCES acte (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C95ED9CBB3 FOREIGN KEY (approuve_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9FC29C013 FOREIGN KEY (cree_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE absence_log ADD CONSTRAINT FK_99C161C42DFF238F FOREIGN KEY (absence_id) REFERENCES absence (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE absence_log ADD CONSTRAINT FK_99C161C4A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE type_absence ADD CONSTRAINT FK_5E7B8F3C10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');

        // Types d'absence de référence (système global, institution_id = NULL)
        $this->addSql("INSERT INTO type_absence (institution_id, code, libelle, description, unite, duree_max, quota_annuel_max, justificatif_requis, remuneree, couleur, actif) VALUES 
            (NULL, 'CONCOURS_EXAMEN', 'Concours ou examen professionnel', 'Participation à des épreuves de concours ou examens professionnels officiels', 'JOURS', 5.00, 10.00, 1, 1, '#4e73df', 1),
            (NULL, 'CONSULTATION_MEDICALE', 'Consultation médicale ponctuelle', 'Consultation ou examen médical spécialisé ne justifiant pas d\'arrêt de travail', 'HEURES', 4.00, 20.00, 1, 1, '#1cc88a', 1),
            (NULL, 'EVENEMENT_FAMILIAL', 'Événement familial ponctuel', 'Absence exceptionnelle pour événement familial (mariage, naissance, décès)', 'JOURS', 3.00, 15.00, 1, 1, '#36b9cc', 1),
            (NULL, 'CONVENANCE_PERSONNELLE', 'Convenance personnelle', 'Autorisation d\'absence pour convenance personnelle (non rémunérée)', 'JOURS', 3.00, 10.00, 0, 0, '#f6c23e', 1),
            (NULL, 'FORMATION_COURTE', 'Formation ou séminaire de courte durée', 'Participation à une formation continue ponctuelle ou séminaire professionnel', 'JOURS', 5.00, 15.00, 1, 1, '#6f42c1', 1),
            (NULL, 'ACTIVITE_SYNDICALE', 'Activité ou représentation syndicale', 'Exercice d\'un mandat ou participation à des réunions syndicales statutaires', 'JOURS', 2.00, 10.00, 1, 1, '#e74a3b', 1),
            (NULL, 'AUTRE', 'Autre autorisation d\'absence', 'Autre motif exceptionnel soumis à appréciation de l\'autorité hiérarchique', 'JOURS', 3.00, 10.00, 0, 1, '#858796', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C910405986');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C91B65292');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C930FCF5AA');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9C33F7837');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9A767B8C7');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C95ED9CBB3');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9FC29C013');
        $this->addSql('ALTER TABLE absence_log DROP FOREIGN KEY FK_99C161C42DFF238F');
        $this->addSql('ALTER TABLE absence_log DROP FOREIGN KEY FK_99C161C4A76ED395');
        $this->addSql('ALTER TABLE type_absence DROP FOREIGN KEY FK_5E7B8F3C10405986');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE absence_log');
        $this->addSql('DROP TABLE type_absence');
    }
}
