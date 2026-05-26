# AgentHarness

A Phalanx starter app for building terminal agent harnesses.

## Run

```bash
composer install
php bin/harness
```

## Shape

- `app/Agents/Assistant/Agent.php` and `prompt.md` are colocated so each agent owns its identity and instructions.
- `app/Config/AppConfig.php` is the typed config root.
- `app/Template/` contains the screen, keymap, overlay, render, and state slices that make the starter useful immediately.
