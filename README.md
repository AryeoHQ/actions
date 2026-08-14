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

## Architecture

For implementation details and lifecycle flow, see `docs/architecture.md`.

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

## Manually Failing an Action

Like a traditional Laravel Job, an action can mark itself as failed by calling `$this->fail()`:

```php
public function handle(): void
{
    if (! $this->order->isPayable()) {
        $this->fail('Order is not payable.');

        return;
    }

    // ...
}
```

`fail()` accepts a `Throwable`, a string (converted to a `ManuallyFailedException`), or nothing (a `ManuallyFailedException` is created for you). On every execution path the `failed()` hook is invoked exactly once with the exception, and `succeeded()` will not run. The same applies to `$this->release()` on a queued run — a released attempt is not a success, so `succeeded()` waits for the attempt that actually completes.

How the failure surfaces depends on where the action is running:

- **On a queue worker** — standard Laravel behavior: the job is marked as failed, queue bookkeeping runs (`failed_jobs`, `JobFailed`), and no exception is thrown — there is no waiting caller to interrupt.
- **Synchronously** (`now()`, `dispatchSync()`, or `dispatch()` with the `sync` queue driver) — a caller is waiting on the result, so the failure is thrown to it as the exception passed to `fail()`. Handle it with `try`/`catch` or `rescue()`:

```php
$result = rescue(fn () => ProcessOrder::make($order)->now(), $fallbackValue);
```

> **Important:** Because `fail()` throws outside the queue but returns inside it, any code written after a `fail()` call only ever executes on a queued run. Follow `fail()` with a `return` (or make it the last statement) so the action behaves identically on every path.

## Automatic Post-Execution Dispatching

An action can re-dispatch a fresh copy of itself to the queue after it runs. You opt in fluently, on the instance you want the follow-up for:

| Method | Triggers the follow-up dispatch when |
|---|---|
| `dispatchAfterSucceeded()` | the run completes successfully |
| `dispatchAfterFailed()` | the run fails |

The choice is not baked into the class — one instance can opt in while another leaves it off. It works the same on every transport (`now()`, `dispatch()`, `dispatchSync()`, any driver). The re-dispatched copy is standalone, so it does not itself re-dispatch.

```php
ProcessOrder::make($order)->dispatchAfterSucceeded()->now();

ProcessOrder::make($order)->dispatchAfterFailed()->dispatch();
```

These setters flip a flag on the instance and it stays set. So if you reuse the same instance, every later run follows up too — this runs the action twice, so it re-dispatches twice:

```php
$action = ProcessOrder::make($order)->dispatchAfterSucceeded();

$action->now(); // runs, then re-dispatches
$action->now(); // still armed, so it re-dispatches again
```

Make a fresh instance per run if you do not want this. The common `ProcessOrder::make($order)->dispatchAfterSucceeded()->now()` form already does.

The re-dispatch happens **after** the corresponding `succeeded()` / `failed()` lifecycle hook has run, so cleanup or compensation in a hook is guaranteed to complete before the follow-up copy is enqueued.

The follow-up is **one-shot**: the re-dispatched copy does not carry the flag, so it will not re-dispatch itself again. To follow up on the copy too, opt in again at that call site.

## Queue Features

Actions work exactly like Laravel Jobs and support all queue features including batching, chaining, middleware, rate limiting, unique jobs, encrypted jobs, and lifecycle methods. The `AsAction` trait includes:

- `Dispatchable` - Custom implementation for action dispatching (composes `Queueable` internally)
- `InteractsWithJob` - Job-state behavior shared by the sync and queue paths (composes `InteractsWithQueue` internally); provides `$runningInQueue`, `fail()`, `clearJob()`, `$attempts`, `$failed`, `$released`, `$failedOrReleased`, `$attemptsLimited`, `$attemptsExhausted`
- `InteractsWithChain` - provides `clearChain()`; used by `standalone()` (with `clearJob()` and `clearDispatchAfter()`) to detach a copy from its run before re-dispatch
- `Fakeable` - Testing support with fake actions
- `Nowable` - Synchronous execution support

> **Note:** `SerializesModels` is left off `AsAction` so each action can opt in for itself. Without it (the default), a dispatched model is serialized as-is, so the worker sees the model exactly as it was at dispatch. Add `use \Illuminate\Queue\SerializesModels;` to an action when you want Laravel's default instead — storing only the model identifier and rehydrating fresh from the database when the job runs.

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

Actions may also define a `middleware()` method, which is merged with `$middleware` on both the sync and queue paths. `middleware()` items run outermost (before `$middleware` items), matching Laravel's own `CallQueuedHandler` behaviour.

### Middleware Blocking on `now()`

Middleware like `WithoutOverlapping` and `RateLimited` can prevent an action from running — for example, when a lock is already held or a rate limit is exceeded. On the queue this is handled by releasing the job for a later retry. On `now()` there is no queue to retry on, so the package throws an `Interrupted` exception instead of silently returning null:

```php
use Support\Actions\Pipeline\Exceptions\Interrupted;

try {
    ProcessOrder::make($order)->now();
} catch (Interrupted $e) {
    $e->action;     // The action class-string, e.g. ProcessOrder::class
    $e->middleware;  // The middleware class-string that interrupted, e.g. WithoutOverlapping::class
}
```

The exception carries the class-string of the middleware that stopped the chain, so callers can branch on it. The package does not maintain a list of known middleware — any middleware that declines to pass control on (by not calling `$next`) is detected and attributed.

An interruption is not a success and not a failure — it means the action never ran. So neither `succeeded()` nor `failed()` is called, and no post-execution dispatch (`dispatchAfterSucceeded()` / `dispatchAfterFailed()`) fires. The `Interrupted` exception propagates to the caller, who decides what to do next (for example, catch it and `dispatch()` the action to the queue, where the middleware can release and retry).

### Unique Actions on `now()`

An action that implements `ShouldBeUnique` will not run while another copy of itself is already running. On the queue, Laravel drops the duplicate silently. On `now()` a caller is waiting on the return value, so the package throws a `Prevented` exception instead of silently returning null:

```php
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Support\Actions\Bus\Exceptions\Prevented;

final class ProcessOrder implements Action, ShouldBeUnique
{
    use AsAction;

    public function uniqueId(): string
    {
        return (string) $this->order->id;
    }

    // ...
}

try {
    ProcessOrder::make($order)->now();
} catch (Prevented $e) {
    $e->action; // The action class-string, e.g. ProcessOrder::class
}
```

Like an interruption, a prevented run never happened: `handle()` is not called, no hook runs, and nothing is re-dispatched. The lock is held only for the duration of the run and released when it ends, so a later `now()` for the same key runs normally.

`uniqueId`, `uniqueFor`, and `uniqueVia` work exactly as they do on the queue. `ShouldBeUniqueUntilProcessing` is also honored, releasing the lock as soon as the run starts rather than when it ends — matching Laravel's queue behaviour.

## Static Analysis

PHPStan rules are automatically registered into tooling to ensure Actions follow the implementation standards.

## Rector

Corresponding Rector rules are also provided as a developer convenience to automate the rules for this package that are being enforced by PHPStan.
