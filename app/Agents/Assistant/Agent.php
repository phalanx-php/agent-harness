<?php

declare(strict_types=1);

namespace App\AgentCollab\Agents\Assistant;

use Phalanx\Panoply\Agent as AgentContract;
use Phalanx\Panoply\Capabilities;
use Phalanx\Panoply\Capability;
use Phalanx\Panoply\Context;
use Phalanx\Panoply\Effects;
use Phalanx\Panoply\Output;
use Phalanx\Panoply\Provider\Needs as ProviderNeeds;
use Phalanx\Panoply\Provider\Preference;
use Phalanx\Panoply\Transport\Needs as TransportNeeds;
use RuntimeException;

final class Agent implements AgentContract
{
    private ?string $cachedPurpose = null;

    public string $id {
        get => 'assistant';
    }

    public string $name {
        get => 'Assistant';
    }

    public string $purpose {
        get => $this->cachedPurpose ??= self::loadPrompt(__DIR__ . '/prompt.md');
    }

    public Output $output {
        get => Output::text();
    }

    public Context $context {
        get => Context::new();
    }

    public Effects $effects {
        get => Effects::none();
    }

    public ProviderNeeds $provider {
        get => ProviderNeeds::new()
            ->prefer(Preference::LocalFirst)
            ->require(Capability::Reasoning);
    }

    public Capabilities $capabilities {
        get => Capabilities::of(Capability::Reasoning, Capability::Streaming);
    }

    public TransportNeeds $transport {
        get => TransportNeeds::new()->streaming()->cancellable();
    }

    private static function loadPrompt(string $path): string
    {
        $content = file_get_contents($path);

        if (!is_string($content)) {
            throw new RuntimeException("Unable to read agent prompt: {$path}");
        }

        return trim($content);
    }
}
