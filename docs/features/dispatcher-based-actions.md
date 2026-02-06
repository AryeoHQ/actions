# Dispatcher-Based Actions with Testable Return Values

## Goals

Merge the concepts of Actions and Jobs into a unified pattern where:

1. **All execution goes through Laravel's dispatcher** - Enables consistent testing and interception
2. **Control return values in tests** - Mock action results without mocking the entire class
3. **Support both sync and async execution** - `execute()` for immediate results, `dispatch()` for queuing
4. **Maintain excellent IDE support** - Type hints and autocomplete for action constructors
5. **Preserve Bus::fake() compatibility** - Leverage Laravel's existing assertion methods
6. **Test nested action calls** - Actions that call other actions with return value control

## Current State Analysis

### Existing Pattern (v1.x)

**AsAction trait provides:**
- `make()` - Container resolution for DI
- `mock()` - Mockery-based testing (mocks entire class via container)
- `executeIf()` - Conditional execution
- `shouldExecute()` - Fluent Mockery expectation builder

**Actions:**
- Implement `Action` contract + use `AsAction` trait
- Define `execute()` method with flexible signature
- Execute as: `MyAction::make()->execute($args)`
- Test via: `MyAction::shouldExecute()->once()->andReturn('value')`

**Limitations:**
- Cannot test actions that dispatch other actions and use their return values
- Mocking replaces the entire class in the container
- No support for queued execution
- Actions and Jobs are separate concepts

### Target Pattern (v2.0)

**AsAction trait provides:**
- `make(...$arguments)` - Simple factory returning action instance or proxy
- `execute()` - Synchronous dispatch via `dispatchNow()`, returns result
- `dispatch()` - Asynchronous dispatch via `PendingDispatch`
- `fake(mixed $returns)` - Per-action return value control with Bus compatibility
- `clearFakes()` - Test cleanup

**Actions:**
- Implement `Action` contract + use `AsAction` trait
- Define `handle()` method (called by dispatcher, never directly by users)
- Can optionally implement `ShouldQueue` for async execution
- Execute as: `MyAction::execute($args)` or `MyAction::make($args)->execute()`
- Test via: `MyAction::fake('value')` + `Bus::assertDispatched()`

**Benefits:**
- Test actions that call other actions with controlled return values
- Combine action pattern with job dispatching
- Full Bus::fake() assertion compatibility
- Support for both sync and async execution
- Actions are truly dispatchable jobs

## Understanding: How It Works

### Execution Flow

```php
// Synchronous execution with return value
$result = MyAction::execute($arg1, $arg2);

// Internally:
// 1. __callStatic creates instance: new MyAction($arg1, $arg2)
// 2. Calls private execute() method on instance
// 3. execute() calls: app(Dispatcher::class)->dispatchNow($this)
// 4. Dispatcher invokes handle() method
// 5. Result returned to caller

// Asynchronous execution
MyAction::dispatch($arg1, $arg2);

// Internally:
// 1. __callStatic creates instance: new MyAction($arg1, $arg2)
// 2. Calls private dispatch() method on instance
// 3. dispatch() returns: new PendingDispatch($this)
// 4. Job queued for later processing
```

### Testing Flow

```php
// Set up fake
MyAction::fake('mocked-value');

// Internally:
// 1. Stores return value in Context: ['MyAction' => 'mocked-value']
// 2. Calls Bus::fake([MyAction::class]) for assertion tracking
// 3. Wraps dispatcher with Mockery mock
// 4. Mock intercepts dispatchNow/dispatch calls
// 5. If action is faked, records to Bus fake and returns stored value
// 6. If not faked, delegates to original dispatcher

// Execute action
$result = MyAction::execute($args);
// Returns 'mocked-value' without executing handle()

// Assert
Bus::assertDispatched(MyAction::class);
```

### Key Design Decisions

**1. `handle()` vs `execute()`**
- `handle()` = business logic (called by dispatcher only)
- `execute()` = dispatch method (synchronous, returns result)
- `dispatch()` = dispatch method (asynchronous, queues job)

