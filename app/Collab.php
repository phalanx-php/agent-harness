<?php

declare(strict_types=1);

namespace App\Collab;

use App\Collab\Agents\Assistant\Agent;
use Phalanx\Tui\Collab\Apps\Builder;
use Phalanx\Tui\Collab\Prompts\FilePrompt;
use Phalanx\Tui\Tui;

final class Collab
{
    /** @param array<string, mixed> $context */
    public static function app(array $context = []): Builder
    {
        return Tui::collab($context)
            ->primary(new Agent(new FilePrompt(__DIR__ . '/Agents/Assistant/prompt.md')));
    }
}
