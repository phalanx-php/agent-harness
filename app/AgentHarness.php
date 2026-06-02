<?php

declare(strict_types=1);

namespace App\AgentHarness;

use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Theatron\AgentHarness\Apps\AgentHarnessBuilder;
use Phalanx\Theatron\AgentHarness\Prompts\FilePrompt;
use Phalanx\Theatron\Theatron;

final class AgentHarness
{
    /** @param array<string, mixed> $context */
    public static function app(array $context = []): AgentHarnessBuilder
    {
        return Theatron::agentHarness($context)
            ->primary(new Agent(new FilePrompt(__DIR__ . '/Agents/Assistant/prompt.md')));
    }
}