**2. Magic methods vs explicit API**
- Magic `__callStatic` enables: `MyAction::execute($args)`
- Also supports: `MyAction::make($args)->execute()`
- Private methods prevent direct calls without dispatcher

**3. Single trait vs separate traits**
- Merge `AsAction` and `Fakeable` into one trait
- Faking is inherently about testing actions, natural coupling
- Simpler API, less to remember

**4. Proxy vs no proxy**
- **Decision: No proxy (use magic methods)**
- Leaning toward prototype's approach for simplicity
- Direct instantiation in `make()`: `return new static(...$arguments)`
- Keep API surface minimal and straightforward

**5. Laravel Queue Feature Integration**
- **We are NOT reimplementing Laravel's queue features**
- Actions simply integrate with Laravel's existing dispatcher infrastructure
- All Bus methods (batch, chain, fake, assertions) work automatically
- Actions inherit all job capabilities: middleware, lifecycle methods, traits, properties

**Option A: No Proxy (Current Prototype - CHOSEN APPROACH)**
```php
trait AsAction {
    public static function make(...$arguments): static {
        return new static(...$arguments);
    }
    private function execute() { /* ... */ }
    public function __call($method, $args) { /* ... */ }
}
```
- ✅ Simpler implementation
- ✅ `make()` returns actual action type
- ✅ Direct instantiation, no container complexity
- ⚠️ Magic methods
- ⚠️ Users could accidentally call `handle()` directly (mitigated by docs/linting)

**Option B: Higher Order Proxy (NOT CHOSEN)**
```php
class HigherOrderActionProxy {
    public function execute(): mixed { /* ... */ }
    public function dispatch(): PendingDispatch { /* ... */ }
}

trait AsAction {
    public static function make(...$arguments): HigherOrderActionProxy {
        return new HigherOrderActionProxy(new static(...$arguments));
    }
}
```
- ✅ Explicit API, no magic
- ✅ Prevents direct `handle()` calls
- ✅ Better static analysis
- ⚠️ More verbose
- ⚠️ Additional class to maintain
- ⚠️ `make()` return type isn't the action class

## Laravel Queue Feature Compatibility

### Understanding: We Integrate, Not Reimplement

**Core Principle**: Actions are simply objects passed to Laravel's dispatcher. All Laravel queue features work automatically because the dispatcher infrastructure handles them.

**What We Provide:**
- `AsAction` trait with convenient API: `make()`, `execute()`, `dispatch()`, `fake()`
- Integration with Laravel's dispatcher via `dispatchNow()` and `dispatch()`
- Per-action return value control for testing nested actions
- Bus::fake() compatibility for familiar testing patterns

**What Laravel Provides** (and Actions inherit):
- Job batching via `Bus::batch()`
- Job chaining via `Bus::chain()`
- Job middleware, lifecycle methods, retry logic
- Queue configuration, delays, connections
- All job traits: `SerializesModels`, `InteractsWithQueue`, `Queueable`
- All Bus assertions: `assertDispatched()`, `assertBatched()`, `assertChained()`

### Job Batching

Actions can be batched exactly like Laravel jobs:

```php
// Basic batching
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

// Testing batched actions
ProcessInvoice::fake('processed');
Bus::batch([
    ProcessInvoice::make($invoice),
])->dispatch();

Bus::assertBatched(function (PendingBatch $batch) {
    return $batch->jobs->count() === 1;
});
```

### Job Chaining

Two distinct chaining patterns exist:

**External Chaining (Asynchronous, No Return Values)**
```php
// Chain actions via Bus::chain() - jobs run sequentially on queue
Bus::chain([
    ValidateOrder::make($order),
    ProcessPayment::make($order),
    SendConfirmation::make($order),
])->dispatch();

// Each job runs after the previous completes
// No return values passed between jobs
// Cannot use fake() to control returns between chained jobs
```

