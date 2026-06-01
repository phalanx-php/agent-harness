<?php

declare(strict_types=1);

namespace App\AgentCollab\Tests;

use App\AgentCollab\AgentCollab;
use App\AgentCollab\Agents\Assistant\Agent;
use Phalanx\Scope\TaskScope;
use Phalanx\Theatron\Collab\Apps\CollabBuilder;
use Phalanx\Theatron\Collab\Participants\Collaborator;
use Phalanx\Theatron\Collab\Plans\Activity;
use Phalanx\Theatron\Collab\Plans\WorkItem;
use Phalanx\Theatron\Collab\Plans\WorkPlanItem;
use Phalanx\Theatron\Collab\Prompts\PromptSource;
use Phalanx\Theatron\Collab\State\CollabStore;
use Phalanx\Theatron\Collab\WorkContext;
use Phalanx\Theatron\Tui\Apps\TheatronApp;
use Phalanx\Theatron\Tui\Drawing\StageConfig;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('collab')]
final class StarterSmokeTest extends TestCase
{
    #[Test]
    public function agentUsesPromptSourceInstructions(): void
    {
        $agent = new Agent(new StarterPromptSource());
        $item = WorkPlanItem::pending(new WorkItem(Activity::Thinking, 'Draft a plan', id: 'work_test'));
        $ctx = new WorkContext($this->createStub(TaskScope::class), new CollabStore());
        $result = $agent($item, $ctx);

        self::assertSame('assistant', $agent->id);
        self::assertSame('Assistant', $agent->name);
        self::assertSame('Assistant received: Draft a plan', $result->summary);
        self::assertIsArray($result->payload);
        self::assertSame('Phalanx AgentCollab instructions', $result->payload['prompt']);
    }

    #[Test]
    public function agentImplementsCollabContract(): void
    {
        self::assertInstanceOf(Collaborator::class, new Agent(new StarterPromptSource()));
    }

    #[Test]
    public function appFactoryBuildsCollabBuilder(): void
    {
        self::assertInstanceOf(CollabBuilder::class, AgentCollab::app());
    }

    #[Test]
    public function appFactoryBuildProducesTheatronApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        self::assertIsResource($stream);

        $app = AgentCollab::app();
        $app->stageConfig(new StageConfig(
            handleInput: false,
            defaultExitHandler: false,
            stream: $stream,
            env: ['COLUMNS' => '80', 'LINES' => '24'],
        ));

        self::assertInstanceOf(TheatronApp::class, $app->build());
    }

    #[Test]
    public function publicAppCodeDoesNotExposeProviderRuntimeImports(): void
    {
        $offenders = [];

        foreach (self::appFiles() as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);

            foreach (['Phalanx\\Athena\\', 'Phalanx\\Panoply\\', 'Phalanx\\Themis\\'] as $token) {
                if (str_contains($source, $token)) {
                    $offenders[] = str_replace(dirname(__DIR__) . '/', '', $file) . " contains {$token}";
                }
            }
        }

        self::assertSame([], $offenders, implode("\n", $offenders));
    }

    /**
     * @return list<string>
     */
    private static function appFiles(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__) . '/app'));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }
}

final class StarterPromptSource implements PromptSource
{
    public string $id {
        get => 'starter';
    }

    public function __invoke(TaskScope $scope): string
    {
        return 'Phalanx AgentCollab instructions';
    }
}
