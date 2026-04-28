# Actions System Architecture

## Overview

This package provides a unified Action pattern for Laravel with two execution modes:
- `->now()` for synchronous execution with return values.
- `->dispatch()` for queue dispatch semantics with Laravel `PendingDispatch` support.

All actions implement `Support\Actions\Contracts\Action` and typically use `Support\Actions\Concerns\AsAction`.

## Contract Surface

`Support\Actions\Contracts\Action` extends `ShouldQueue` and requires:
- `make(...$arguments): static`
- `dispatch(): PendingDispatch`
- `now(): mixed`
- `runningInQueue(): bool`
- `prepareFor(Invocation $via): static`
- `through($middleware)`
- `clearJob(): static`

## Core Traits

### AsAction

Composes:
- `Dispatchable`
- `Fakeable`
- `HasLifecycle`
- `Nowable`

Also provides `make()`.

### Nowable

- `now()` calls `Illuminate\Contracts\Bus\Dispatcher::dispatchNow($this)`.
- `nowIf()` and `nowUnless()` provide conditional synchronous execution.

### Dispatchable

- Uses Laravel `Queueable` + `InteractsWithQueue`.
- `dispatch()` returns `PendingDispatch`.
- `runningInQueue()` checks for non-null job and excludes `SyncJob`.
- `dispatchIf()` and `dispatchUnless()` provide conditional dispatch.

### HasLifecycle

Lifecycle preparation is owned in this trait.

- `prepareFor(Invocation $via)`:
  - clears stale job state via `clearJob()`
  - calls optional consumer `prepare()` if present
  - injects lifecycle middleware for the invocation (`Now`, `Dispatch`, or `Sync`)
- `throughLifecycleMiddleware()` prepends lifecycle middleware and preserves non-lifecycle middleware.
- `removeLifecycleMiddleware()` strips previously applied lifecycle middleware via the `Lifecycle` marker interface.

### Fakeable

- `fake()` returns an action fake registration object.
- `assertFired()`, `assertNotFired()`, `assertFiredTimes()` delegate to Laravel Bus fake assertions.

## Dispatcher Decoration

`Support\Actions\Bus\Dispatcher` decorates Laravel's queueing dispatcher and is bound by the package provider.

### `dispatchNow()` path

- Non-actions pass directly through.
- Re-entry guard: if `$command->job` is already set, dispatch passes through directly.
- Otherwise:
  - `prepareFor(Invocation::Now)` is called.
  - Pipeline executes through command middleware.
  - `finally()` clears the job.
  - command is dispatched through the decorated dispatcher.

### `dispatch()` path

- For actions, `prepareFor(Invocation::Dispatch)` is called before dispatching.
- Then call delegates to the decorated dispatcher.

### `dispatchSync()` path

- For actions, `prepareFor(Invocation::Sync)` is called before dispatching.
- Then call delegates to the decorated dispatcher.
- `RunFailed` is intentionally excluded here; Laravel queue failure handling invokes `failed()` for sync-dispatched queueable jobs.

## Invocation Mapping

`Support\Actions\Bus\Invocation` defines lifecycle middleware sets.

- `Invocation::Now`:
  - `RunSucceeded`
  - `RunFailed`
  - `RunDispatchAfterSyncSucceeded`
  - `RunDispatchAfterSyncFailed`

- `Invocation::Dispatch`:
  - `RunSucceeded`
  - `RunDispatchAfterQueuedSucceeded`
  - `RunDispatchAfterQueuedFailed`

- `Invocation::Sync`:
  - `RunSucceeded`
  - `RunDispatchAfterSyncSucceeded`
  - `RunDispatchAfterSyncFailed`

## Lifecycle Middleware

All lifecycle middleware implement `Support\Actions\Middleware\Lifecycle\Contracts\Lifecycle`.

### Core hooks

- `RunSucceeded`: executes `succeeded()` after successful handling when method exists.
- `RunFailed`: executes `failed(Throwable $e)` on exception when method exists, then rethrows original exception.

### DispatchAfter attribute hooks

All attribute-based hooks use reflection to check class attributes and conditionally redispatch.

- `RunDispatchAfterSyncFailed`:
  - Trigger: exception + `DispatchAfterSyncFailed` attribute + not running in queue.
- `RunDispatchAfterSyncSucceeded`:
  - Trigger: success + `DispatchAfterSyncSucceeded` attribute + not running in queue.
- `RunDispatchAfterQueuedFailed`:
  - Trigger: exception + `DispatchAfterQueuedFailed` attribute + running in queue + final attempt reached.
- `RunDispatchAfterQueuedSucceeded`:
  - Trigger: success + `DispatchAfterQueuedSucceeded` attribute + running in queue.

Hook-internal exceptions are wrapped with `rescue(..., report: true)` so lifecycle hook failures do not replace the original flow outcome.

## Attributes

Supported class-level marker attributes:
- `DispatchAfterSyncFailed`
- `DispatchAfterSyncSucceeded`
- `DispatchAfterQueuedFailed`
- `DispatchAfterQueuedSucceeded`

Additional constraint: `DispatchAfterQueuedFailed` requires `$tries`, enforced by a PHPStan rule.

## Testing and Fakes

### Fake manager

`Support\Actions\Testing\Fakes\Manager` maintains fake registrations in Laravel Context and configures Bus fake interception.

Behavior:
- first registration sets up `Bus::fake([...])`
- subsequent registrations expand the faked job list
- dispatcher calls are intercepted and either:
  - route to fake return values, or
  - delegate to the underlying bus fake dispatcher

### Fake action object

`Support\Actions\Testing\Fakes\Action`:
- stores class being faked
- allows `andReturn()` static values or closures
- registers itself through manager on creation

## Static Analysis and Refactoring Enforcement

### PHPStan

Custom rules enforce constraints including:
- action finality
- required `handle()` method
- `AsAction` usage and contract implementation
- prohibited trait/method combinations
- direct `handle()` call prohibition
- queued-failed attribute tries requirement

### Rector

Rector rules provide auto-fixes for a subset of those constraints to keep code aligned with architecture rules.

## Service Provider Wiring

The package provider extends Laravel's bus dispatcher binding to use the package dispatcher decorator and registers `make:action` in console.

## Lifecycle Flow (Current)

```text
Sync (`->now()`):
  Nowable::now()
    -> Dispatcher::dispatchNow()
      -> prepareFor(Invocation::Now)
      -> Pipeline through middleware
      -> decorated dispatchNow()
      -> finally clearJob()

Dispatch (`->dispatch()`):
  Dispatchable::dispatch()
    -> prepareFor(Invocation::Dispatch)
    -> decorated dispatch

DispatchSync (`dispatchSync()`):
  Dispatcher::dispatchSync()
    -> prepareFor(Invocation::Sync)
    -> decorated dispatchSync

Middleware selection and deduplication:
  HasLifecycle::prepareFor()
    -> Invocation::middleware()
    -> removeLifecycleMiddleware()
    -> through([...lifecycle, ...existingNonLifecycle])
```