**Internal Chaining (Synchronous, With Return Values)**
```php
// Chain actions via execute() within handle() - runs immediately
class ProcessOrder
{
    public function handle()
    {
        $validated = ValidateOrder::execute($this->order);
        $payment = ProcessPayment::execute($validated);
        return SendConfirmation::execute($payment);
    }
}

// Each action executes immediately and returns a value
// Can use fake() to control return values for testing
ValidateOrder::fake(['valid' => true]);
ProcessPayment::fake(['payment_id' => 123]);
```

**Key Difference**: External chaining is asynchronous (queued, no returns). Internal chaining is synchronous (immediate, with returns). Choose based on whether you need return values or can work asynchronously.

### Queue Feature Compatibility Matrix

| Feature | Support | Notes |
|---------|---------|-------|
| **Batching** | ✅ Automatic | `Bus::batch([MyAction::make(), ...])` |
| **Chaining** | ✅ Automatic | `Bus::chain([MyAction::make(), ...])` for async; `::execute()` for sync with returns |
| **Middleware** | ✅ Automatic | Implement `middleware()` method |
| **Lifecycle Methods** | ✅ Automatic | `failed()`, `retryUntil()`, `backoff()` |
| **Job Properties** | ✅ Automatic | `$tries`, `$timeout`, `$maxExceptions`, etc. |
| **Job Traits** | ✅ Automatic | `SerializesModels`, `InteractsWithQueue`, `Queueable` |
| **Unique Jobs** | ✅ Automatic | Implement `ShouldBeUnique` |
| **Encrypted Jobs** | ✅ Automatic | Implement `ShouldBeEncrypted` |
| **Pending Dispatch** | ✅ Automatic | `->onQueue()`, `->delay()`, `->onConnection()` |
| **Conditional Dispatch** | ✅ Automatic | `dispatchIf()`, `dispatchUnless()` |
| **After Response** | ✅ Automatic | `dispatchAfterResponse()` |
| **Bus Assertions** | ✅ Automatic | All `Bus::assert*()` methods work |
| **Fake in Batches** | ✅ Via trait | `fake()` works with batched actions |
| **Fake in Chains** | ⚠️ Limited | Only works for internal (sync) chaining via `execute()` |

### Testing Advanced Features

```php
// Test job middleware
MyAction::fake('result');
$action = MyAction::make($args);
$this->assertContains(RateLimited::class, $action->middleware());
Bus::assertDispatched(MyAction::class);

// Test lifecycle methods
MyAction::fake('result');
MyAction::execute($args);
// Verify failed() callback behavior

// Test with job traits
class MyAction implements ShouldQueue
{
    use AsAction, SerializesModels, Queueable;

    public function handle()
    {
        // Business logic
    }
}

// Test PendingDispatch chaining
MyAction::fake();
MyAction::dispatch($args)
    ->onQueue('high-priority')
    ->delay(now()->addMinutes(5));

Bus::assertDispatched(MyAction::class, function ($action) {
    return $action->queue === 'high-priority';
});
```

## Implementation Checklist

### Phase 1: Core Implementation

- [ ] **Decision: Proxy or No Proxy?** - ✅ **DECIDED: No proxy, use magic methods**
- [ ] ~~Create `HigherOrderActionProxy` class~~ - **NOT NEEDED**
- [ ] Rewrite `AsAction` trait in `src/Support/Actions/Concerns/AsAction.php`
  - [ ] Merge `Fakeable` functionality into trait
  - [ ] Implement `make(...$arguments)` returning action: `return new static(...$arguments)`
  - [ ] Add private `execute()` method calling `dispatchNow()`
  - [ ] Add private `dispatch()` method returning `PendingDispatch`
  - [ ] Add `__callStatic()` for static shortcuts (`MyAction::execute()`)
  - [ ] Add `__call()` for instance method proxying
  - [ ] Implement complete `fake(mixed $returns = null)` method
  - [ ] Implement `clearFakes()` method
  - [ ] Remove `mock()`, `shouldExecute()`, `executeIf()` methods
  - [ ] Remove `Fluent` dependency
- [ ] Update `Action` contract in `src/Support/Actions/Contracts/Action.php`
  - [ ] Ensure `make()` return type is `:static`
  - [ ] Document convention: Actions implement `handle()` method (not in interface)

