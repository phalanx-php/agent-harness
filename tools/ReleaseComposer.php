<?php

declare(strict_types=1);

class AgentHarnessReleaseComposer
{
    public function __construct(
        private readonly string $root,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function localComposer(): array
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
     * @return array<string, mixed>
     */
    public function publishComposer(): array
    {
        $composer = $this->localComposer();
        unset($composer['repositories']);
        $composer['require']['phalanx-php/phalanx'] = $this->publishConstraint($composer) ?? '';

        return $composer;
    }

    /**
     * @param array<string, mixed> $composer
     */
    public function branchAlias(array $composer): ?string
    {
        $alias = $composer['extra']['branch-alias']['dev-main'] ?? null;
        if (!is_string($alias) || preg_match('/^\d+\.\d+\.x-dev$/', $alias) !== 1) {
            return null;
        }

        return $alias;
    }

    /**
     * @param array<string, mixed> $composer
     */
    public function publishConstraint(array $composer): ?string
    {
        $alias = $this->branchAlias($composer);
        if ($alias === null) {
            return null;
        }

        return '^' . str_replace('.x-dev', '', $alias);
    }

    /**
     * @param array<string, mixed> $composer
     */
    public function encode(array $composer): string
    {
        return json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
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
