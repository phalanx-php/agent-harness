<?php

declare(strict_types=1);

namespace App\AgentHarness;

use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Harness\Harness;
use Phalanx\Harness\HarnessBuilder;

final class AgentHarness
{
    /** @param array<string, mixed> $context */
    public static function app(array $context = []): HarnessBuilder
    {
        $builder = Harness::app($context)
            ->agent(Agent::class);

        if ($context['HARNESS_DURABLE'] ?? false) {
            $builder->durable();
        }

        return $builder;
    }
}
