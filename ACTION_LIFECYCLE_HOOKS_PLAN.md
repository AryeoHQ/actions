# Plan: Action Lifecycle Hooks (`succeeded` / `failed`)

## TL;DR

Add `succeeded()` and `failed()` lifecycle hooks to the Actions package that work in both `->now()` (sync) and `->dispatch()` (queued) contexts. `succeeded()` runs after the action completes without throwing. `failed()` runs when the action throws. Both are opt-in via `method_exists()`.

## Context

- `RecordEvent` needs to split `writeToDatabase()` (in `handle()`) from `$log->status->prepare()->now()` (in `succeeded()`) so that the `rescue` fallback in the Dispatcher and `afterRollBack` protection remain cleanly separated.
- Laravel's `CallQueuedHandler` already calls `$command->failed($e)` for queued jobs, but the sync path (`dispatchNow`) has no lifecycle hooks at all.

## Design

### `Nowable` — split `now()` and `dispatchNow()`

`now()` owns the lifecycle hooks. `dispatchNow()` is the raw dispatch — a protected override point for consumers that need to wrap `handle()` in additional logic.

`failed()` is wrapped in `rescue(report: true)` so that if `failed()` itself throws, its exception is reported but does not replace the original causal exception. `failed()` is a notification hook — it must never swallow or replace the real error.

```php
trait Nowable
{
    public function now(): mixed
    {
        try {
            $result = $this->dispatchNow();
        } catch (Throwable $e) {
            if (method_exists($this, 'failed')) {
                rescue(fn () => $this->failed($e), report: true);
            }
            throw $e;
        }

        if (method_exists($this, 'succeeded')) {
            $this->succeeded();
        }

        return $result;
    }

    protected function dispatchNow(): mixed
    {
        return app(Dispatcher::class)->dispatchNow($this);
    }

    public function nowIf(bool $condition): mixed
    {
        return match ($condition) {
            true => $this->now(),
            false => null,
        };
    }

    public function nowUnless(bool $condition): mixed
    {
        return $this->nowIf(! $condition);
    }
}
```

### `Dispatchable` — absorb queue traits, guard `through()`, append lifecycle middleware

```php
trait Dispatchable
{
    use InteractsWithQueue;
    use Queueable {
        Queueable::through as private queueableThrough;
    }

    public function dispatch(): PendingDispatch
    {
        $this->middleware = array_merge($this->middleware, [new ActionLifecycleMiddleware]);

        return new PendingDispatch($this);
    }

    public function through($middleware)
    {
        throw new \BadMethodCallException(
            'Actions do not support through(). Use the middleware() method instead.'
        );
    }

    public function dispatchIf(bool $condition): null|PendingDispatch
    {
        return match ($condition) {
            true => $this->dispatch(),
            false => null,
        };
    }

    public function dispatchUnless(bool $condition): null|PendingDispatch
    {
        return $this->dispatchIf(! $condition);
    }
}
```

### `AsAction` — simplified composition

```php
trait AsAction
{
    use Dispatchable; // brings Queueable + InteractsWithQueue
    use Fakeable;
    use Nowable;

    public static function make(mixed ...$arguments): static
    {
        return new static(...$arguments);
    }
}
```

### `ActionLifecycleMiddleware` — queued `succeeded()` hook

The middleware simply calls `succeeded()` after `$next()` returns. It has no knowledge of transactions or other consumer-specific concerns.

```php
class ActionLifecycleMiddleware
{
    public function handle($command, $next)
    {
        $next($command);

        if (method_exists($command, 'succeeded')) {
            rescue(fn () => $command->succeeded());
        }
    }
}
```

`failed()` is not called here — `CallQueuedHandler` already handles that natively.

### Consumer example

```php
final class RecordEvent implements Action
{
    use AsAction;

    public function handle(): void
    {
        // write to database
    }

    public function succeeded(): void
    {
        // prepare the log — only runs if handle() succeeded
    }

    public function failed(Throwable $e): void
    {
        // handle failure
    }
}

// Both paths get hooks automatically:
RecordEvent::make($event)->now();
RecordEvent::make($event)->dispatch();
```

## Steps

### Phase 1: Sync — modify `Nowable`
- [x] Split `now()` into two methods: `now()` (hooks) and `dispatchNow()` (raw dispatch, protected)
- [x] `now()`: call `$this->succeeded()` after `dispatchNow()` returns successfully (if method exists)
- [x] `now()`: call `$this->failed($e)` inside `rescue(report: true)` if `dispatchNow()` throws (if method exists), then re-throw the original exception

### Phase 2: Refactor trait composition — move `Queueable` and `InteractsWithQueue` into `Dispatchable`
- [x] Move `use Queueable` and `use InteractsWithQueue` from `AsAction` into `Dispatchable`
- [x] Alias `Queueable::through` as `private queueableThrough` in `Dispatchable`
- [x] Override `through()` in `Dispatchable` to throw `\BadMethodCallException` — consumers must use `middleware()` instead
- [x] Simplify `AsAction` — remove `use Queueable` and `use InteractsWithQueue` since `Dispatchable` brings them

### Phase 3: Queued — job middleware for `succeeded()`
- [x] Create `ActionLifecycleMiddleware` — after `$next($command)`, call `$command->succeeded()` inside `rescue()` (if method exists)
- [x] Append `ActionLifecycleMiddleware` to `$this->middleware` property in `Dispatchable::dispatch()`. `failed()` already works in the queue context — `CallQueuedHandler` natively calls `$command->failed($e)`

### Phase 4: Tests
- [x] Add sync lifecycle test cases to `NowableTestCases` — succeeded called, failed called, exception re-thrown, failed() exception reported but original preserved
- [x] Add queued lifecycle test cases to `DispatchableTestCases`
- [x] Add test that `through()` throws `\BadMethodCallException`

## Relevant files

- `src/Support/Actions/Concerns/Nowable.php` — split `now()` / `dispatchNow()`, add lifecycle calls
- `src/Support/Actions/Concerns/Dispatchable.php` — absorb `Queueable` + `InteractsWithQueue`, guard `through()`, append lifecycle middleware in `dispatch()`
- `src/Support/Actions/Concerns/AsAction.php` — simplify: remove `Queueable` + `InteractsWithQueue`
- New file: `ActionLifecycleMiddleware` class
- `src/Support/Actions/Concerns/NowableTestCases.php` — new tests
- `src/Support/Actions/Concerns/DispatchableTestCases.php` — new tests

## Decisions

- **Naming**: `succeeded()` pairs with `failed()` — direct opposites, same grammatical pattern
- **Opt-in**: Both hooks are opt-in via `method_exists()` — no interface required
- **`failed()` in sync**: Wrapped in `rescue(report: true)` — if `failed()` itself throws, the exception is reported but does not replace the original causal exception. `failed()` is a notification hook, not an error handler
- **`failed()` in queue**: Already works natively via `CallQueuedHandler` — no changes needed
- **`succeeded()` failure in queue**: Swallowed via `rescue()` so it can't fail the job
- **`succeeded()` failure in sync**: Propagates, so callers like `rescue()` in the Dispatcher can catch it
- **Middleware strategy**: Lifecycle middleware appended to `$middleware` property via `Dispatchable::dispatch()`. `through()` is overridden to throw, preventing consumers from accidentally overwriting the property. Consumers use the `middleware()` method for their own middleware, which `CallQueuedHandler` merges with the property
- **`dispatchNow()` as override point**: `Nowable` exposes `protected dispatchNow()` so consumers can wrap the raw dispatch without overriding `now()`. Hooks fire at whatever scope `dispatchNow()` covers
