# Actions

This package provides a unified Actions and Jobs pattern for Laravel applications, allowing units of business logic to defined once while be executable synchronously or asynchronously..

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

For asynchronous execution, implement Laravel's `ShouldQueue` interface:

```php
use Illuminate\Contracts\Queue\ShouldQueue;

final class ProcessOrder implements Action, ShouldQueue
{
    use AsAction;

    public function handle(): void
    {
        // When dispatched, this will be pushed to the queue
    }
}
```

> **Note:** Implementing `ShouldQueue` tells Laravel to queue the action when `dispatch()` is called. You can still execute it synchronously using `now()`, bypassing the queue entirely.

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
use Illuminate\Support\Facades\Bus;

public function test_it_processes_order()
{
    // Fake the payment action to return a specific result
    ProcessPayment::fake(['payment_id' => 'test-123']);

    // Execute the action
    $result = ProcessOrder::make($order)->now();

    // Assert the payment action was dispatched
    Bus::assertDispatched(ProcessPayment::class);
}
```

### Faking with Closures

```php
public function test_it_returns_dynamic_values()
{
    // Fake with a closure for dynamic return values
    ProcessPayment::fake(fn() => ['payment_id' => uniqid()]);

    $result = ProcessOrder::make($order)->now();

    Bus::assertDispatched(ProcessPayment::class);
}
```

### Testing Nested Actions

```php
public function test_it_handles_nested_actions()
{
    // Control return values of actions called within other actions
    ValidateOrder::fake(['valid' => true]);
    ProcessPayment::fake(['payment_id' => 'test-123']);
    SendConfirmation::fake(['confirmation_id' => 'conf-456']);

    $result = ProcessOrder::make($order)->now();

    // All nested actions were dispatched and returned fake values
    Bus::assertDispatched(ValidateOrder::class);
    Bus::assertDispatched(ProcessPayment::class);
    Bus::assertDispatched(SendConfirmation::class);
}
```

### Testing Queued Actions

```php
public function test_it_dispatches_to_queue()
{
    ProcessOrder::fake();

    ProcessOrder::make($order)->dispatch();

    Bus::assertDispatched(ProcessOrder::class, function ($action) {
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

    Bus::assertNotDispatched(ProcessOrder::class);
}
```

> **Note:** Fakes are automatically cleaned up after each test via Laravel's `setUpTraits()` lifecycle hook. No manual cleanup required.

## Advanced Queue Features

Actions are fully compatible with all Laravel queue features since they're dispatched through Laravel's dispatcher.

### Job Batching

Group multiple actions into a batch:

```php
use Illuminate\Support\Facades\Bus;

Bus::batch([
    ProcessInvoice::make($invoice1),
    ProcessInvoice::make($invoice2),
    ProcessInvoice::make($invoice3),
])->then(function (Batch $batch) {
    // All jobs completed successfully
})->catch(function (Batch $batch, Throwable $e) {
    // First batch job failure
})->finally(function (Batch $batch) {
    // Batch finished executing
})->dispatch();
```

### Job Chaining

**External Chaining** (asynchronous, no return values):
```php
// Jobs run sequentially on the queue
Bus::chain([
    ValidateOrder::make($order),
    ProcessPayment::make($order),
    SendConfirmation::make($order),
])->dispatch();
```

**Internal Chaining** (synchronous, with return values):
```php
// Actions execute immediately and pass values
class ProcessOrder
{
    public function handle()
    {
        $validated = ValidateOrder::make($this->order)->now();
        $payment = ProcessPayment::make($validated)->now();
        return SendConfirmation::make($payment)->now();
    }
}
```

Use external chaining for asynchronous workflows without return value dependencies. Use internal chaining when you need to pass return values between actions.

### Job Middleware

Add middleware to control job execution:

```php
use Illuminate\Queue\Middleware\RateLimited;

class ProcessOrder implements Action
{
    use AsAction;

    public function middleware(): array
    {
        return [new RateLimited('orders')];
    }

    public function handle(): void
    {
        // Process order
    }
}
```

### Lifecycle Methods

Control job retry behavior and handle failures:

```php
class ProcessOrder implements Action
{
    use AsAction;

    public $tries = 3;
    public $timeout = 120;
    public $maxExceptions = 2;

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(10);
    }

    public function backoff(): array
    {
        return [1, 5, 10]; // Retry after 1, 5, and 10 seconds
    }

    public function failed(Throwable $exception): void
    {
        // Handle job failure
        Log::error('Order processing failed', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Job Traits

Use Laravel's job traits for additional functionality:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class ProcessOrder implements Action, ShouldQueue
{
    use AsAction, Queueable, SerializesModels, InteractsWithQueue;

    public function handle(): void
    {
        // Access job instance
        $attempts = $this->attempts();

        // Release back to queue
        if ($someCondition) {
            $this->release(30); // Try again in 30 seconds
        }

        // Delete the job
        if ($shouldStop) {
            $this->delete();
        }
    }
}
```

### Unique Jobs

Ensure only one instance of a job runs at a time:

```php
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ProcessOrder implements Action, ShouldQueue, ShouldBeUnique
{
    use AsAction;

    public function uniqueId(): string
    {
        return $this->order->id;
    }

    public function uniqueFor(): int
    {
        return 3600; // Lock for 1 hour
    }
}
```

### Encrypted Jobs

Encrypt sensitive job data:

```php
use Illuminate\Contracts\Queue\ShouldBeEncrypted;

class ProcessPayment implements Action, ShouldQueue, ShouldBeEncrypted
{
    use AsAction;

    public function handle(): void
    {
        // Sensitive payment data is encrypted
    }
}
```

For more queue features, see the [Laravel Queue Documentation](https://laravel.com/docs/queues).

## Static Analysis

PHPStan rules are automatically registered into tooling to ensure Actions follow the implementation standards:
- Actions are `final` classes (ActionMustBeFinal)
- Actions use the `AsAction` trait (ActionMustUseAsAction)
- Actions have a `handle()` method (ActionMustDefineHandleMethod)
- Classes using `AsAction` must implement the `Action` interface (AsActionMustImplementAction)
- Classes implementing `Action` must also implement `ShouldQueue` (ActionMustImplementShouldQueue)
- Classes implementing `ShouldQueue` must also implement `Action` (ShouldQueueMustImplementAction)

## Rector

Corresponding Rector rules are also provided as a developer convenience to automate the rules for this package that are being enforced by PHPStan.