### Phase 2: Tooling Updates

- [ ] Update PHPStan rule in `src/Tooling/PHPStan/Rules/ActionRule.php`
  - [ ] Change `hasExecuteMethod()` to `hasHandleMethod()`
  - [ ] Update error message: "Action classes must implement the handle() method."
  - [ ] Update identifier from `actions.execute` to `actions.handle`
- [ ] Update Rector rule (if needed)
  - [ ] Verify it still correctly adds `Action` contract and `AsAction` trait
- [ ] Update MakeAction generator in `src/Support/Actions/Commands/MakeAction.php`
  - [ ] Update stub/template to generate `handle()` method instead of `execute()`
  - [ ] Ensure proper imports for any new classes

### Phase 3: Test Fixtures & Tests

- [ ] Update all test fixtures in `tests/Fixtures/`
  - [ ] `TestAction.php` - Rename `execute()` to `handle()`
  - [ ] `TestActionWithArgs.php` - Rename `execute()` to `handle()`
  - [ ] All files in `Fixtures/Variations/` - Rename `execute()` to `handle()`
- [ ] Update `TestClass.php` in `tests/Fixtures/`
  - [ ] Update methods to use `::execute()` pattern instead of `make()->execute()`
  - [ ] Remove any `executeIf()` usage
- [ ] Rewrite `ActionTest.php` in `tests/Support/Actions/`
  - [ ] Remove all `shouldExecute()` tests
  - [ ] Remove all `mock()` tests
  - [ ] Remove `executeIf()` tests
  - [ ] Add tests for `fake()` with return values
  - [ ] Add tests for `::execute()` static shorthand
  - [ ] Add tests for `make()->execute()` pattern
  - [ ] Add tests for `::dispatch()` for queued jobs
  - [ ] Add tests for nested action calls with faking
  - [ ] Add tests for `Bus::assertDispatched()` compatibility
  - [ ] Add tests for `clearFakes()` cleanup
- [ ] **Add Laravel queue feature integration tests**
  - [ ] Test Actions with `SerializesModels`, `InteractsWithQueue`, `Queueable` traits
  - [ ] Test Actions with `middleware()` method
  - [ ] Test Actions with lifecycle methods: `failed()`, `retryUntil()`, `backoff()`
  - [ ] Test Actions with job properties: `$tries`, `$timeout`, `$maxExceptions`
  - [ ] Test Actions implementing `ShouldBeUnique`
  - [ ] Test Actions implementing `ShouldBeEncrypted`
  - [ ] Test PendingDispatch chaining: `->onQueue()`, `->delay()`, `->onConnection()`
  - [ ] Test conditional dispatch: `dispatchIf()`, `dispatchUnless()`
  - [ ] Test delayed dispatch: `dispatchAfterResponse()`
- [ ] **Add job batching tests**
  - [ ] Test Actions can be batched via `Bus::batch()`
  - [ ] Test batch callbacks: `then()`, `catch()`, `finally()`
  - [ ] Test `fake()` works with batched Actions
  - [ ] Test `Bus::assertBatched()` with Actions
  - [ ] Test batch progress and status tracking
- [ ] **Add job chaining tests**
  - [ ] Test external chaining via `Bus::chain()` (asynchronous)
  - [ ] Test `Bus::assertChained()` with Actions
  - [ ] Test internal chaining via `execute()` (synchronous with returns)
  - [ ] Test `fake()` works with internal chaining
  - [ ] Verify `fake()` does NOT affect external chain return values (expected)
  - [ ] Test mixed chaining patterns (action calls action via execute, which dispatches another)
- [ ] Verify BananaTest.php patterns work with final implementation
  - [ ] Test synchronous execution with return values
  - [ ] Test queued execution
  - [ ] Test nested actions calling each other

### Phase 4: Documentation

