# Agent Harness

Local Phalanx starter app for a Tui runtime workspace.

## Local Install

```bash
composer install
composer test
```

This starter uses a local Composer path repository pointing at `../../phalanx`. It is not a Packagist install path yet.

## Project Shape

```text
app/AgentHarness.php
app/Agents/Assistant/Agent.php
app/Agents/Assistant/prompt.md
config/phalanx.toml
tests/StarterSmokeTest.php
```

## App Factory

`AgentHarness::app($context)` builds the current `Phalanx\Tui\Runtime` workspace through `Tui::starting()` with the starter assistant as the primary participant.
