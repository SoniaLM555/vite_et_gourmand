<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218150357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu ADD entree_id INT DEFAULT NULL, ADD plat_principal_id INT DEFAULT NULL, ADD dessert_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A93AF7BD910 FOREIGN KEY (entree_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A9329599711 FOREIGN KEY (plat_principal_id) REFERENCES plat (id)');
        $this->addSql('ALTER TABLE menu ADD CONSTRAINT FK_7D053A93745B52FD FOREIGN KEY (dessert_id) REFERENCES plat (id)');
        $this->addSql('CREATE INDEX IDX_7D053A93AF7BD910 ON menu (entree_id)');
        $this->addSql('CREATE INDEX IDX_7D053A9329599711 ON menu (plat_principal_id)');
        $this->addSql('CREATE INDEX IDX_7D053A93745B52FD ON menu (dessert_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A93AF7BD910');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A9329599711');
        $this->addSql('ALTER TABLE menu DROP FOREIGN KEY FK_7D053A93745B52FD');
        $this->addSql('DROP INDEX IDX_7D053A93AF7BD910 ON menu');
        $this->addSql('DROP INDEX IDX_7D053A9329599711 ON menu');
        $this->addSql('DROP INDEX IDX_7D053A93745B52FD ON menu');
        $this->addSql('ALTER TABLE menu DROP entree_id, DROP plat_principal_id, DROP dessert_id');
    }
}
