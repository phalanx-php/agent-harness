<?php

declare(strict_types=1);

namespace App\AgentCollab\Tests;

use App\AgentCollab\AgentCollab;
use App\AgentCollab\Agents\Assistant\Agent;
use Phalanx\Panoply\Agent as AgentContract;
use Phalanx\Theatron\Tui\Apps\TheatronApp;
use Phalanx\Theatron\Tui\Apps\TheatronBuilder;
use Phalanx\Theatron\Tui\Drawing\StageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('collab')]
final class StarterSmokeTest extends TestCase
{
    #[Test]
    public function agentLoadsColocatedPrompt(): void
    {
        $agent = new Agent();

        self::assertSame('assistant', $agent->id);
        self::assertStringContainsString('Phalanx AgentCollab', $agent->purpose);
    }

    #[Test]
    public function agentImplementsPanoplyContract(): void
    {
        self::assertInstanceOf(AgentContract::class, new Agent());
    }

    #[Test]
    public function appFactoryBuildsTheatronBuilder(): void
    {
        self::assertInstanceOf(TheatronBuilder::class, AgentCollab::app());
    }

    #[Test]
    public function appFactoryBuildProducesTheatronApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        assert(is_resource($stream));

        $app = AgentCollab::app();
        $app->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));

        self::assertInstanceOf(TheatronApp::class, $app->build());
    }
}
