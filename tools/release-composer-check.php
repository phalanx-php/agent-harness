<?php

declare(strict_types=1);

final class AgentHarnessReleaseComposerCheck
{
    /** @var list<string> */
    private array $errors = [];

    public function __construct(
        private readonly string $root,
    ) {
    }

    public function __invoke(): int
    {
        $composer = $this->composer();

        $this->assertCanonicalIdentity($composer);
        $this->assertLocalPathRepository($composer);
        $this->assertPublishMetadata($this->publishComposer($composer));

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
        if (($composer['require']['phalanx-php/phalanx'] ?? null) !== '0.7.x-dev') {
            $this->errors[] = 'Local composer.json must require phalanx-php/phalanx at 0.7.x-dev.';
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

        if (($repository['options']['versions']['phalanx-php/phalanx'] ?? null) !== '0.7.x-dev') {
            $this->errors[] = 'Local Phalanx path version must be 0.7.x-dev.';
        }
    }

    /**
     * @param array<string, mixed> $composer
     */
    private function assertPublishMetadata(array $composer): void
    {
        if (array_key_exists('repositories', $composer)) {
            $this->errors[] = 'Publish metadata must not include local repositories.';
        }

        if (($composer['require']['phalanx-php/phalanx'] ?? null) !== '^0.7') {
            $this->errors[] = 'Publish metadata must require phalanx-php/phalanx at ^0.7.';
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function composer(): array
    {
        $composer = json_decode(
            $this->read($this->root . '/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (!is_array($composer)) {
            throw new RuntimeException('composer.json did not decode to an object.');
        }

        return $composer;
    }

    /**
     * @param array<string, mixed> $composer
     * @return array<string, mixed>
     */
    private function publishComposer(array $composer): array
    {
        unset($composer['repositories']);
        $composer['require']['phalanx-php/phalanx'] = '^0.7';

        return $composer;
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new RuntimeException("Unable to read {$path}");
        }

        return $contents;
    }
}

exit((new AgentHarnessReleaseComposerCheck(dirname(__DIR__)))());
