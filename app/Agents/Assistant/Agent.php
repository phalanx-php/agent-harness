<?php

declare(strict_types=1);

namespace App\AgentCollab\Agents\Assistant;

use Phalanx\Theatron\Collab\Participants\Collaborator;
use Phalanx\Theatron\Collab\Plans\WorkPlanItem;
use Phalanx\Theatron\Collab\Plans\WorkResult;
use Phalanx\Theatron\Collab\Prompts\PromptSource;
use Phalanx\Theatron\Collab\WorkContext;

final class Agent implements Collaborator
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
