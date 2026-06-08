<?php

declare(strict_types=1);

namespace App\AgentHarness\Tests;

use App\AgentHarness\AgentHarness;
use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Scope\ExecutionScope;
use Phalanx\Scope\TaskScope;
use Phalanx\Stream\ResourceHandle;
use Phalanx\Stream\Stream;
use Phalanx\Testing\PhalanxTestCase;
use Phalanx\Tui\Apps\App;
use Phalanx\Tui\Drawing\ScreenMode;
use Phalanx\Tui\Drawing\StageConfig;
use Phalanx\Tui\Runtime\Apps\Application;
use Phalanx\Tui\Runtime\Apps\Builder;
use Phalanx\Tui\Runtime\Apps\Runtime;
use Phalanx\Tui\Runtime\Boundaries\InputPromptSubmitter;
use Phalanx\Tui\Runtime\Participants\AgentParticipant;
use Phalanx\Tui\Runtime\Plans\Activity;
use Phalanx\Tui\Runtime\Plans\WorkItem;
use Phalanx\Tui\Runtime\Plans\WorkPlanItem;
use Phalanx\Tui\Runtime\Plans\WorkPlanStatus;
use Phalanx\Tui\Runtime\Plans\WorkResult;
use Phalanx\Tui\Runtime\Prompts\FilePrompt;
use Phalanx\Tui\Runtime\Prompts\PromptSource;
use Phalanx\Tui\Runtime\State\Store;
use Phalanx\Tui\Runtime\WorkContext;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('agent-harness')]
final class StarterSmokeTest extends PhalanxTestCase
{
    /** @var list<ResourceHandle> */
    private array $stageStreams = [];

    #[Test]
    public function agentUsesPromptSourceInstructions(): void
    {
        $agent = new Agent(new StarterPromptSource());
        $item = WorkPlanItem::pending(new WorkItem(Activity::Thinking, 'Draft a plan', id: 'work_test'));
        $ctx = new WorkContext($this->createStub(TaskScope::class), new Store());
        $result = $agent($ctx, $item);

        self::assertSame('assistant', $agent->id);
        self::assertSame('Assistant', $agent->name);
        self::assertSame('Assistant received: Draft a plan', $result->summary);
        self::assertIsArray($result->payload);
        self::assertSame('Phalanx Agent Harness instructions', $result->payload['prompt']);
    }

    #[Test]
    public function agentImplementsRuntimeContract(): void
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

            return $agent($ctx, $item);
        });

        self::assertSame('Assistant received: Use shipped prompt', $result->summary);
        self::assertIsArray($result->payload);
        self::assertStringStartsWith('file:', $result->payload['instructions']);
        self::assertStringContainsString('concise assistant', $result->payload['prompt']);
    }

    #[Test]
    public function appFactoryBuildsRuntimeBuilder(): void
    {
        self::assertInstanceOf(Builder::class, AgentHarness::app());
    }

    #[Test]
    public function appFactoryBuildProducesRuntimeApplication(): void
    {
        $app = AgentHarness::app()
            ->stageConfig($this->stageConfig())
            ->build();

        self::assertInstanceOf(Application::class, $app);
        self::assertInstanceOf(App::class, $app->tui());
    }

    #[Test]
    public function appFactoryRuntimeAcceptsInputAndProjectsCompletionState(): void
    {
        $builder = AgentHarness::app(['APP_ENV' => 'test'])
            ->stageConfig($this->stageConfig());
        $app = $builder->build();

        $app->runtime()->run(static function (ExecutionScope $scope): void {
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
        $composer = self::composer();
        $packages = [];
        foreach (array_keys($composer['require']) as $package) {
            if (is_string($package) && str_starts_with($package, 'phalanx-php/')) {
                $packages[] = $package;
            }
        }

        self::assertSame(['phalanx-php/phalanx'], $packages);
    }

    #[Test]
    public function agentHarnessIdentityIsCanonical(): void
    {
        $composer = self::composer();

        self::assertSame('phalanx-php/agent-harness', $composer['name']);
        self::assertContains('agent-harness', $composer['keywords']);
        self::assertSame('agent-harness', self::configName());
    }

    #[Test]
    public function publishComposerMetadataUsesReleasedFrameworkConstraint(): void
    {
        $composer = self::publishComposer();

        self::assertArrayNotHasKey('repositories', $composer);
        self::assertSame('^0.7', $composer['require']['phalanx-php/phalanx']);
    }

    #[Test]
    public function localComposerMetadataUsesSourceLinkedFrameworkPackage(): void
    {
        $composer = self::composer();
        $repository = $composer['repositories'][0];

        self::assertSame('0.7.x-dev', $composer['require']['phalanx-php/phalanx']);
        self::assertSame('path', $repository['type']);
        self::assertSame('../../phalanx', $repository['url']);
        self::assertTrue($repository['options']['symlink']);
        self::assertSame('0.7.x-dev', $repository['options']['versions']['phalanx-php/phalanx']);
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

    /**
     * @return array<string, mixed>
     */
    private static function composer(): array
    {
        $composer = json_decode(self::read(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        return $composer;
    }

    /**
     * @return array<string, mixed>
     */
    private static function publishComposer(): array
    {
        $composer = self::composer();
        unset($composer['repositories']);
        $composer['require']['phalanx-php/phalanx'] = '^0.7';

        return $composer;
    }

    private static function configName(): string
    {
        preg_match('/^name\s*=\s*"([^"]+)"/m', self::read(dirname(__DIR__) . '/config/phalanx.toml'), $matches);

        return $matches[1] ?? '';
    }

    #[After]
    public function closeStageStreams(): void
    {
        foreach ($this->stageStreams as $stream) {
            $stream->close();
        }

        $this->stageStreams = [];
    }

    private function stageConfig(): StageConfig
    {
        $stream = Stream::memoryBuffer();
        $this->stageStreams[] = $stream;

        return new StageConfig(
            screenMode: ScreenMode::Inline,
            handleInput: false,
            stream: $stream->resource(),
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        );
    }
}

final class StarterPromptSource implements PromptSource
{
    public string $id {
        get => 'starter';
    }

    public function __invoke(TaskScope $scope): string
    {
        return 'Phalanx Agent Harness instructions';
    }
}
