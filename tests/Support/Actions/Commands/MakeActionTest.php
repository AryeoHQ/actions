<?php

namespace Tests\Support\Actions\Commands;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Console\GeneratorCommand;
use Support\Actions\Commands\MakeAction;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MakeAction::class)]
class MakeActionTest extends TestCase
{
    #[Test]
    public function commanIsInstanceOfGeneratorCommand(): void
    {
        $this->assertInstanceOf(GeneratorCommand::class, app(MakeAction::class));
    }

    #[Test]
    public function itCanMakeAnAction(): void
    {
        $this->artisan(MakeAction::class, ['name' => 'TestAction']);

        $this->assertFileExists(app_path('Actions/TestAction.php'), 'The action was not created');
    }

    #[Test]
    public function itActionIncludesAsActionTraitAndImplementsActionInterface(): void
    {
        $this->artisan(MakeAction::class, ['name' => 'TestAction']);

        $actionClass = file_get_contents(app_path('Actions/TestAction.php'));
        $this->assertStringContainsString( 'final class TestAction', $actionClass, 'The action does not define the class as final');
        $this->assertStringContainsString( 'use Support\Actions\Contracts\Action;', $actionClass, 'The action does not import the Action interface');
        $this->assertStringContainsString( 'implements Action', $actionClass, 'The action does not implement the Action interface');
        $this->assertStringContainsString( 'use AsAction;', $actionClass, 'The action does not use the AsAction trait');
    }
}