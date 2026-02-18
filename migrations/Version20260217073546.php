<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260217073546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE plat_menu DROP FOREIGN KEY `FK_CA9ED79CCCD7E912`');
        $this->addSql('ALTER TABLE plat_menu DROP FOREIGN KEY `FK_CA9ED79CD73DB560`');
        $this->addSql('DROP TABLE plat_menu');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE plat_menu (plat_id INT NOT NULL, menu_id INT NOT NULL, INDEX IDX_CA9ED79CCCD7E912 (menu_id), INDEX IDX_CA9ED79CD73DB560 (plat_id), PRIMARY KEY (plat_id, menu_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE plat_menu ADD CONSTRAINT `FK_CA9ED79CCCD7E912` FOREIGN KEY (menu_id) REFERENCES menu (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plat_menu ADD CONSTRAINT `FK_CA9ED79CD73DB560` FOREIGN KEY (plat_id) REFERENCES plat (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
