<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241209130600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP INDEX UNIQ_6EEAA67DF77D927C, ADD INDEX IDX_6EEAA67DF77D927C (panier_id)');
        $this->addSql('ALTER TABLE panier_produit CHANGE panier_id panier_id INT NOT NULL, CHANGE produit_id produit_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE panier_produit CHANGE panier_id panier_id INT DEFAULT NULL, CHANGE produit_id produit_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande DROP INDEX IDX_6EEAA67DF77D927C, ADD UNIQUE INDEX UNIQ_6EEAA67DF77D927C (panier_id)');
    }
}
