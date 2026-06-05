<?php

declare(strict_types=1);

namespace App\Collab\Tests;

use App\Collab\Agents\Assistant\Agent;
use App\Collab\Collab;
use Phalanx\Scope\ExecutionScope;
use Phalanx\Scope\TaskScope;
use Phalanx\Testing\PhalanxTestCase;
use Phalanx\Tui\Apps\App;
use Phalanx\Tui\Collab\Apps\Builder;
use Phalanx\Tui\Collab\Apps\Runtime;
use Phalanx\Tui\Collab\Boundaries\InputPromptSubmitter;
use Phalanx\Tui\Collab\Participants\AgentParticipant;
use Phalanx\Tui\Collab\Plans\Activity;
use Phalanx\Tui\Collab\Plans\WorkItem;
use Phalanx\Tui\Collab\Plans\WorkPlanItem;
use Phalanx\Tui\Collab\Plans\WorkPlanStatus;
use Phalanx\Tui\Collab\Plans\WorkResult;
use Phalanx\Tui\Collab\Prompts\FilePrompt;
use Phalanx\Tui\Collab\Prompts\PromptSource;
use Phalanx\Tui\Collab\State\Store;
use Phalanx\Tui\Collab\WorkContext;
use Phalanx\Tui\Drawing\StageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('collab')]
final class StarterSmokeTest extends PhalanxTestCase
{
    #[Test]
    public function agentUsesPromptSourceInstructions(): void
    {
        $agent = new Agent(new StarterPromptSource());
        $item = WorkPlanItem::pending(new WorkItem(Activity::Thinking, 'Draft a plan', id: 'work_test'));
        $ctx = new WorkContext($this->createStub(TaskScope::class), new Store());
        $result = $agent($item, $ctx);

        self::assertSame('assistant', $agent->id);
        self::assertSame('Assistant', $agent->name);
        self::assertSame('Assistant received: Draft a plan', $result->summary);
        self::assertIsArray($result->payload);
        self::assertSame('Phalanx Collab instructions', $result->payload['prompt']);
    }

    #[Test]
    public function agentImplementsCollabContract(): void
    {
        self::assertInstanceOf(AgentParticipant::class, new Agent(new StarterPromptSource()));
    }

    #[Test]
    public function shippedAssistantPromptFileIsExecutable(): void
    {
        $agent = new Agent(new FilePrompt(dirname(__DIR__) . '/app/Agents/Assistant/prompt.md'));
        $result = $this->scope->run(static function (ExecutionScope $scope) use ($agent): WorkResult {
            $item = WorkPlanItem::pending(new WorkItem(Activity::Thinking, 'Use shipped prompt', id: 'work_prompt'));
            $ctx = new WorkContext($scope, new Store());

            return $agent($item, $ctx);
        });

        self::assertSame('Assistant received: Use shipped prompt', $result->summary);
        self::assertIsArray($result->payload);
        self::assertStringStartsWith('file:', $result->payload['instructions']);
        self::assertStringContainsString('concise assistant', $result->payload['prompt']);
    }

    #[Test]
    public function appFactoryBuildsCollabBuilder(): void
    {
        self::assertInstanceOf(Builder::class, Collab::app());
    }

    #[Test]
    public function appFactoryBuildProducesTuiApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        $app = Collab::app();
        $app->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));

        self::assertInstanceOf(App::class, $app->build());
    }

    #[Test]
    public function appFactoryRuntimeAcceptsInputAndProjectsCompletionState(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        $builder = Collab::app(['APP_ENV' => 'test']);
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
            $runtime = $scope->service(Runtime::class);
            $store = $scope->service(Store::class);

            self::assertInstanceOf(InputPromptSubmitter::class, $submit);
            self::assertInstanceOf(Runtime::class, $runtime);
            self::assertInstanceOf(Store::class, $store);

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

            foreach (['Phalanx\\Athena\\', 'Phalanx\\Panoply\\', 'Phalanx\\Themis\\', 'Phalanx\\Theatron\\'] as $token) {
                if (str_contains($source, $token)) {
                    $offenders[] = str_replace(dirname(__DIR__) . '/', '', $file) . " contains {$token}";
                }
            }
        }

        self::assertSame([], $offenders, implode("\n", $offenders));
    }

    #[Test]
    public function composerRequireSurfaceNamesOnlyLocalFrameworkPackage(): void
    {
        $composer = json_decode(self::read(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $packages = [];
        foreach (array_keys($composer['require']) as $package) {
            if (is_string($package) && str_starts_with($package, 'phalanx-php/')) {
                $packages[] = $package;
            }
        }

        self::assertSame(['phalanx-php/phalanx'], $packages);
    }

    /**
     * @return list<string>
     */
    private static function publicSurfaceFiles(): array
    {
        $root = dirname(__DIR__);
        $files = [
            $root . '/README.md',
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
        return 'Phalanx Collab instructions';
    }
}
