<?php

declare(strict_types=1);

namespace Support\Actions\Commands;

use Illuminate\Console\GeneratorCommand;

class MakeAction extends GeneratorCommand
{
    protected $name = 'make:action';

    protected $description = 'Create a new action class';

    protected $type = 'Action';

    protected function getStub(): string
    {
        return __DIR__.'/../stubs/action.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [];
    }
}
