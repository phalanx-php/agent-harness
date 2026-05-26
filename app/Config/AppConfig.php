<?php

declare(strict_types=1);

namespace App\AgentHarness\Config;

use Phalanx\Themis\Config;
use Phalanx\Themis\Issue;
use Phalanx\Themis\ValidationContext;

final class AppConfig implements Config
{
    public bool $configured {
        get => true;
    }

    /** @return list<Issue> */
    public function validate(ValidationContext $context): array
    {
        return [];
    }
}
