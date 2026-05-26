<?php

declare(strict_types=1);

namespace App\AgentHarness\Tests;

use App\AgentHarness\AgentHarness;
use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Harness\AgoraServiceBundle;
use Phalanx\Harness\HarnessBuilder;
use Phalanx\Panoply\Agent as AgentContract;
use Phalanx\Surreal\SurrealBundle;
use Phalanx\Theatron\Stage\StageConfig;
use Phalanx\Theatron\TheatronApp;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class StarterSmokeTest extends TestCase
{
    #[Test]
    public function agentLoadsColocatedPrompt(): void
    {
        $agent = new Agent();

        self::assertSame('assistant', $agent->id);
        self::assertStringContainsString('Phalanx AgentHarness', $agent->purpose);
    }

    #[Test]
    public function agentImplementsPanoplyContract(): void
    {
        self::assertInstanceOf(AgentContract::class, new Agent());
    }

    #[Test]
    public function appFactoryBuildsHarnessBuilder(): void
    {
        self::assertInstanceOf(HarnessBuilder::class, AgentHarness::app());
    }

    #[Test]
    public function appFactoryBuildProducesTheatronApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        assert(is_resource($stream));

        $app = AgentHarness::app();
        $app->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));

        self::assertInstanceOf(TheatronApp::class, $app->build());
    }

    #[Test]
    public function appFactoryWithDurableContextRegistersAgoraBundles(): void
    {
        $stream = fopen('php://memory', 'w+');
        assert(is_resource($stream));

        $builder = AgentHarness::app(['HARNESS_DURABLE' => true]);
        $builder->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));
        $builder->build();

        $providers = $builder->registeredProviders();

        self::assertNotNull(array_find(
            $providers,
            static fn(mixed $p): bool => $p instanceof SurrealBundle,
        ));
        self::assertNotNull(array_find(
            $providers,
            static fn(mixed $p): bool => $p instanceof AgoraServiceBundle,
        ));
    }
}
