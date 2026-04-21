<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create events table (append-only event store).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE events (
                sequence     BIGSERIAL    NOT NULL,
                stream_id    UUID         NOT NULL,
                version      INT          NOT NULL,
                event_type   VARCHAR(100) NOT NULL,
                payload      JSONB        NOT NULL,
                metadata     JSONB        NOT NULL DEFAULT '{}'::jsonb,
                occurred_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
                PRIMARY KEY (stream_id, version),
                UNIQUE (sequence)
            )
        SQL);

        $this->addSql('CREATE INDEX events_event_type_idx ON events (event_type)');
        $this->addSql('CREATE INDEX events_occurred_at_idx ON events (occurred_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS events');
    }
}