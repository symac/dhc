<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240618085154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE person ADD COLUMN gender VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE person ADD COLUMN description VARCHAR(512) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__person AS SELECT id, qid, label, image, birth, death, image_license, image_creator, count_awards FROM person');
        $this->addSql('DROP TABLE person');
        $this->addSql('CREATE TABLE person (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, qid VARCHAR(12) NOT NULL, label VARCHAR(512) NOT NULL, image VARCHAR(512) DEFAULT NULL, birth DATE DEFAULT NULL, death DATE DEFAULT NULL, image_license VARCHAR(255) DEFAULT NULL, image_creator VARCHAR(512) DEFAULT NULL, count_awards INTEGER NOT NULL)');
        $this->addSql('INSERT INTO person (id, qid, label, image, birth, death, image_license, image_creator, count_awards) SELECT id, qid, label, image, birth, death, image_license, image_creator, count_awards FROM __temp__person');
        $this->addSql('DROP TABLE __temp__person');
    }
}
