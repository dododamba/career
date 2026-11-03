<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001112017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Module 02: Multi-Tenancy schema partitioning, indexes, and historical data backfill';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conges ADD institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE conges ADD CONSTRAINT FK_6327DE3A10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_conges_institution ON conges (institution_id)');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B910405986');
        $this->addSql('DROP INDEX idx_f804d3b910405986 ON employe');
        $this->addSql('CREATE INDEX idx_employe_institution ON employe (institution_id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B910405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE fonction ADD institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fonction ADD CONSTRAINT FK_900D5BD10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_fonction_institution ON fonction (institution_id)');
        $this->addSql('ALTER TABLE institution ADD code VARCHAR(50) DEFAULT NULL, ADD status VARCHAR(20) DEFAULT \'ACTIVE\' NOT NULL, ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3A9F98E577153098 ON institution (code)');
        $this->addSql('CREATE INDEX idx_institution_code ON institution (code)');
        $this->addSql('CREATE INDEX idx_institution_status ON institution (status)');
        $this->addSql('ALTER TABLE pointage ADD institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pointage ADD CONSTRAINT FK_7591B2010405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_pointage_institution ON pointage (institution_id)');
        $this->addSql('ALTER TABLE presence ADD institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE presence ADD CONSTRAINT FK_6977C7A510405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_presence_institution ON presence (institution_id)');
        $this->addSql('ALTER TABLE security_audit_log ADD institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE security_audit_log ADD CONSTRAINT FK_9E5F188A10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_security_audit_log_institution ON security_audit_log (institution_id)');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD210405986');
        $this->addSql('DROP INDEX idx_e19d9ad210405986 ON service');
        $this->addSql('CREATE INDEX idx_service_institution ON service (institution_id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD210405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64910405986');
        $this->addSql('ALTER TABLE user CHANGE institution_id institution_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_8d93d64910405986 ON user');
        $this->addSql('CREATE INDEX idx_user_institution ON user (institution_id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64910405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE visite ADD institution_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE visite ADD CONSTRAINT FK_B09C8CBB10405986 FOREIGN KEY (institution_id) REFERENCES institution (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX idx_visite_institution ON visite (institution_id)');

        // Backfill data for existing rows
        $this->addSql("UPDATE institution SET code = CONCAT('INST-', id) WHERE code IS NULL OR code = ''");
        $this->addSql("UPDATE institution SET status = 'ACTIVE' WHERE status IS NULL OR status = ''");
        $this->addSql("UPDATE institution SET slug = CONCAT('institution-', id) WHERE slug IS NULL OR slug = ''");
        $this->addSql("UPDATE pointage p INNER JOIN employe e ON p.employe_id = e.id SET p.institution_id = e.institution_id WHERE p.institution_id IS NULL");
        $this->addSql("UPDATE conges c INNER JOIN employe e ON c.employe_id = e.id SET c.institution_id = e.institution_id WHERE c.institution_id IS NULL");
        $this->addSql("UPDATE visite v INNER JOIN employe e ON v.employe_id = e.id SET v.institution_id = e.institution_id WHERE v.institution_id IS NULL");
        $this->addSql("UPDATE presence pr INNER JOIN employe e ON pr.employe_id = e.id SET pr.institution_id = e.institution_id WHERE pr.institution_id IS NULL");
        $this->addSql("UPDATE security_audit_log s INNER JOIN `user` u ON s.user_id = u.id SET s.institution_id = u.institution_id WHERE s.institution_id IS NULL");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conges DROP FOREIGN KEY FK_6327DE3A10405986');
        $this->addSql('DROP INDEX idx_conges_institution ON conges');
        $this->addSql('ALTER TABLE conges DROP institution_id');
        $this->addSql('ALTER TABLE employe DROP FOREIGN KEY FK_F804D3B910405986');
        $this->addSql('DROP INDEX idx_employe_institution ON employe');
        $this->addSql('CREATE INDEX IDX_F804D3B910405986 ON employe (institution_id)');
        $this->addSql('ALTER TABLE employe ADD CONSTRAINT FK_F804D3B910405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE fonction DROP FOREIGN KEY FK_900D5BD10405986');
        $this->addSql('DROP INDEX idx_fonction_institution ON fonction');
        $this->addSql('ALTER TABLE fonction DROP institution_id');
        $this->addSql('DROP INDEX UNIQ_3A9F98E577153098 ON institution');
        $this->addSql('DROP INDEX idx_institution_code ON institution');
        $this->addSql('DROP INDEX idx_institution_status ON institution');
        $this->addSql('ALTER TABLE institution DROP code, DROP status, DROP slug');
        $this->addSql('ALTER TABLE pointage DROP FOREIGN KEY FK_7591B2010405986');
        $this->addSql('DROP INDEX idx_pointage_institution ON pointage');
        $this->addSql('ALTER TABLE pointage DROP institution_id');
        $this->addSql('ALTER TABLE presence DROP FOREIGN KEY FK_6977C7A510405986');
        $this->addSql('DROP INDEX idx_presence_institution ON presence');
        $this->addSql('ALTER TABLE presence DROP institution_id');
        $this->addSql('ALTER TABLE security_audit_log DROP FOREIGN KEY FK_9E5F188A10405986');
        $this->addSql('DROP INDEX idx_security_audit_log_institution ON security_audit_log');
        $this->addSql('ALTER TABLE security_audit_log DROP institution_id');
        $this->addSql('ALTER TABLE service DROP FOREIGN KEY FK_E19D9AD210405986');
        $this->addSql('DROP INDEX idx_service_institution ON service');
        $this->addSql('CREATE INDEX IDX_E19D9AD210405986 ON service (institution_id)');
        $this->addSql('ALTER TABLE service ADD CONSTRAINT FK_E19D9AD210405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D64910405986');
        $this->addSql('ALTER TABLE `user` CHANGE institution_id institution_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_user_institution ON `user`');
        $this->addSql('CREATE INDEX IDX_8D93D64910405986 ON `user` (institution_id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D64910405986 FOREIGN KEY (institution_id) REFERENCES institution (id)');
        $this->addSql('ALTER TABLE visite DROP FOREIGN KEY FK_B09C8CBB10405986');
        $this->addSql('DROP INDEX idx_visite_institution ON visite');
        $this->addSql('ALTER TABLE visite DROP institution_id');
    }
}
