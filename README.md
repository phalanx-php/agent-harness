# Collab Starter

Local Phalanx starter app for a Tui Collab workspace.

## Local Install

```bash
composer install
composer test
```

This starter uses a local Composer path repository pointing at `../../phalanx`. It is not a Packagist install path yet.

## Project Shape

```text
app/Collab.php
app/Agents/Assistant/Agent.php
app/Agents/Assistant/prompt.md
config/phalanx.toml
tests/StarterSmokeTest.php
```

## App Factory

`Collab::app($context)` builds the current `Phalanx\Tui\Collab` workspace with the starter assistant as the primary participant.
