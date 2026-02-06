<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Commands;

use Illuminate\Console\GeneratorCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Support\Actions\Commands\MakeAction;
use Tests\TestCase;

#[CoversClass(MakeAction::class)]
class MakeActionTest extends TestCase
{
    protected function tearDown(): void
    {
        $testActionPath = app_path('Actions/TestAction.php');

        if (file_exists($testActionPath)) {
            unlink($testActionPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function command_is_instance_of_generator_command(): void
    {
        $this->assertInstanceOf(GeneratorCommand::class, app(MakeAction::class));
    }

    #[Test]
    public function it_can_make_an_action(): void
    {
        $this->artisan(MakeAction::class, ['name' => 'TestAction']);

        $this->assertFileExists(app_path('Actions/TestAction.php'), 'The action was not created');
    }

    #[Test]
    public function it_action_includes_as_action_trait_and_implements_action_interface(): void
    {
        $this->artisan(MakeAction::class, ['name' => 'TestAction']);

        $actionClass = file_get_contents(app_path('Actions/TestAction.php'));
        $this->assertStringContainsString('final class TestAction', $actionClass, 'The action does not define the class as final');
        $this->assertStringContainsString('use Support\Actions\Contracts\Action;', $actionClass, 'The action does not import the Action interface');
        $this->assertStringContainsString('implements Action', $actionClass, 'The action does not implement the Action interface');
        $this->assertStringContainsString('use Illuminate\Contracts\Queue\ShouldQueue;', $actionClass, 'The action does not import the ShouldQueue interface');
        $this->assertStringContainsString('implements Action, ShouldQueue', $actionClass, 'The action does not implement the ShouldQueue interface');
        $this->assertStringContainsString('use AsAction;', $actionClass, 'The action does not use the AsAction trait');
    }
}
