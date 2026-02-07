<?php

declare(strict_types=1);

namespace Tests\Support\Actions\Concerns;

use Illuminate\Support\Facades\Bus;
use PHPUnit\Framework\Attributes\Test;
use Tests\Fixtures\Orders\Actions\Ship;
use Tests\Fixtures\Orders\Actions\Archive;

trait AsActionCases
{
    use DispatchableCases;
    use NowableCases;

    #[Test]
    public function it_is_makeable(): void
    {
        $action = Archive::make('test');

        $this->assertInstanceOf(Archive::class, $action);
    }

    #[Test]
    public function it_works_when_handle_has_dependency_injection(): void
    {
        $result = Ship::make('test')->now();

        $this->assertEquals('test shipped', $result);
    }

    #[Test]
    public function it_works_when_faked_with_dependency_injection(): void
    {
        Ship::fake('mocked-value');

        $result = Ship::make('test')->now();

        $this->assertEquals('mocked-value', $result);
    }

    #[Test]
    public function it_works_when_dependency_injection_action_is_faked_but_non_dependency_injection_action_is_not_faked(): void
    {
        Ship::fake();

        $result = Archive::make('test')->now();

        $this->assertEquals('test archived', $result);
    }

    #[Test]
    public function it_works_when_non_dependency_injection_action_is_faked_but_dependency_injection_action_is_not_faked(): void
    {
        Archive::fake();

        $result = Ship::make('test')->now();

        $this->assertEquals('test shipped', $result);
    }
}
