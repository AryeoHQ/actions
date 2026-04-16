# Actions

This package provides a unified Actions and Jobs pattern for Laravel applications, allowing units of business logic to be defined once and executed synchronously or asynchronously.

## Installation

```bash
composer require aryeo/actions
```

## Overview

Actions integrate seamlessly with Laravel's queue system, allowing you to:
- Execute actions synchronously with `Ship::make()->now()`
- Dispatch actions asynchronously with `Ship::make()->dispatch()`
- Test actions with mocked return values using `fake()`
- Use all Laravel queue features (batching, chaining, middleware, etc.)

## Usage

### Generate Actions

Actions can be generated via an artisan command:

```sh
php artisan make:action Ship
```

### Defining Actions

Actions must implement the `Action` contract and use the `AsAction` trait. The business logic goes in the `handle()` method.

Actions provide the same structural affordances customary in traditional Laravel Jobs.
- Any number of arguments can be accepted in `__construct()`
- Any number of arguments can be accepted and resolved from the container in `handle()`.

The key difference is that `handle()` additionally supports return values for use when an `Action` is executed synchronously.

**Important**: The `handle()` method should never be called directly. Always use `now()` for synchronous execution or `dispatch()` for asynchronous execution.

> To encourage designing around discrete operations an `Action` must be `final`.  If dependent but separate behaviors are needed they can be put into new `Action` classes for consumption by another.

```php
use Support\Actions\Concerns\AsAction;
use Support\Actions\Contracts\Action;

final class Ship implements Action
{
    use AsAction;

    public readonly Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): TrackingNumber
    {
        // Business logic
    }
}
```

> **Note:** All `Action` implementations extend `ShouldQueue` by default, making them queueable. You can execute any action synchronously with `now()` or asynchronously with `dispatch()`.

### Executing Actions

Actions can be executed synchronously or dispatched asynchronously:

**Synchronous execution** (returns result immediately):
```php
$result = ProcessOrder::make($order)->now();
```

**Asynchronous execution** (queues the job):
```php
// Dispatch to queue
ProcessOrder::make($order)->dispatch();

// With queue configuration
ProcessOrder::make($order)
    ->dispatch()
    ->onQueue('orders')
    ->delay(now()->addMinutes(5));
```

### Conditional Execution

Actions support conditional execution methods for cleaner code when you need to execute or dispatch based on a condition:

**Synchronous conditional execution:**
```php
// Execute only if condition is true
ProcessOrder::make($order)->nowIf($shouldProcess);

// Execute only if condition is false
ProcessOrder::make($order)->nowUnless($alreadyProcessed);
```

**Asynchronous conditional dispatch:**
```php
// Dispatch only if condition is true
ProcessOrder::make($order)->dispatchIf($shouldQueue);

// Dispatch only if condition is false
ProcessOrder::make($order)->dispatchUnless($isProcessedSync);
```

These methods return `null` when the condition is not met, or the result/PendingDispatch when executed.

```php
// Example: Process order only if payment is confirmed
$result = ProcessOrder::make($order)->nowIf($order->isPaid());

if ($result) {
    // Order was processed
}

// Example: Dispatch notification unless user opted out
NotifyUser::make($user, $message)->dispatchUnless($user->hasOptedOut());
```

## Testing

The `fake()` method provides per-action return value control, perfect for testing actions that call other actions.

### Basic Faking

```php
public function test_it_processes_order()
{
    // Fake the payment action to return a specific result
    ProcessPayment::fake()->andReturn(['payment_id' => 'test-123']);

    // Execute the action
    $result = ProcessOrder::make($order)->now();

    // Assert the payment action was dispatched
    ProcessPayment::assertFired();
}
```

### Faking with Closures

```php
public function test_it_returns_dynamic_values()
{
    // Fake with a closure for dynamic return values
    ProcessPayment::fake()->andReturn(fn() => ['payment_id' => uniqid()]);

    $result = ProcessOrder::make($order)->now();

    ProcessPayment::assertFired();
}
```

The closure can also receive the action instance as a parameter, allowing you to create dynamic return values based on the action's properties:

```php
public function test_it_uses_action_properties()
{
    // Access the action instance in the closure
    ProcessOrder::fake()->andReturn(fn($action) => [
        'order_id' => $action->order->id,
        'status' => 'processed',
    ]);

    $result = ProcessOrder::make($order)->now();

    // Returns ['order_id' => 123, 'status' => 'processed']
}
```

### Testing Nested Actions

```php
public function test_it_handles_nested_actions()
{
    // Control return values of actions called within other actions
    ValidateOrder::fake()->andReturn(['valid' => true]);
    ProcessPayment::fake()->andReturn(['payment_id' => 'test-123']);
    SendConfirmation::fake()->andReturn(['confirmation_id' => 'conf-456']);

    $result = ProcessOrder::make($order)->now();

    // All nested actions were dispatched and returned fake values
    ValidateOrder::assertFired();
    ProcessPayment::assertFired();
    SendConfirmation::assertFired();
}
```

