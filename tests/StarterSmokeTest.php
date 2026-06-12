<?php

declare(strict_types=1);

namespace App\AgentHarness\Tests;

use App\AgentHarness\AgentHarness;
use Phalanx\Bootstrap\BootstrapContract;
use Phalanx\Phalanx;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StarterSmokeTest extends TestCase
{
    #[Test]
    public function appExposesTheV2BootstrapContract(): void
    {
        self::assertSame(Phalanx::bootstrapContract()->toArray(), AgentHarness::bootstrapContract()->toArray());
    }

    #[Test]
    public function composerRequireSurfaceNamesOnlyLocalFrameworkPackage(): void
    {
        $composer = self::composer();
        $packages = [];
        foreach (array_keys($composer['require']) as $package) {
            if (is_string($package) && str_starts_with($package, 'phalanx-php/')) {
                $packages[] = $package;
            }
        }

        self::assertSame([BootstrapContract::PACKAGE], $packages);
    }

    #[Test]
    public function agentHarnessIdentityIsCanonical(): void
    {
        $composer = self::composer();

        self::assertSame('phalanx-php/agent-harness', $composer['name']);
        self::assertContains('agent-harness', $composer['keywords']);
        self::assertFileExists(dirname(__DIR__) . '/phalanx.toml');
    }

    #[Test]
    public function publishComposerMetadataUsesReleasedFrameworkConstraint(): void
    {
        $composer = self::generatedPublishComposer();

        self::assertArrayNotHasKey('repositories', $composer);
        self::assertSame(self::publishConstraint($composer), $composer['require'][BootstrapContract::PACKAGE]);
    }

    #[Test]
    public function localComposerMetadataUsesSourceLinkedFrameworkPackage(): void
    {
        $composer = self::composer();
        $repository = $composer['repositories'][0];

        self::assertSame(self::publishConstraint($composer) . '@dev', $composer['require'][BootstrapContract::PACKAGE]);
        self::assertSame('path', $repository['type']);
        self::assertIsString($repository['url']);
        self::assertNotSame('', $repository['url']);
        self::assertTrue($repository['options']['symlink']);
        self::assertSame(self::branchAlias($composer), $repository['options']['versions'][BootstrapContract::PACKAGE]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function composer(): array
    {
        $composer = json_decode(self::read(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        return $composer;
    }

    /**
     * @return array<string, mixed>
     */
    private static function generatedPublishComposer(): array
    {
        $lines = [];
        exec(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/tools/release-composer.php') . ' --stdout',
            $lines,
            $exitCode,
        );
        self::assertSame(0, $exitCode);

        $composer = json_decode(implode("\n", $lines), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        return $composer;
    }

    /**
     * @param array<string, mixed> $composer
     */
    private static function branchAlias(array $composer): string
    {
        $alias = $composer['extra']['branch-alias']['dev-main'] ?? null;
        self::assertIsString($alias);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.x-dev$/', $alias);

        return $alias;
    }

    /**
     * @param array<string, mixed> $composer
     */
    private static function publishConstraint(array $composer): string
    {
        $alias = self::branchAlias($composer);

        return '^' . str_replace('.x-dev', '', $alias);
    }

    private static function read(string $file): string
    {
        $contents = file_get_contents($file);
        self::assertIsString($contents);

        return $contents;
    }
}
