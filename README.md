# Agent Harness

Local Phalanx starter app for the v2 bootstrap contract.

## Local Install

```bash
composer install
composer test
composer release:check
composer release:composer
```

This starter uses a local Composer path repository pointing at `../../phalanx`. It is not a Packagist install path yet.
`composer release:composer` prints the publish-ready Composer metadata. `composer release:check` validates the local path repository and release metadata.

## Project Shape

```text
app/AgentHarness.php
config/phalanx.toml
tests/StarterSmokeTest.php
```

## Bootstrap Contract

`AgentHarness::bootstrapContract()` returns the public `Phalanx\Phalanx::bootstrapContract()` value.
