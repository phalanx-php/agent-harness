<?php

declare(strict_types=1);

namespace App\AgentHarness\Agents\Assistant;

use Phalanx\Tui\Runtime\Participants\AgentParticipant;
use Phalanx\Tui\Runtime\Plans\WorkPlanItem;
use Phalanx\Tui\Runtime\Plans\WorkResult;
use Phalanx\Tui\Runtime\Prompts\PromptSource;
use Phalanx\Tui\Runtime\WorkContext;

final class Agent implements AgentParticipant
{
    public string $id {
        get => 'assistant';
    }

    public string $name {
        get => 'Assistant';
    }

    public function __construct(
        private PromptSource $instructions,
    ) {
    }

    public function __invoke(WorkContext $ctx, WorkPlanItem $item): WorkResult
    {
        return WorkResult::done(
            itemId: $item->workItem->id,
            payload: [
                'agent_id' => $this->id,
                'instructions' => $this->instructions->id,
                'prompt' => trim(($this->instructions)($ctx->scope)),
            ],
            summary: sprintf('%s received: %s', $this->name, $item->workItem->prompt),
        );
    }

    public function supports(WorkContext $ctx, WorkPlanItem $item): bool
    {
        return true;
    }
}
