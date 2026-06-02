<?php

declare(strict_types=1);

namespace App\AgentHarness\Agents\Assistant;

use Phalanx\Theatron\AgentHarness\Participants\AgentParticipant;
use Phalanx\Theatron\AgentHarness\Plans\WorkPlanItem;
use Phalanx\Theatron\AgentHarness\Plans\WorkResult;
use Phalanx\Theatron\AgentHarness\Prompts\PromptSource;
use Phalanx\Theatron\AgentHarness\WorkContext;

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

    public function __invoke(WorkPlanItem $item, WorkContext $ctx): WorkResult
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

    public function supports(WorkPlanItem $item, WorkContext $ctx): bool
    {
        return true;
    }
}
