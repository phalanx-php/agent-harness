<?php

declare(strict_types=1);

require_once __DIR__ . '/ReleaseComposer.php';

class AgentHarnessReleaseComposerCheck
{
    /** @var list<string> */
    private array $errors = [];

    private AgentHarnessReleaseComposer $release;

    public function __construct(
        string $root,
    ) {
        $this->release = new AgentHarnessReleaseComposer($root);
    }

    public function __invoke(): int
    {
        $composer = $this->release->localComposer();

        $this->assertCanonicalIdentity($composer);
        $this->assertLocalPathRepository($composer);
        $this->assertPublishMetadata($this->release->publishComposer());

        if ($this->errors === []) {
            fwrite(STDOUT, "Agent Harness Composer release checks passed.\n");

            return 0;
        }

        fwrite(STDERR, "Agent Harness Composer release checks failed:\n");
        foreach ($this->errors as $error) {
            fwrite(STDERR, "  - {$error}\n");
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $composer
     */
    private function assertCanonicalIdentity(array $composer): void
    {
        if (($composer['name'] ?? null) !== 'phalanx-php/agent-harness') {
            $this->errors[] = 'Package name must remain phalanx-php/agent-harness.';
        }

        $keywords = $composer['keywords'] ?? [];
        if (!is_array($keywords) || !in_array('agent-harness', $keywords, true)) {
            $this->errors[] = 'Composer keywords must include agent-harness.';
        }
    }

    /**
     * @param array<string, mixed> $composer
     */
    private function assertLocalPathRepository(array $composer): void
    {
        $branchAlias = $this->release->branchAlias($composer);
        if ($branchAlias === null) {
            $this->errors[] = 'Branch alias must be defined as MAJOR.MINOR.x-dev.';

            return;
        }

        $localConstraint = $this->release->publishConstraint($composer) . '@dev';
        if (($composer['require']['phalanx-php/phalanx'] ?? null) !== $localConstraint) {
            $this->errors[] = "Local composer.json must require phalanx-php/phalanx at {$localConstraint}.";
        }

        $repositories = $composer['repositories'] ?? [];
        $repository = is_array($repositories) ? ($repositories[0] ?? null) : null;
        if (!is_array($repository)) {
            $this->errors[] = 'composer.json must keep the local Phalanx path repository.';

            return;
        }

        if (($repository['type'] ?? null) !== 'path') {
            $this->errors[] = 'Local Phalanx repository must be type path.';
        }

        if (($repository['url'] ?? null) !== '../../phalanx') {
            $this->errors[] = 'Local Phalanx repository must point at ../../phalanx.';
        }

        if (($repository['options']['symlink'] ?? null) !== true) {
            $this->errors[] = 'Local Phalanx repository must symlink source packages.';
        }

        if (($repository['options']['versions']['phalanx-php/phalanx'] ?? null) !== $branchAlias) {
            $this->errors[] = "Local Phalanx path version must be {$branchAlias}.";
        }
    }

    /**
     * @param array<string, mixed> $composer
     */
    private function assertPublishMetadata(array $composer): void
    {
        $publishConstraint = $this->release->publishConstraint($composer);
        if ($publishConstraint === null) {
            $this->errors[] = 'Publish constraint could not be derived from branch alias.';

            return;
        }

        if (array_key_exists('repositories', $composer)) {
            $this->errors[] = 'Publish metadata must not include local repositories.';
        }

        if (($composer['require']['phalanx-php/phalanx'] ?? null) !== $publishConstraint) {
            $this->errors[] = "Publish metadata must require phalanx-php/phalanx at {$publishConstraint}.";
        }
    }
}

exit((new AgentHarnessReleaseComposerCheck(dirname(__DIR__)))());
