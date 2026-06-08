# Agent Harness

Local Phalanx starter app for a Tui runtime workspace.

## Local Install

```bash
composer install
composer test
composer release:check
```

This starter uses a local Composer path repository pointing at `../../phalanx`. It is not a Packagist install path yet.
`composer release:check` validates the local path repository and the publish-ready Composer metadata dry run.

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
