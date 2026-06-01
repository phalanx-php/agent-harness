<?php

declare(strict_types=1);

namespace App\AgentCollab;

use Phalanx\Theatron\Theatron;
use Phalanx\Theatron\Tui\Apps\TheatronBuilder;

final class AgentCollab
{
    /** @param array<string, mixed> $context */
    public static function app(array $context = []): TheatronBuilder
    {
        return Theatron::app($context)
            ->screens([AgentScreen::class]);
    }
}
