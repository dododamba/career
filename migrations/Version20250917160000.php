<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conges (id INT AUTO_INCREMENT NOT NULL, employe_id INT NOT NULL, type_conge_id INT NOT NULL, date_debut DATE NOT NULL, date_fin DATE NOT NULL, date_diff INT NOT NULL, motif LONGTEXT DEFAULT NULL, statut SMALLINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, type VARCHAR(255) DEFAULT NULL, INDEX IDX_6327DE3A1B65292 (employe_id), INDEX IDX_6327DE3A753BDA5 (type_conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE conges_log (id INT AUTO_INCREMENT NOT NULL, conges_id INT NOT NULL, user_id INT NOT NULL, action VARCHAR(50) NOT NULL, action_label VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_D4ABF347F50CF8EF (conges_id), INDEX IDX_D4ABF347A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE employe (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, fonction_id INT DEFAULT NULL, service_id INT DEFAULT NULL, institution_id INT NOT NULL, nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, numero_telephone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, salaire DOUBLE PRECISION NOT NULL, statut TINYINT(1) DEFAULT NULL, date_embauche DATE DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, matricule VARCHAR(255) DEFAULT NULL, numero INT DEFAULT NULL, photo VARCHAR(255) DEFAULT NULL, actif TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_F804D3B9A76ED395 (user_id), INDEX IDX_F804D3B957889920 (fonction_id), INDEX IDX_F804D3B9ED5CA9E6 (service_id), INDEX IDX_F804D3B910405986 (institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fonction (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE institution (id INT AUTO_INCREMENT NOT NULL, type_institution_id INT DEFAULT NULL, nom VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3A9F98E546837012 (type_institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pointage (id INT AUTO_INCREMENT NOT NULL, employe_id INT DEFAULT NULL, date DATE NOT NULL, heure_arrivee TIME DEFAULT NULL, heure_depart TIME DEFAULT NULL, est_present TINYINT(1) NOT NULL, motif_absence VARCHAR(255) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, date_entree DATETIME NOT NULL, date_sortie DATETIME DEFAULT NULL, commentaire VARCHAR(1000) DEFAULT NULL, INDEX IDX_7591B201B65292 (employe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE presence (id INT AUTO_INCREMENT NOT NULL, employe_id INT DEFAULT NULL, date_presence DATE DEFAULT NULL, heure_entree TIME DEFAULT NULL, heure_sortie TIME DEFAULT NULL, statut TINYINT(1) DEFAULT NULL, UNIQUE INDEX UNIQ_6977C7A51B65292 (employe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE role (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', deleted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, nom VARCHAR(255) DEFAULT NULL, description LONGTEXT NOT NULL, INDEX IDX_E19D9AD210405986 (institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solde_conge (id INT AUTO_INCREMENT NOT NULL, employe_id INT NOT NULL, type_conge_id INT NOT NULL, solde NUMERIC(10, 2) NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_EF1BB271B65292 (employe_id), INDEX IDX_EF1BB27753BDA5 (type_conge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_conges (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_deductible TINYINT(1) NOT NULL, couleur VARCHAR(7) DEFAULT NULL, is_active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE type_institution (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, institution_id INT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, firstname VARCHAR(255) DEFAULT NULL, lastname VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) NOT NULL, slug VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D64910405986 (institution_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE visite (id INT AUTO_INCREMENT NOT NULL, employe_id INT NOT NULL, visiteur VARCHAR(255) NOT NULL, arrivee DATETIME NOT NULL, depart DATETIME DEFAULT NULL, objet VARCHAR(255) DEFAULT NULL, statut TINYINT(1) DEFAULT 0 NOT NULL, email VARCHAR(255) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, commentaires LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, badge VARCHAR(255) DEFAULT NULL, INDEX IDX_B09C8CBB1B65292 (employe_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3A1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3A753BDA5 FOREIGN KEY (type_conge_id) REFERENCES type_conges (id)');
        $this->addSql('ALTER TABLE conges_log ADD CONSTRAINT FK_D4ABF347F50CF8EF FOREIGN KEY (conges_id) REFERENCES conges (id)');
        $this->addSql('ALTER TABLE conges_log ADD CONSTRAINT FK_D4ABF347A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B957889920 FOREIGN KEY (fonction_id) REFERENCES fonction (id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B9ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B910405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE institution ADD CONSTRAINT FK_3A9F98E546837012 FOREIGN KEY (type_institution_id) REFERENCES type_institution (id)');
        $this->addSql('ALTER TABLE pointage ADD CONSTRAINT FK_7591B201B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A51B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD210405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB271B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
        $this->addSql('ALTER TABLE solde_conge ADD CONSTRAINT FK_EF1BB27753BDA5 FOREIGN KEY (type_conge_id) REFERENCES type_conges (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64910405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE visite ADD CONSTRAINT FK_B09C8CBB1B65292 FOREIGN KEY (employe_id) REFERENCES employe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3A1B65292');
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3A753BDA5');
        $this->addSql('ALTER TABLE conges_log DROP FOREIGN KEY FK_D4ABF347F50CF8EF');
        $this->addSql('ALTER TABLE conges_log DROP FOREIGN KEY FK_D4ABF347A76ED395');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B9A76ED395');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B957889920');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B9ED5CA9E6');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B910405986');
        $this->addSql('ALTER TABLE institution DROP FOREIGN KEY FK_3A9F98E546837012');
        $this->addSql('ALTER TABLE pointage DROP FOREIGN KEY FK_7591B201B65292');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A51B65292');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD210405986');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB271B65292');
        $this->addSql('ALTER TABLE solde_conge DROP FOREIGN KEY FK_EF1BB27753BDA5');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64910405986');
        $this->addSql('ALTER TABLE visite DROP FOREIGN KEY FK_B09C8CBB1B65292');
        $this->addSql('DROP TABLE conges');
        $this->addSql('DROP TABLE conges_log');
        $this->addSql('DROP TABLE employe');
        $this->addSql('DROP TABLE fonction');
        $this->addSql('DROP TABLE institution');
        $this->addSql('DROP TABLE pointage');
        $this->addSql('DROP TABLE presence');
        $this->addSql('DROP TABLE role');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE solde_conge');
        $this->addSql('DROP TABLE type_conges');
        $this->addSql('DROP TABLE type_institution');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE visite');
    }
}
