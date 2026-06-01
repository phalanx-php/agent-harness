# AgentCollab

A Phalanx starter app for building terminal agent collaboration tools. Swoole-native, supervised by Aegis, rendered by Theatron.

## Layer Stack

| Layer | Package | Role |
|-------|---------|------|
| Theatron | `phalanx-php/theatron` | Terminal UI framework: screens, input, render, status bars, overlays |
| Panoply | `phalanx-php/panoply` | Provider/cue/tool taxonomy and typed runtime events |
| Themis | `phalanx-php/themis` | Typed config objects, env catalog, validation |

## Boot Path

```
bin/collab (symfony/runtime)
  -> AgentCollab::app($context)
    -> Theatron::app($context) -> TheatronBuilder
      -> ->screens([AgentScreen::class])
    -> TheatronBuilder->run()
```

## Run

```bash
composer install
php bin/collab
```

## Config

| Key | Type | Default | Description |
|-----|------|---------|-------------|
| `COLLAB_OLLAMA_BASE_URL` | string | `http://localhost:11434` | Ollama API base URL |
| `COLLAB_OLLAMA_MODEL` | string | `qwen3:4b` | Default chat model |
| `COLLAB_MAX_INVOCATIONS` | int | `3` | Max agent invocations per activity |

## Project Shape

```
bin/collab                          - entry point (symfony/runtime)
app/AgentCollab.php                 - app factory
app/AgentScreen.php                 - first Theatron screen
app/Agents/Assistant/Agent.php      - colocated agent identity
app/Agents/Assistant/prompt.md      - agent system prompt
app/Config/AppConfig.php            - typed config root
```

## Theatron

The UI is built with Theatron screens and TDOM renderables. Start with `AgentScreen`, add state with Theatron Reactive primitives, then wire real model execution through Panoply/Athena when the app is ready for it.
