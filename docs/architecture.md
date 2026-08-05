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
- `prepareFor(Invocation $via): static`
- `null|Invocation $invokedVia { get; }`
- `declares(string $attribute): bool`
- `bool $runningInQueue { get; }`
- `int $attempts { get; }`
- `bool $failed { get; }`
- `bool $released { get; }`
- `bool $failedOrReleased { get; }`
- `bool $attemptsLimited { get; }`
- `bool $attemptsExhausted { get; }`
- `clearJob(): static`
- `clearChain(): static`
- `standalone(): static`
- `through($middleware)`

## Core Traits

### AsAction

Composes:
- `Dispatchable`
- `Fakeable`
- `HasLifecycle`
- `InteractsWithChain`
- `InteractsWithJob`
- `Nowable`

Also provides `make()`, `declares($attribute)` (reports whether the action class declares the given attribute; used by the two lifecycle decorators to decide re-dispatch), and `standalone()`, which detaches a copy from the run it came from (`clearJob()` + `clearChain()`) so it can be re-dispatched as a fresh, standalone invocation.

### Nowable

- `now()` calls `Illuminate\Contracts\Bus\Dispatcher::dispatchNow($this)`.
- `nowIf()` and `nowUnless()` provide conditional synchronous execution.

### Dispatchable

- Uses Laravel `Queueable`.
- `dispatch()` returns `PendingDispatch`.
- `dispatchIf()` and `dispatchUnless()` provide conditional dispatch.

### InteractsWithJob

Encapsulates all path-agnostic job-state behavior (used by both the sync and queue paths). The underlying `$this->job` property comes from Laravel's `InteractsWithQueue`, composed below.

- Uses Laravel `InteractsWithQueue` (source of `$this->job`).
- `$runningInQueue` (get-only hook) checks for non-null job and excludes `SyncJob`.
- `$attempts` (get-only hook) is the number of times the action has been attempted.
- `$failed` (get-only hook) reports whether the job has been marked as failed.
- `$released` (get-only hook) reports whether the job has been released back onto the queue.
- `$failedOrReleased` (get-only hook) reports whether the job has failed or been released; used to gate success hooks.
- `$attemptsLimited` (get-only hook) reports whether the job has a maximum number of allowed attempts. A `null` or `0` `maxTries()` means the job is retried indefinitely, so this is `false`.
- `$attemptsExhausted` (get-only hook) reports whether the allowed attempts have been used up — `$attemptsLimited` and `attempts()` has reached `maxTries()`; used to detect terminal failure by exhaustion. A job retried indefinitely is never exhausted.
- `clearJob()` nulls the job so the command can be re-dispatched cleanly.
- `fail()` overrides `InteractsWithQueue::fail()`:
  - normalizes the argument (`string` → `ManuallyFailedException`, `null` → `ManuallyFailedException`) so `failed()` always receives a `Throwable`
  - on a queue worker (`$runningInQueue`): delegates to `Job::fail()` — standard Laravel bookkeeping, execution continues
  - synchronously (`now()`, `dispatchSync()`, sync queue driver): marks the job as failed and throws the exception to the waiting caller
  - `failed()` runs exactly once on every path (via the `Bus\Dispatcher` decorator on `now()`, via Laravel elsewhere)

### HasLifecycle

Lifecycle preparation is owned in this trait.

- `prepareFor(Invocation $via)`:
  - clears stale job state via `clearJob()`
  - records the invocation in `$invokedVia` (serializes with the payload)
  - calls optional consumer `prepare()` if present

### Fakeable

- `fake()` returns an action fake registration object.
- `assertFired()`, `assertNotFired()`, `assertFiredTimes()` delegate to Laravel Bus fake assertions.

## Dispatcher Decoration

`Support\Actions\Bus\Dispatcher` decorates Laravel's queueing dispatcher and is bound by the package provider.

### `dispatchNow()` path — the in-process lifecycle

- Non-actions pass directly through.
- Re-entry guard: if `$command->job` is already set, dispatch passes through directly (queued executions arrive here through the framework `CallQueuedHandler` with a job set; their lifecycle belongs to `Queue\CallQueuedHandler`).
- Otherwise the decorator owns the full lifecycle:
  - `prepareFor(Invocation::Now)` is called; a `standalone()` clone (job + chain detached) is captured for potential re-dispatch.
  - A `DetectsInterruption` pipeline (subclass of `Illuminate\Pipeline\Pipeline`) executes through command middleware. It composes around `Pipeline::carry()` to wrap each pipe, without reimplementing any pipe-invocation mechanics.
  - On success: `succeeded()` runs (when the method exists and the job is not failed/released — `fail()` + continue and `release()` are not successes), then `DispatchAfterSyncSucceeded` re-dispatch.
  - On exception: `failed(Throwable)` runs (when the method exists), then `DispatchAfterSyncFailed` re-dispatch, then the original exception rethrows.
  - `Interrupted` is caught first and rethrown untouched: an interruption is neither a success nor a failure — the action never ran — so neither hook nor re-dispatch fires.
  - `finally` clears the job.
  - Hook and re-dispatch failures are wrapped in `rescue(..., report: true)` so they never replace the original outcome.

