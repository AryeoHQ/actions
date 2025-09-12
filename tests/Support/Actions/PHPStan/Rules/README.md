# PHPStan Rule Testing

This directory contains tests for custom PHPStan rules.

## ActionRuleTest

Tests the `Support\Actions\PHPStan\Rules\ActionRule` which validates that Action classes:

1. Are declared as `final`
2. Implement the `execute()` method
3. Use the `Support\Actions\Concerns\AsAction` trait
4. Implement the `Support\Actions\Contracts\Action` interface

## Running the Tests

```bash
# Run just the PHPStan rule tests
vendor/bin/phpunit tests/Support/Actions/PHPStan/Rules/ActionRuleTest.php

# Run all tests
vendor/bin/phpunit
```

## Test Data Files

The test data files in the `data/` directory contain various PHP class examples that are analyzed by the PHPStan rule:

- `valid-action.php` - A properly implemented Action class
- `not-final-action.php` - Action class missing `final` keyword
- `missing-execute-method.php` - Action class without `execute()` method
- `missing-as-action-trait.php` - Action class not using the AsAction trait
- `missing-all-requirements.php` - Action class missing all requirements
- `non-action-class.php` - Regular class that doesn't implement Action (should be ignored)
- `complete-action.php` - Complete Action class with all requirements

## Adding New Tests

To add new test cases:

1. Create a new PHP file in the `data/` directory with the code you want to test
2. Add a new test method in `ActionRuleTest.php` that calls `$this->analyse()` with the file path and expected errors
3. Update the `composer.json` autoload-dev files section to include the new test data file
4. Run `composer dump-autoload` to update the autoloader
