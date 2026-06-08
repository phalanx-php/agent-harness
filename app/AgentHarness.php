<?php

declare(strict_types=1);

namespace App\AgentHarness;

use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Tui\Runtime\Apps\Builder;
use Phalanx\Tui\Runtime\Prompts\FilePrompt;
use Phalanx\Tui\Tui;

class AgentHarness
{
    /** @param array<string, mixed> $context */
    public static function app(array $context = []): Builder
    {
        return Tui::starting($context)
            ->primary(new Agent(new FilePrompt(__DIR__ . '/Agents/Assistant/prompt.md')));
    }
}
