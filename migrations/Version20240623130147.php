<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240623130147 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE doctorate_university (doctorate_id INTEGER NOT NULL, university_id INTEGER NOT NULL, PRIMARY KEY(doctorate_id, university_id), CONSTRAINT FK_367756182C0C2CBA FOREIGN KEY (doctorate_id) REFERENCES doctorate (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_36775618309D1878 FOREIGN KEY (university_id) REFERENCES university (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_367756182C0C2CBA ON doctorate_university (doctorate_id)');
        $this->addSql('CREATE INDEX IDX_36775618309D1878 ON doctorate_university (university_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__university AS SELECT id, qid, label FROM university');
        $this->addSql('DROP TABLE university');
        $this->addSql('CREATE TABLE university (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, qid VARCHAR(12) NOT NULL, label VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO university (id, qid, label) SELECT id, qid, label FROM __temp__university');
        $this->addSql('DROP TABLE __temp__university');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE doctorate_university');
        $this->addSql('CREATE TEMPORARY TABLE __temp__university AS SELECT id, qid, label FROM university');
        $this->addSql('DROP TABLE university');
        $this->addSql('CREATE TABLE university (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, doctorate_id INTEGER DEFAULT NULL, qid VARCHAR(12) NOT NULL, label VARCHAR(255) NOT NULL, CONSTRAINT FK_A07A85EC2C0C2CBA FOREIGN KEY (doctorate_id) REFERENCES doctorate (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO university (id, qid, label) SELECT id, qid, label FROM __temp__university');
        $this->addSql('DROP TABLE __temp__university');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A07A85EC2C0C2CBA ON university (doctorate_id)');
    }
}
