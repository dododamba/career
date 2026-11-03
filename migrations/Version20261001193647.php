<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001193647 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 09: Upgrade Conges, SoldeConge, TypeConges with multi-tenancy, workflow tracking and default leave types';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3A1B65292');
        $this->addSql('ALTER TABLE conges ADD document_id INT DEFAULT NULL, ADD acte_id INT DEFAULT NULL, ADD approuve_par_id INT DEFAULT NULL, ADD commentaire_refus LONGTEXT DEFAULT NULL, ADD date_approbation DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3AC33F7837 FOREIGN KEY (document_id) REFERENCES document (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3AA767B8C7 FOREIGN KEY (acte_id) REFERENCES acte (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3A5ED9CBB3 FOREIGN KEY (approuve_par_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3A1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_6327DE3AC33F7837 ON conges (document_id)');
        $this->addSql('CREATE INDEX IDX_6327DE3AA767B8C7 ON conges (acte_id)');
        $this->addSql('CREATE INDEX IDX_6327DE3A5ED9CBB3 ON conges (approuve_par_id)');
        $this->addSql('CREATE INDEX idx_conges_statut ON conges (statut)');
        $this->addSql('CREATE INDEX idx_conges_dates ON conges (date_debut, date_fin)');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB271B65292');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB27753BDA5');
        $this->addSql('ALTER TABLE solde_conge ADD institution_id INT DEFAULT NULL, ADD annee INT DEFAULT NULL, ADD solde_acquis NUMERIC(10, 2) DEFAULT \'30.00\' NOT NULL, ADD solde_pris NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB2710405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB271B65292 FOREIGN KEY (employe_id) REFERENCES employe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB27753BDA5 FOREIGN KEY (type_conge_id) REFERENCES type_conges (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_solde_conge_institution ON solde_conge (institution_id)');
        $this->addSql('CREATE INDEX idx_solde_conge_annee ON solde_conge (annee)');
        $this->addSql('ALTER TABLE type_conges ADD institution_id INT DEFAULT NULL, ADD code VARCHAR(50) DEFAULT NULL, ADD jours_par_an INT DEFAULT 30 NOT NULL, ADD justificatif_requis TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE type_conges ADD CONSTRAINT FK_40F0B50210405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_type_conges_code ON type_conges (code)');
        $this->addSql('CREATE INDEX idx_type_conges_institution ON type_conges (institution_id)');

        // Seed global reference leave types
        $this->addSql("INSERT INTO type_conges (libelle, code, jours_par_an, justificatif_requis, is_deductible, is_active, couleur, institution_id) VALUES
            ('Congé Annuel', 'ANNUEL', 30, 0, 1, 1, '#0d6efd', NULL),
            ('Congé de Maladie', 'MALADIE', 90, 1, 0, 1, '#dc3545', NULL),
            ('Congé de Maternité', 'MATERNITE', 98, 1, 0, 1, '#d63384', NULL),
            ('Congé de Paternité', 'PATERNITE', 14, 1, 0, 1, '#0dcaf0', NULL),
            ('Événement Familial', 'EVENEMENT_FAMILIAL', 10, 1, 0, 1, '#ffc107', NULL),
            ('Congé Sans Solde', 'SANS_SOLDE', 365, 0, 0, 1, '#6c757d', NULL),
            ('Récupération', 'RECUPERATION', 15, 0, 1, 1, '#198754', NULL),
            ('Autre Absence Autorisée', 'AUTRE', 30, 0, 0, 1, '#20c997', NULL)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3AC33F7837');
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3AA767B8C7');
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3A5ED9CBB3');
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3A1B65292');
        $this->addSql('DROP INDEX IDX_6327DE3AC33F7837 ON conges');
        $this->addSql('DROP INDEX IDX_6327DE3AA767B8C7 ON conges');
        $this->addSql('DROP INDEX IDX_6327DE3A5ED9CBB3 ON conges');
        $this->addSql('DROP INDEX idx_conges_statut ON conges');
        $this->addSql('DROP INDEX idx_conges_dates ON conges');
        $this->addSql('ALTER TABLE conges DROP document_id, DROP acte_id, DROP approuve_par_id, DROP commentaire_refus, DROP date_approbation');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3A1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB2710405986');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB271B65292');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB27753BDA5');
        $this->addSql('DROP INDEX idx_solde_conge_institution ON solde_conge');
        $this->addSql('DROP INDEX idx_solde_conge_annee ON solde_conge');
        $this->addSql('ALTER TABLE solde_conge DROP institution_id, DROP annee, DROP solde_acquis, DROP solde_pris');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB271B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB27753BDA5 FOREIGN KEY (type_conge_id) REFERENCES type_conges (id)');
        $this->addSql('ALTER TABLE type_conges DROP FOREIGN KEY FK_40F0B50210405986');
        $this->addSql('DROP INDEX idx_type_conges_code ON type_conges');
        $this->addSql('DROP INDEX idx_type_conges_institution ON type_conges');
        $this->addSql('ALTER TABLE type_conges DROP institution_id, DROP code, DROP jours_par_an, DROP justificatif_requis');
    }
}
