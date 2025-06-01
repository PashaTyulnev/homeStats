<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250601110745 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE homelog DROP dust_value, DROP dust_voltage');
        $this->addSql('ALTER TABLE permanent_data ADD temp_outside DOUBLE PRECISION DEFAULT NULL, DROP dust_value, DROP dust_voltage');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE homelog ADD dust_value DOUBLE PRECISION DEFAULT NULL, ADD dust_voltage DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE permanent_data ADD dust_voltage DOUBLE PRECISION DEFAULT NULL, CHANGE temp_outside dust_value DOUBLE PRECISION DEFAULT NULL');
    }
}
