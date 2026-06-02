<?php

declare(strict_types=1);

namespace App\AgentHarness\Tests;

use App\AgentHarness\AgentHarness;
use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Scope\ExecutionScope;
use Phalanx\Scope\TaskScope;
use Phalanx\Testing\PhalanxTestCase;
use Phalanx\Theatron\AgentHarness\Apps\AgentHarnessBuilder;
use Phalanx\Theatron\AgentHarness\Apps\AgentHarnessRuntime;
use Phalanx\Theatron\AgentHarness\Boundaries\InputPromptSubmitter;
use Phalanx\Theatron\AgentHarness\Participants\AgentParticipant;
use Phalanx\Theatron\AgentHarness\Plans\Activity;
use Phalanx\Theatron\AgentHarness\Plans\WorkItem;
use Phalanx\Theatron\AgentHarness\Plans\WorkPlanItem;
use Phalanx\Theatron\AgentHarness\Plans\WorkPlanStatus;
use Phalanx\Theatron\AgentHarness\Plans\WorkResult;
use Phalanx\Theatron\AgentHarness\Prompts\FilePrompt;
use Phalanx\Theatron\AgentHarness\Prompts\PromptSource;
use Phalanx\Theatron\AgentHarness\State\AgentHarnessStore;
use Phalanx\Theatron\AgentHarness\WorkContext;
use Phalanx\Theatron\Tui\Apps\TheatronApp;
use Phalanx\Theatron\Tui\Drawing\StageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('agent-harness')]
final class StarterSmokeTest extends PhalanxTestCase
{
    #[Test]
    public function agentUsesPromptSourceInstructions(): void
    {
        $agent = new Agent(new StarterPromptSource());
        $item = WorkPlanItem::pending(new WorkItem(Activity::Thinking, 'Draft a plan', id: 'work_test'));
        $ctx = new WorkContext($this->createStub(TaskScope::class), new AgentHarnessStore());
        $result = $agent($item, $ctx);

        self::assertSame('assistant', $agent->id);
        self::assertSame('Assistant', $agent->name);
        self::assertSame('Assistant received: Draft a plan', $result->summary);
        self::assertIsArray($result->payload);
        self::assertSame('Phalanx AgentHarness instructions', $result->payload['prompt']);
    }

    #[Test]
    public function agentImplementsAgentHarnessContract(): void
    {
        self::assertInstanceOf(AgentParticipant::class, new Agent(new StarterPromptSource()));
    }

    #[Test]
    public function shippedAssistantPromptFileIsExecutable(): void
    {
        $agent = new Agent(new FilePrompt(dirname(__DIR__) . '/app/Agents/Assistant/prompt.md'));
        $result = $this->scope->run(static function (ExecutionScope $scope) use ($agent): WorkResult {
            $item = WorkPlanItem::pending(new WorkItem(Activity::Thinking, 'Use shipped prompt', id: 'work_prompt'));
            $ctx = new WorkContext($scope, new AgentHarnessStore());

            return $agent($item, $ctx);
        });

        self::assertSame('Assistant received: Use shipped prompt', $result->summary);
        self::assertIsArray($result->payload);
        self::assertStringStartsWith('file:', $result->payload['instructions']);
        self::assertStringContainsString('concise assistant', $result->payload['prompt']);
    }

    #[Test]
    public function appFactoryBuildsAgentHarnessBuilder(): void
    {
        self::assertInstanceOf(AgentHarnessBuilder::class, AgentHarness::app());
    }

    #[Test]
    public function appFactoryBuildProducesTheatronApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        $app = AgentHarness::app();
        $app->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));

        self::assertInstanceOf(TheatronApp::class, $app->build());
    }

    #[Test]
    public function appFactoryRuntimeAcceptsInputAndProjectsCompletionState(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        $builder = AgentHarness::app(['APP_ENV' => 'test']);
        $builder->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));
        $app = $builder->build();
        $testApp = $this->testApp([], ...$builder->resolvedProviders($app));

        $testApp->application->scoped(static function (ExecutionScope $scope): void {
            $submit = $scope->service(InputPromptSubmitter::class);
            $runtime = $scope->service(AgentHarnessRuntime::class);
            $store = $scope->service(AgentHarnessStore::class);

            self::assertInstanceOf(InputPromptSubmitter::class, $submit);
            self::assertInstanceOf(AgentHarnessRuntime::class, $runtime);
            self::assertInstanceOf(AgentHarnessStore::class, $store);

            $submit('Summarize the roadmap');
            $status = $runtime->tick($scope);

            self::assertSame(WorkPlanStatus::Complete, $status);
            self::assertSame(WorkPlanStatus::Complete, $store->workPlan->plan->status);
            self::assertSame('Summarize the roadmap', $store->messages->envelopes[0]->payload);
            self::assertSame('Assistant received: Summarize the roadmap', $store->messages->entries[2]->summary);
        });
    }

    #[Test]
    public function publicAppCodeDoesNotExposeProviderRuntimeImports(): void
    {
        $offenders = [];

        foreach (self::publicSurfaceFiles() as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);

            foreach (['Phalanx\\Athena\\', 'Phalanx\\Panoply\\', 'Phalanx\\Themis\\', 'Phalanx\\Theatron\\Collab\\'] as $token) {
                if (str_contains($source, $token)) {
                    $offenders[] = str_replace(dirname(__DIR__) . '/', '', $file) . " contains {$token}";
                }
            }
        }

        self::assertSame([], $offenders, implode("\n", $offenders));
    }

    #[Test]
    public function composerRequireSurfaceNamesOnlyTheatronFromPhalanxPackages(): void
    {
        $composer = json_decode(self::read(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $packages = [];
        foreach (array_keys($composer['require']) as $package) {
            if (is_string($package) && str_starts_with($package, 'phalanx-php/')) {
                $packages[] = $package;
            }
        }

        self::assertSame(['phalanx-php/theatron'], $packages);
    }

    /**
     * @return list<string>
     */
    private static function publicSurfaceFiles(): array
    {
        $root = dirname(__DIR__);
        $files = [
            $root . '/README.md',
            $root . '/bin/agent-harness',
            $root . '/composer.json',
        ];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/app'));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private static function read(string $file): string
    {
        $source = file_get_contents($file);
        self::assertIsString($source);

        return $source;
    }
}

final class StarterPromptSource implements PromptSource
{
    public string $id {
        get => 'starter';
    }

    public function __invoke(TaskScope $scope): string
    {
        return 'Phalanx AgentHarness instructions';
    }
}
