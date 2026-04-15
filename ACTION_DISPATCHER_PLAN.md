# Plan: Bus Dispatcher Decorator for Lifecycle Hooks

## TL;DR

Replace `Nowable`'s inline lifecycle hook logic with a Dispatcher decorator (`Support\Actions\Dispatcher`) that wraps `dispatchNow()` with `succeeded()` / `failed()` hooks for `Action` instances only. Follows the same decorator pattern as the existing event dispatcher decorator (`Support\Events\Log\Dispatcher`). This moves hooks below the faking interception point, so they naturally don't fire when an action is faked.

## Steps

### Phase 1: Create Dispatcher decorator

- [x] Create `src/Support/Actions/Bus/Dispatcher.php` — class `Support\Actions\Bus\Dispatcher` implementing `\Illuminate\Contracts\Bus\Dispatcher`
  - Constructor: `private readonly \Illuminate\Contracts\Bus\Dispatcher $decorated`
  - `dispatchNow($command, $handler = null)`: if `$command instanceof Action`, wrap `$this->decorated->dispatchNow()` with try/catch for `failed()` hook and post-success `succeeded()` hook (both guarded by `method_exists`, both wrapped in `rescue(report: true)`). Non-Action commands delegate directly.
  - 6 delegate methods: `dispatch()`, `dispatchSync()`, `hasCommandHandler()`, `getCommandHandler()`, `pipeThrough()`, `map()` — all forward to `$this->decorated`
  - `__call()` for any non-interface methods on the concrete Dispatcher

### Phase 2: Register in Provider

- [x] In `Provider::register()`, extend the Dispatcher binding:
  `$this->app->extend(BusDispatcher::class, fn ($dispatcher) => new Dispatcher($dispatcher));`
  (import `Illuminate\Contracts\Bus\Dispatcher as BusDispatcher` to avoid name collision)
- [x] Remove `DeferrableProvider` interface and the `provides()` method — must register eagerly so `extend()` wraps the Dispatcher before any action runs

### Phase 3: Simplify Nowable

- [x] Strip all lifecycle hook logic from `Nowable::now()` — becomes `return $this->dispatchNow();`
- [x] Remove `use Throwable` import

### Phase 4: Clean up PHPStan neon

- [x] Remove all three Nowable-specific ignores from `tooling/phpstan/rules.neon` (method_exists, call_user_func, rescue template)

## Relevant files

- **New:** `src/Support/Actions/Bus/Dispatcher.php` — decorator following `Support\Events\Log\Dispatcher` pattern
- `src/Support/Actions/Providers/Provider.php` — add `extend()`, remove `DeferrableProvider`
- `src/Support/Actions/Concerns/Nowable.php` — strip hook logic to one-liner
- `tooling/phpstan/rules.neon` — remove 3 Nowable-specific ignores
- Unchanged: `RunSucceededHook.php`, `Dispatchable.php`, test cases

## Verification

- [x] `./vendor/bin/phpunit` — all tests pass
- [x] `./vendor/bin/testbench tooling:phpstan` — no errors
- [x] `./vendor/bin/testbench tooling:pint` — formatting
- [x] `./vendor/bin/testbench tooling:rector --dry-run` — no issues
- [x] Faking tests pass naturally: `it_does_not_run_succeeded_when_faked_now`, `it_does_not_run_failed_when_faked_now`, `it_does_not_run_succeeded_when_faked_dispatch`, `it_does_not_run_failed_when_faked_dispatch`

## Decisions

- **Follows existing event dispatcher decorator pattern** — `private readonly $decorated`, explicit interface method implementations, `__call()` for non-interface methods
- **Class named `Dispatcher`** in `Support\Actions\Bus` namespace (mirrors `Support\Events\Log\Dispatcher`)
- **Decorator over pipe:** `pipeThrough()` replaces globally — fragile. Decorator is durable.
- **Scoped via `instanceof Action`:** Non-action commands pass through unchanged.
- **Hooks skipped when faked naturally:** Manager replaces `Dispatcher::class` binding with mock, removing decorator from call chain.
- **Provider deferred → eager:** Must register `extend()` before any action dispatches. Cost is negligible.
