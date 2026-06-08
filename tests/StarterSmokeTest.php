<?php

declare(strict_types=1);

namespace App\AgentHarness\Tests;

use App\AgentHarness\AgentHarness;
use App\AgentHarness\Agents\Assistant\Agent;
use Phalanx\Scope\ExecutionScope;
use Phalanx\Scope\TaskScope;
use Phalanx\Stream\ResourceHandle;
use Phalanx\Stream\Stream;
use Phalanx\Testing\FixtureFile;
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
    public function publicAppCodeUsesCurrentTuiEntryBuilder(): void
    {
        $source = self::read(dirname(__DIR__) . '/app/AgentHarness.php');

        self::assertStringContainsString('use Phalanx\\Tui\\Tui;', $source);
        self::assertStringContainsString('return Tui::starting($context)', $source);
        self::assertStringContainsString('->primary(new Agent(', $source);
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
        $composer = self::generatedPublishComposer();

        self::assertArrayNotHasKey('repositories', $composer);
        self::assertSame(self::publishConstraint($composer), $composer['require']['phalanx-php/phalanx']);
    }

    #[Test]
    public function localComposerMetadataUsesSourceLinkedFrameworkPackage(): void
    {
        $composer = self::composer();
        $repository = $composer['repositories'][0];

        self::assertSame(self::branchAlias($composer), $composer['require']['phalanx-php/phalanx']);
        self::assertSame('path', $repository['type']);
        self::assertSame('../../phalanx', $repository['url']);
        self::assertTrue($repository['options']['symlink']);
        self::assertSame(self::branchAlias($composer), $repository['options']['versions']['phalanx-php/phalanx']);
    }

    private static function read(string $file): string
    {
        return FixtureFile::read($file);
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
    private static function generatedPublishComposer(): array
    {
        $lines = [];
        exec(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(dirname(__DIR__) . '/tools/release-composer.php') . ' --stdout',
            $lines,
            $exitCode,
        );
        self::assertSame(0, $exitCode);

        $composer = json_decode(implode("\n", $lines), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);

        return $composer;
    }

    /**
     * @param array<string, mixed> $composer
     */
    private static function branchAlias(array $composer): string
    {
        $alias = $composer['extra']['branch-alias']['dev-main'] ?? null;
        self::assertIsString($alias);
        self::assertMatchesRegularExpression('/^\d+\.\d+\.x-dev$/', $alias);

        return $alias;
    }

    /**
     * @param array<string, mixed> $composer
     */
    private static function publishConstraint(array $composer): string
    {
        $alias = self::branchAlias($composer);

        return '^' . str_replace('.x-dev', '', $alias);
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
