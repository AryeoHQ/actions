# Actions
This package provides the base for Actions in Laravel applications.

## Installation
```bash
composer require aryeo/actions
```

## Overview
This package offers a simple, testable pattern for encapsulating business logic as reusable "actions" in Laravel applications.

## Usage

### Genrate Actions

Actions can be generated via an artisan command

```sh
php artisan make:action MyAction
```

### Defining Actions

Actions must define an `execute()` method, implement the `Action` contract and use the `AsAction` trait. 

Any number of arguments can be added to the execute method as parameters and they can return any data type. The `execute()` method
is not defined in the `Action` contract for flexibility in implementation.

All action classes will be `final` as our internal standards are to not use inhertance for actions but rather create new actions should there be a need fr different behavior.

```php
final class MyAction implements Action
{
    use AsAction;

    /**
     * Execute the action.
     */
    public function execute()
    {
        // The business logic goes here.
    }
}
```

### Executing Actions

Actions can be executed in two ways.

Directly
```php
MyAction::make()->execute();
```

Conditionally
```php
MyAction::make()->executeIf(shouldExecute: true);
```

## Testing

Fluent testing helpers are provided to mock and assert action execution.

`shouldExecute()` returns an instance of the Mockery `ExceptionInterface`, which allows you to chain any additional Mockery assertions.

### Examples

1. Asserting the action executes
```php
MyAction::shouldExecute()
    ->once();
```

2. Asserting the action doesn't execute
```php
MyAction::shouldExecute()
    ->never();
```

3. Asserting the action accepts args and returns data
```php
MyAction::shouldExecute()
    ->withArgs(['foor'])
    ->once()
    ->andReturns('bar');
```

## Static Analysis

A custom PHPStan rules is available to add to your projects to ensure Actions follow the implementation standards.

The rule can be added to your projects `phpstan.neon` to the `rules` key.

```yml
rules:
    - Tooling\PHPStan\Rules\ActionRule
```

## Rector

A custom Rector rule is available to add to your projects `rector.php` file.

```php
use Rector\Config\RectorConfig;
use Tooling\Rector\Rules\AddContractAndTraitForActions;

return RectorConfig::configure()
    ->withRules([
        AddContractAndTraitForActions::class
        //..
    ])
    //..
```
