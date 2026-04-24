<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415173500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow nullable Google IDs and larger face descriptors for user biometric login.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users MODIFY face_data LONGTEXT DEFAULT NULL, MODIFY google_id VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users MODIFY face_data VARCHAR(255) DEFAULT NULL, MODIFY google_id VARCHAR(255) NOT NULL');
    }
}
