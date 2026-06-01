<?php

declare(strict_types=1);

namespace App\AgentCollab;

use App\AgentCollab\Agents\Assistant\Agent;
use Phalanx\Theatron\Tui\Core\Screen;
use Phalanx\Theatron\Tui\Core\ScreenContext;
use Phalanx\Theatron\Tui\Tdom\Renderable;

use function Phalanx\Theatron\Tui\Kit\column;
use function Phalanx\Theatron\Tui\Kit\panel;
use function Phalanx\Theatron\Tui\Kit\text;

final class AgentScreen implements Screen
{
    public function __invoke(ScreenContext $ctx): Renderable
    {
        $agent = new Agent();

        return panel(
            'AgentCollab',
            column(
                text('Agent: ' . $agent->name),
                text('Purpose: ' . $agent->purpose),
            ),
        );
    }
}
