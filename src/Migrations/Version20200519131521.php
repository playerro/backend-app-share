<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200519131521 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SEQUENCE app_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE app (id INT NOT NULL, uploader_id INT NOT NULL, name VARCHAR(255) NOT NULL, domain VARCHAR(255) NOT NULL, comment TEXT NOT NULL, is_static BOOLEAN NOT NULL, source VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, created INT NOT NULL, deleted INT NOT NULL, image VARCHAR(255) DEFAULT NULL, folder VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C96E70CF16678C77 ON app (uploader_id)');
        $this->addSql('ALTER TABLE app ADD CONSTRAINT FK_C96E70CF16678C77 FOREIGN KEY (uploader_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE app_id_seq CASCADE');
        $this->addSql('DROP TABLE app');
    }
}
