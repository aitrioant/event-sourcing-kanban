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

- **Board state** — current columns and cards.
- **User activity** — per-user event stream.
- **Cycle-time metrics** — derived from `CardMoved` history.

## Stack

- PHP 8.4
- Symfony 8
- Doctrine (event store + projections)

## Running locally

```bash
composer install
symfony server:start
```

## Status

Work in progress. Built as a portfolio piece to explore event sourcing patterns in a non-trivial domain.