- [ ] Update README.md
  - [ ] Update "Defining Actions" section
    - [ ] Show `handle()` method instead of `execute()`
    - [ ] Document that `handle()` is called by dispatcher, never directly
    - [ ] Show optional `ShouldQueue` interface for async
  - [ ] Update "Executing Actions" section
    - [ ] Document `MyAction::execute($args)` for synchronous
    - [ ] Document `MyAction::dispatch($args)` for asynchronous
    - [ ] Document `MyAction::make($args)->execute()` for explicit instantiation
    - [ ] Remove `executeIf()` documentation
  - [ ] Completely rewrite "Testing" section
    - [ ] Document `fake()` method and return value control
    - [ ] Show `Bus::assertDispatched()` patterns
    - [ ] Show examples of testing actions that call other actions
    - [ ] Document `clearFakes()` for test cleanup
    - [ ] Remove all `mock()` and `shouldExecute()` examples
  - [ ] **Add "Advanced Queue Features" section**
    - [ ] Document job batching with `Bus::batch()` examples
    - [ ] Document job chaining patterns
      - [ ] External chaining via `Bus::chain()` (async, no returns)
      - [ ] Internal chaining via `execute()` (sync, with returns)
      - [ ] Explain when to use each pattern
    - [ ] Document job middleware usage
    - [ ] Document lifecycle methods: `failed()`, `retryUntil()`, `backoff()`
    - [ ] Document job traits compatibility: `SerializesModels`, `InteractsWithQueue`, `Queueable`
    - [ ] Document job properties: `$tries`, `$timeout`, `$maxExceptions`
    - [ ] Document unique jobs with `ShouldBeUnique`
    - [ ] Document encrypted jobs with `ShouldBeEncrypted`
    - [ ] Document PendingDispatch chaining: `->onQueue()`, `->delay()`
    - [ ] Link to Laravel queue documentation for complete feature reference
  - [ ] Add "Migration from v1.x" section
    - [ ] Document breaking changes
    - [ ] Provide migration examples
- [ ] Create UPGRADE.md guide
  - [ ] List all breaking changes
  - [ ] Provide before/after code examples
  - [ ] Document new patterns and best practices

### Phase 5: Validation & Polish

- [ ] Run full test suite and ensure all tests pass
- [ ] Run PHPStan analysis and fix any issues
- [ ] Run Rector and verify rules work correctly
- [ ] Test IDE autocomplete for `make()` parameters
- [ ] Test IDE autocomplete for `execute()` and `dispatch()` methods
- [ ] Verify `fake()` works with various return types (null, primitives, objects)
- [ ] Test cleanup between tests with `clearFakes()`
- [ ] Performance test: measure overhead of proxy/magic methods
- [ ] Code review: ensure consistent naming and patterns

## Open Questions

1. **Constructor autocomplete**: Can we improve IDE hints for `make()` parameters beyond `...$arguments`?
   - **Status**: Accepted as limitation - return type inference works well, parameter hints are generic
2. **DI in make()**: Should `make()` with no args attempt container resolution, or always use `new static()`?
   - **Status**: Use `new static()` - simpler, more predictable, avoids container complexity
3. **Static shortcuts**: Should we keep `MyAction::execute()` shorthand, or require `make()->execute()`?
   - **Status**: Keep both - `::execute()` is convenient, `make()->` is explicit
4. **Test cleanup**: Should `clearFakes()` be called automatically in a base TestCase tearDown?
   - **Status**: TBD - consider adding to package's base TestCase
5. **Return type variance**: How to handle actions with different return types in `execute()`?
   - **Status**: TBD - `mixed` return type, rely on docblocks for specificity
6. **Queue detection**: Should `dispatch()` validate that action implements `ShouldQueue`?
   - **Status**: TBD - Laravel handles this, probably unnecessary validation

## Success Criteria

- [ ] All existing tests converted and passing
- [ ] Can test actions that call other actions with controlled return values
- [ ] Both synchronous and asynchronous execution work correctly
- [ ] Bus::fake() assertions work as expected
- [ ] IDE provides good autocomplete for action usage
- [ ] PHPStan analysis passes without errors
- [ ] Documentation is clear and comprehensive
- [ ] Migration path from v1.x is well-documented