### `dispatch()` path

- For actions, `prepareFor(Invocation::Dispatch)` is called before dispatching.
- Then call delegates to the decorated dispatcher.
- The entire lifecycle (hooks and re-dispatch) is owned by `Queue\CallQueuedHandler` — see [Queue-mediated lifecycle](#queue-mediated-lifecycle).

### `dispatchSync()` path

- For actions, `prepareFor(Invocation::Sync)` is called before dispatching.
- Then call delegates to the decorated dispatcher.
- Lifecycle is likewise owned by `Queue\CallQueuedHandler`; it fires the `Sync` attributes only when `invokedVia === Invocation::Sync` (i.e. `dispatchSync()`, not `dispatch()` on the sync driver).

> **Seam rule:** one decorator per execution world. `Bus\Dispatcher` owns the in-process lifecycle (`now()`); `Queue\CallQueuedHandler` owns the queue-mediated lifecycle (`dispatch()`, `dispatchSync()`). In both worlds the invariant is identical: *work → terminal hook → re-dispatch*.

## Invocation

`Support\Actions\Bus\Invocation` tags how an action was invoked. `prepareFor()` records it in `$invokedVia`, which serializes with the payload — the queue-side decorator uses it to distinguish `dispatchSync()` from `dispatch()` on the sync driver. It carries no middleware; lifecycle behavior lives entirely in the two decorators.

## Queue-mediated lifecycle

`Support\Actions\Queue\CallQueuedHandler` extends `Illuminate\Queue\CallQueuedHandler` and is bound via `app()->extend()` in the provider. It faithfully proxies `call()` and `failed()` into the decorated handler, then runs the lifecycle **after** the framework has fully processed the job — including chain advancement, batch recording, and delete. This mirrors the framework's own convention: `CallQueuedHandler::failed()` runs the command's `failed()` hook *after* `ensureFailedBatchJobIsRecorded()` and `ensureChainCatchCallbacksAreInvoked()`. Success and failure hooks are therefore symmetric — both fire after bookkeeping, then re-dispatch follows the hook, on every terminal path (timeout, `maxExceptions`, worker `--tries`, pre-fire exhaustion).

- Success (`call()`): after the inner handler returns, if `! $job->hasFailed() && ! $job->isReleased()` (the framework's own success predicate) and the payload is an `Action`: run `succeeded()` (when the method exists), then re-dispatch on `DispatchAfterQueuedSucceeded` (real job) or `DispatchAfterSyncSucceeded` (`SyncJob` + `invokedVia === Invocation::Sync`). The command is re-hydrated with `setJobInstanceIfNecessary()` before `succeeded()` runs, so the hook sees run state (`runningInQueue`, `attempts`, `batch()`) — mirroring how the framework re-attaches the job before its own `failed()` hook. Running the hook only after `call()` returns cleanly also means a chain/batch bookkeeping failure can no longer produce a `succeeded()`-then-`failed()` double-fire on a single attempt.
- Failure (`failed()`): the inner handler runs the command's `failed()` hook (framework-native); then re-dispatch on `DispatchAfterQueuedFailed` (real job) or `DispatchAfterSyncFailed` (`SyncJob` + `invokedVia === Invocation::Sync`). The re-dispatch is in a `finally`, so a `failed()` hook that throws still re-dispatches; the hook exception then propagates to the worker unchanged.
- Re-dispatch clones the command and calls `standalone()` (clears job + chain) so the re-dispatched copy is a fresh invocation that does not re-run the downstream chain.
- A `commandName` pre-filter avoids unserializing non-action payloads (which would trigger `SerializesModels` DB refetches for every failed job in the app).
- Hook and re-dispatch failures are wrapped in `rescue(..., report: true)` so they cannot mask the original outcome.
- Because it proxies into the handler it decorates, it composes with any other `CallQueuedHandler` extenders regardless of position in the chain. It never sees `Interrupted` — that is a `now()`-only concern (`DetectsInterruption` runs only in `dispatchNow()`).

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
      -> DetectsInterruption pipeline through command middleware
      -> decorated dispatchNow()
      -> success: succeeded() -> DispatchAfterSyncSucceeded re-dispatch
      -> failure: failed($e) -> DispatchAfterSyncFailed re-dispatch -> rethrow
      -> Interrupted: rethrow (no hooks, no re-dispatch)
      -> finally clearJob()

Dispatch (`->dispatch()`):
  Dispatchable::dispatch()
    -> prepareFor(Invocation::Dispatch)
    -> decorated dispatch
    ... worker picks up the job ...
    -> Queue\CallQueuedHandler::call() / failed()
      -> inner handler (runs action; framework runs failed() hook on terminal failure)
      -> success: succeeded() -> DispatchAfterQueuedSucceeded re-dispatch
      -> failure: DispatchAfterQueuedFailed re-dispatch

DispatchSync (`dispatchSync()`):
  Dispatcher::dispatchSync()
    -> prepareFor(Invocation::Sync)
    -> decorated dispatchSync
    -> same handler path as Dispatch, with the Sync attributes
       (gated on invokedVia === Invocation::Sync)
```
