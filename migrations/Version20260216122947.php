<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216122947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE menu_allergene (menu_id INT NOT NULL, allergene_id INT NOT NULL, INDEX IDX_8A634DD1CCD7E912 (menu_id), INDEX IDX_8A634DD14646AB2 (allergene_id), PRIMARY KEY (menu_id, allergene_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE menu_allergene ADD CONSTRAINT FK_8A634DD1CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE menu_allergene ADD CONSTRAINT FK_8A634DD14646AB2 FOREIGN KEY (allergene_id) REFERENCES allergene (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_allergene DROP FOREIGN KEY FK_8A634DD1CCD7E912');
        $this->addSql('ALTER TABLE menu_allergene DROP FOREIGN KEY FK_8A634DD14646AB2');
        $this->addSql('DROP TABLE menu_allergene');
    }
}
