<?php

declare(strict_types=1);

namespace App\AgentCollab;

use App\AgentCollab\Agents\Assistant\Agent;
use Phalanx\Theatron\Collab\Apps\CollabBuilder;
use Phalanx\Theatron\Collab\Prompts\FilePrompt;
use Phalanx\Theatron\Theatron;

final class AgentCollab
{
    /** @param array<string, mixed> $context */
    public static function app(array $context = []): CollabBuilder
    {
        return Theatron::collab($context)
            ->primary(new Agent(new FilePrompt(__DIR__ . '/Agents/Assistant/prompt.md')));
    }
}
