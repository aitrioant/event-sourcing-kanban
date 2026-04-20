# CLAUDE.md

# Agent Rules (Global)

## Scope

- Operate only on the code explicitly provided.
- Do not assume missing context. Ask if required.

## Output Rules

- Prefer minimal responses.
- Default: return only code or diff.
- Keep explanations brief and only when useful.
- When making non-trivial changes, include a short rationale (max 3-5 lines).
- Avoid repeating unchanged code.

## Code Changes

- Modify only what is necessary.
- Preserve existing style and architecture.
- Do not introduce new dependencies unless asked.

## Context Usage

- Ignore unrelated files.
- Do not restate input.
- If multiple files are provided, focus only on relevant parts.

## Refactoring

- Keep changes small and incremental.
- Do not refactor entire modules unless explicitly requested.

## Tests

- Only modify tests if they are directly affected.
- Do not rewrite full test suites.

## Performance

- Prefer simple and efficient solutions.
- Avoid overengineering.

## Communication

- If unclear or ambiguous, ask a short question instead of guessing.

## Modes

- Default: concise output, minimal explanation.
- If the user asks "why", "compare", or "explain", provide a clear but brief explanation.
