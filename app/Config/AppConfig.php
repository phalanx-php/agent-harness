<?php

declare(strict_types=1);

namespace App\AgentHarness\Config;

use Phalanx\Config\Config;
use Phalanx\Config\Issue;
use Phalanx\Config\ValidationContext;

final class AppConfig implements Config
{
    /** @return list<Issue> */
    public function validate(ValidationContext $context): array
    {
        return [];
    }
}
