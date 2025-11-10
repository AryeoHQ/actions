<?php

declare(strict_types=1);

namespace Support\Actions\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:action', description: 'Create a new action class')]
class MakeAction extends GeneratorCommand
{
    protected $type = 'Action';

    protected function getStub(): string
    {
        return __DIR__.'/../stubs/action.stub';
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }
}
