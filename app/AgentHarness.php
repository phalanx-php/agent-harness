<?php

declare(strict_types=1);

namespace App\AgentHarness;

use Phalanx\Bootstrap\BootstrapContract;
use Phalanx\Phalanx;

class AgentHarness
{
    public static function bootstrapContract(): BootstrapContract
    {
        return Phalanx::bootstrapContract();
    }
}
