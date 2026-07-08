<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260706075539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du montant converti pour les virements inter-devises.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions ADD converted_amount NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE transactions DROP converted_amount');
    }
}
