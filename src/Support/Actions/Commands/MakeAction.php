<?php

declare(strict_types=1);

namespace Support\Actions\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Stringable;
use Support\Actions\References\Action;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Tooling\Composer\ClassMap\Collectors\All;
use Tooling\GeneratorCommands\Concerns\CreatesColocatedTests;
use Tooling\GeneratorCommands\Concerns\GeneratorCommandCompatibility;
use Tooling\GeneratorCommands\Concerns\SearchesAutoloadCaches;
use Tooling\GeneratorCommands\Contracts\GeneratesFile;
use Tooling\GeneratorCommands\References\GenericClass;

#[AsCommand(name: 'make:action', description: 'Create a new action class')]
class MakeAction extends GeneratorCommand implements GeneratesFile
{
    use CreatesColocatedTests;
    use GeneratorCommandCompatibility;
    use SearchesAutoloadCaches;

    protected $type = 'Action';

    protected function collector(): string
    {
        return All::class;
    }

    public string $stub {
        get => __DIR__.'/../stubs/action.stub';
    }

    public Stringable $nameInput {
        get => str($this->argument('name'));
    }

    private GenericClass $classReference {
        get => $this->classReference ??= GenericClass::fromFqcn(str($this->argument('class')));
    }

    public protected(set) Action $action;

    public Action $reference {
        get => $this->action;
    }

    public function handle()
    {
        $this->action = new Action(
            name: $this->nameInput,
            baseNamespace: $this->classReference->namespace,
        );

        return parent::handle();
    }

    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'class' => fn () => (string) \Laravel\Prompts\search(
                label: 'Which class should this action relate to?',
                options: fn ($search) => $this->getClassSearchResults($search),
                required: true,
                scroll: 5,
            ),
        ];
    }

    /** @return array<int, InputArgument> */
    protected function getArguments(): array
    {
        return [
            new InputArgument('name', InputArgument::REQUIRED, 'The name of the action'),
            new InputArgument('class', InputArgument::REQUIRED, 'The FQCN of the related class'),
        ];
    }

    /** @return array<int, InputOption> */
    protected function getOptions(): array
    {
        return [
            new InputOption('force', 'f', InputOption::VALUE_NONE, 'Create the class even if it already exists'),
        ];
    }
}
