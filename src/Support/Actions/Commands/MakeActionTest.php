<?php

declare(strict_types=1);

namespace Support\Actions\Commands;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\References\Action;
use Tests\TestCase;
use Tooling\Composer\Composer;
use Tooling\GeneratorCommands\Testing\Concerns\GeneratesFileTestCases;

#[CoversClass(MakeAction::class)]
class MakeActionTest extends TestCase
{
    use GeneratesFileTestCases;

    public Action $reference {
        get => new Action(name: 'SyncRepos', baseNamespace: 'App\\Services');
    }

    /** @var array<string, mixed> */
    public array $baselineInput {
        get => ['name' => 'SyncRepos', 'class' => 'App\\Services\\Github', '--no-test' => true];
    }

    #[Test]
    public function it_creates_an_action_with_the_correct_structure(): void
    {
        Composer::fake();

        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        $contents = File::get($this->reference->filePath->toString());

        $this->assertStringContainsString('final class SyncRepos', $contents);
        $this->assertStringContainsString('use Support\Actions\Contracts\Action;', $contents);
        $this->assertStringContainsString('implements Action', $contents);
        $this->assertStringContainsString('use AsAction;', $contents);
    }

    #[Test]
    public function it_creates_a_colocated_test(): void
    {
        Composer::fake();

        $this->artisan($this->command, ['name' => 'SyncRepos', 'class' => 'App\\Services\\Github'])->assertSuccessful();

        $this->assertTrue(File::exists($this->reference->test->filePath->toString()));
    }

    #[Test]
    public function colocated_test_can_be_suppressed(): void
    {
        Composer::fake();

        $this->artisan($this->command, $this->baselineInput)->assertSuccessful();

        $this->assertFalse(File::exists($this->reference->test->filePath->toString()));
    }
}
