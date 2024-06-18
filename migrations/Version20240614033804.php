<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240614033804 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE award (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, person_id INTEGER NOT NULL, doctorate_id INTEGER NOT NULL, p585 DATE DEFAULT NULL, p6949 DATE DEFAULT NULL, display_date DATE DEFAULT NULL, CONSTRAINT FK_8A5B2EE7217BBB47 FOREIGN KEY (person_id) REFERENCES person (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8A5B2EE72C0C2CBA FOREIGN KEY (doctorate_id) REFERENCES doctorate (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8A5B2EE7217BBB47 ON award (person_id)');
        $this->addSql('CREATE INDEX IDX_8A5B2EE72C0C2CBA ON award (doctorate_id)');
        $this->addSql('CREATE TABLE doctorate (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, qid VARCHAR(12) NOT NULL, label VARCHAR(512) NOT NULL)');
        $this->addSql('CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, qid VARCHAR(12) NOT NULL, label VARCHAR(512) NOT NULL, image VARCHAR(512) DEFAULT NULL, birth DATE DEFAULT NULL, death DATE DEFAULT NULL, image_license VARCHAR(255) DEFAULT NULL, image_creator VARCHAR(512) DEFAULT NULL, count_awards INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE university (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, doctorate_id INTEGER DEFAULT NULL, qid VARCHAR(12) NOT NULL, label VARCHAR(255) NOT NULL, CONSTRAINT FK_A07A85EC2C0C2CBA FOREIGN KEY (doctorate_id) REFERENCES doctorate (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A07A85EC2C0C2CBA ON university (doctorate_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE award');
        $this->addSql('DROP TABLE doctorate');
        $this->addSql('DROP TABLE person');
        $this->addSql('DROP TABLE university');
    }
}
