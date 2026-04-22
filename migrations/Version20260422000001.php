<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create read-model tables: board_view, card_history, user_activity, cycle_time.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE board_view (
                card_id     UUID         PRIMARY KEY,
                board_id    UUID         NOT NULL,
                column_id   VARCHAR(20)  NOT NULL,
                title       VARCHAR(255) NOT NULL,
                assignee_id UUID         NULL,
                archived    BOOLEAN      NOT NULL DEFAULT FALSE
            )
        SQL);
        $this->addSql('CREATE INDEX board_view_board_id_idx ON board_view (board_id)');
        $this->addSql('CREATE INDEX board_view_column_id_idx ON board_view (column_id)');

        $this->addSql(<<<'SQL'
            CREATE TABLE card_history (
                sequence    BIGSERIAL    PRIMARY KEY,
                card_id     UUID         NOT NULL,
                event_type  VARCHAR(100) NOT NULL,
                description TEXT         NOT NULL,
                occurred_at TIMESTAMPTZ  NOT NULL
            )
        SQL);
        $this->addSql('CREATE INDEX card_history_card_id_idx ON card_history (card_id, sequence)');

        $this->addSql(<<<'SQL'
            CREATE TABLE user_activity (
                user_id UUID NOT NULL,
                day     DATE NOT NULL,
                events  INT  NOT NULL DEFAULT 0,
                PRIMARY KEY (user_id, day)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE cycle_time (
                card_id        UUID PRIMARY KEY,
                in_progress_at TIMESTAMPTZ NULL,
                done_at        TIMESTAMPTZ NULL
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS cycle_time');
        $this->addSql('DROP TABLE IF EXISTS user_activity');
        $this->addSql('DROP TABLE IF EXISTS card_history');
        $this->addSql('DROP TABLE IF EXISTS board_view');
    }
}