### Testing Queued Actions

```php
public function test_it_dispatches_to_queue()
{
    ProcessOrder::fake();

    ProcessOrder::make($order)->dispatch();

    ProcessOrder::assertFired(function ($action) {
        return $action->order->id === 123;
    });
}
```

### Testing Actions Not Dispatched

```php
public function test_it_does_not_dispatch_action()
{
    ProcessOrder::fake();

    // Some logic that should not dispatch the action

    ProcessOrder::assertNotFired();
}
```

### Assertion Methods

Actions provide convenient assertion methods for testing:

```php
// Assert action was fired (with optional callback)
ProcessOrder::assertFired();
ProcessOrder::assertFired(fn(Action $action) => $action->order->id === 123);

// Assert action was not fired (with optional callback)
ProcessOrder::assertNotFired();
ProcessOrder::assertNotFired(fn(Action $action) => $action->order->id === 456);

// Assert action was fired a specific number of times
ProcessOrder::assertFiredTimes(3);
```

> **Note:** Fakes are automatically managed using Laravel's context system and work seamlessly with Laravel's Bus fake. The fake system handles both synchronous (`now()`) and asynchronous (`dispatch()`) executions.

## Lifecycle Hooks

Actions support optional `succeeded()` and `failed()` lifecycle hooks that are called automatically in both the synchronous (`now()`) and asynchronous (`dispatch()`) flows:

```php
final class ProcessOrder implements Action
{
    use AsAction;

    public readonly Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(): TrackingNumber
    {
        // Business logic
    }

    public function succeeded(): void
    {
        // Called after handle() completes successfully
    }

    public function failed(\Throwable $e): void
    {
        // Called when handle() throws an exception
    }
}
```

## Queue Features

Actions work exactly like Laravel Jobs and support all queue features including batching, chaining, middleware, rate limiting, unique jobs, encrypted jobs, and lifecycle methods. The `AsAction` trait includes:

- `Dispatchable` - Custom implementation for action dispatching
- `Fakeable` - Testing support with fake actions
- `InteractsWithQueue` - Queue interaction methods
- `Nowable` - Synchronous execution support
- `Queueable` - Queue configuration

> **Note:** `SerializesModels` is intentionally **not** included. Without it, Eloquent models passed to an action are serialized as-is — preserving the exact state at dispatch time rather than being re-fetched from the database when the job is processed. This ensures the worker operates on the data that was originally provided. If you prefer Laravel's default behavior of storing only the model identifier and rehydrating from the database at processing time, you can add `use \Illuminate\Queue\SerializesModels;` to your individual action classes.

For detailed documentation on queue features, see the [Laravel Queue Documentation](https://laravel.com/docs/queues).

### Middleware

Actions support middleware through the `$middleware` property. You can set middleware statically in the property declaration or dynamically using an optional `prepare()` method:

```php
final class ProcessOrder implements Action
{
    use AsAction;

    public readonly Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function prepare(): void
    {
        $this->through([
            new RateLimited('orders'),
            new WithoutOverlapping($this->order->id),
        ]);
    }

    public function handle(): TrackingNumber
    {
        // Business logic
    }
}
```

The `prepare()` method is called automatically before each dispatch — in `now()`, `dispatch()`, and `dispatchSync()` paths. It provides a clean place to configure middleware that depends on constructor arguments, without cluttering the constructor. For queued dispatches, `prepare()` runs before the job is sent to the queue, and the resulting `$middleware` property serializes with the job.

> **Important:** Actions cannot define a `middleware()` method. This restriction ensures that lifecycle hooks (`succeeded()`, `failed()`) always wrap the full middleware + handle lifecycle consistently across all dispatch paths. Use the `$middleware` property, `prepare()`, or `through()` instead.

## Static Analysis

PHPStan rules are automatically registered into tooling to ensure Actions follow the implementation standards:
- Actions are `final` classes (ActionMustBeFinal)
- Actions use the `AsAction` trait (ActionMustUseAsAction)
- Actions have a `handle()` method (ActionMustDefineHandleMethod)
- Classes using `AsAction` must implement the `Action` interface (AsActionMustImplementAction)
- Actions cannot use the `Dispatchable` trait (ActionCannotUseDispatchable)
- Actions cannot use the `Queueable` trait (ActionCannotUseQueueable)
- The `handle()` method cannot be called directly (ActionHandleCannotBeCalledDirectly)
- Actions cannot define a `middleware()` method (ActionCannotDefineMiddlewareMethod)

## Rector

Corresponding Rector rules are also provided as a developer convenience to automate the rules for this package that are being enforced by PHPStan.
