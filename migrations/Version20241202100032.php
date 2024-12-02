<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241202100032 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande CHANGE remise remise DOUBLE PRECISION DEFAULT NULL, CHANGE total_ht total_ht DOUBLE PRECISION NOT NULL, CHANGE total_ttc total_ttc DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE ligne_commande CHANGE prix_unitaire prix_unitaire DOUBLE PRECISION NOT NULL, CHANGE total total DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE prix_achat prix_achat DOUBLE PRECISION NOT NULL, CHANGE prix_vente prix_vente DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande CHANGE remise remise NUMERIC(5, 2) DEFAULT NULL, CHANGE total_ttc total_ttc NUMERIC(10, 2) NOT NULL, CHANGE total_ht total_ht NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE produit CHANGE prix_achat prix_achat NUMERIC(10, 2) NOT NULL, CHANGE prix_vente prix_vente NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE ligne_commande CHANGE total total NUMERIC(10, 2) NOT NULL, CHANGE prix_unitaire prix_unitaire NUMERIC(10, 2) NOT NULL');
    }
}
