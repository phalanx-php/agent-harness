<?php

declare(strict_types=1);

namespace App\AgentHarness\Tests;

use App\AgentHarness\AgentHarness;
use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Harness\HarnessBuilder;
use Phalanx\Panoply\Agent as AgentContract;
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
}
