# AgentCollab

A Phalanx starter app for building terminal agent collaboration tools with Theatron Collab.

## Layer Stack

| Layer | Package | Role |
|-------|---------|------|
| Theatron Tui | `phalanx-php/theatron` | Terminal UI framework: screens, input, render, status bars, overlays |
| Theatron Collab | `phalanx-php/theatron` | Collaboration builder, receive path, work loop, projections |

## Boot Path

```
bin/collab (symfony/runtime)
  -> AgentCollab::app($context)
    -> Theatron::collab($context) -> CollabBuilder
      -> primary(new Agent(new FilePrompt(...)))
    -> CollabBuilder->run()
```

## Run

```bash
composer install
php bin/collab
```

## Config

No required environment values for the starter.

## Project Shape

```
bin/collab                          - entry point (symfony/runtime)
app/AgentCollab.php                 - app factory
app/Agents/Assistant/Agent.php      - Collab collaborator
app/Agents/Assistant/prompt.md      - agent system prompt
```

## Theatron

The UI is the default Collab workspace. User input goes through `InputComposer`, into the receive path, then through the Collab loop and store projection.
