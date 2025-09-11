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

        $this->assertFileExists(app_path('Actions/TestAction.php'));
    }

    #[Test]
    public function itActionIncludesAsActionTraitAndImplementsActionInterface(): void
    {
        $this->artisan(MakeAction::class, ['name' => 'TestAction']);

        $actionClass = file_get_contents(app_path('Actions/TestAction.php'));
        $this->assertStringContainsString( 'use Support\Actions\Contracts\Action;', $actionClass);
        $this->assertStringContainsString( 'implements Action', $actionClass);
        $this->assertStringContainsString( 'use AsAction;', $actionClass);
    }
}