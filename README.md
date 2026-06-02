# AgentHarness

A Phalanx starter app for building terminal agent-harness tools with Theatron AgentHarness.

## Layer Stack

| Layer | Package | Role |
|-------|---------|------|
| Theatron Tui | `phalanx-php/theatron` | Terminal UI framework: screens, input, render, status bars, overlays |
| Theatron AgentHarness | `phalanx-php/theatron` | AgentHarness builder, receive path, work loop, projections |

## Boot Path

```
bin/agent-harness (symfony/runtime)
  -> AgentHarness::app($context)
    -> Theatron::agentHarness($context) -> AgentHarnessBuilder
      -> primary(new Agent(new FilePrompt(...)))
    -> AgentHarnessBuilder->run()
```

## Run

```bash
composer install
php bin/agent-harness
```

## Config

No required environment values for the starter.

## Project Shape

```
bin/agent-harness                          - entry point (symfony/runtime)
app/AgentHarness.php                 - app factory
app/Agents/Assistant/Agent.php      - AgentHarness participant
app/Agents/Assistant/prompt.md      - agent system prompt
```

## Theatron

The UI is the default AgentHarness workspace. User input goes through `InputComposer`, into the receive path, then through the AgentHarness loop and store projection.
