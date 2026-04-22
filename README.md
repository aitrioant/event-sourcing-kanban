# Event-Sourced Kanban

A Kanban board implemented with event sourcing — every card movement, comment, and assignment is persisted as an immutable event. The current board, per-user activity, and cycle-time metrics are derived projections.

The point of the project is to demonstrate the practical value of event sourcing: full audit history, time-travel debugging, and the ability to answer questions the original schema was never designed for — just by replaying the log into a new projection.

## Domain events

- `CardCreated`
- `CardMoved`
- `CardAssigned`
- `CardCommented`
- `CardArchived`

## Projections

Projections are written synchronously inside the same DB transaction as the event append, so reads are consistent with writes during this learning phase.

- **`board_view`** — current state of every card (column, assignee, title, archived).
- **`card_history`** — one human-readable row per event.
- **`user_activity`** — events per user per day (assignees + comment authors).
- **`cycle_time`** — first time a card entered `doing`, last time it hit `done`.

Each projection can be truncated and rebuilt from the event log:

```bash
./bin/console app:projection:rebuild board_view
./bin/console app:projection:rebuild --all
```

## Stack

- PHP 8.4
- Symfony 8
- Doctrine (event store + projections)

## Running locally

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php ./bin/console doctrine:migrations:migrate -n
```

App on http://localhost:8081, Postgres on `localhost:5434`.

## Tests

Unit tests run with no external dependencies:

```bash
./bin/phpunit --testsuite unit
```

Integration tests need the test database up and migrated:

```bash
docker compose up -d db
./bin/console --env=test doctrine:database:create --if-not-exists
./bin/console --env=test doctrine:migrations:migrate -n
./bin/phpunit --testsuite integration
```

Run everything:

```bash
./bin/phpunit
```

## Status

Work in progress. Built as a portfolio piece to explore event sourcing patterns in a non-trivial domain.