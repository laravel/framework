# Resumable Jobs Plan

## Why

Laravel jobs can already be released and retried, and `Interruptible` now lets a running job react to shutdown signals. What is missing is a first-class way for a job to persist small, explicit checkpoints outside the serialized queue payload so the next attempt can resume from known progress instead of starting from scratch.

Two motivating cases:

- A long job intentionally reaches a checkpoint, releases itself, and later resumes from that checkpoint.
- A job receives `SIGTERM`, records where it safely stopped, and lets the replacement worker continue from there.

The important constraint is that Laravel should not mutate and reserialize the queued command payload during processing. Resume data should live beside the job, not inside the job payload.

## Agreed Shape

Add an opt-in resumable job API for class-based queued jobs:

- `Illuminate\Contracts\Queue\Resumable` is a marker interface.
- `Illuminate\Queue\InteractsWithResumableState` is the intended user-facing trait and attachment hook.
- `Illuminate\Queue\ResumeState` is a small context object attached to the job before middleware and `handle()` run.
- `Illuminate\Queue\ResumeStateRepository` is a dedicated abstraction bound to a cache-backed implementation by default.
- `CallQueuedHandler` is the main integration point. Queue drivers should not need special resumable-job behavior.

First-pass job API:

```php
use Illuminate\Contracts\Queue\Resumable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\InteractsWithResumableState;

class ImportUsers implements Resumable
{
    use InteractsWithQueue;
    use InteractsWithResumableState;

    public $resumeStateTtl = 3600;

    public function handle(): void
    {
        $cursor = $this->resumeState('users.cursor');

        // ...

        $this->checkpoint('users.cursor', $nextCursor);
    }
}
```

## State Model

Each resumable job has one cache record containing all named checkpoints for a resume identity:

```php
[
    'first-api-request' => ['external_id' => 'abc'],
    'database-update' => ['user_id' => 123],
    'second-api-request' => $progressObject,
]
```

Decisions:

- The default resume identity is the queued payload UUID.
- Jobs may optionally define `resumeKey(): string` for domain-stable workflows, such as `import:123` or `sync:account:456`.
- Resume keys are not scoped by queue connection or queue name. Laravel cache prefixing and user-chosen `resumeKey()` values are responsible for avoiding collisions.
- Checkpoint values may be anything Laravel's cache store can serialize.
- `checkpoint(string $name, mixed $value): void` replaces the named checkpoint value.
- `resumeState(?string $name = null, mixed $default = null): mixed` returns either one named checkpoint or the full checkpoint map when no name is provided.
- `forgetCheckpoint(string $name): void` may be included as a provisional convenience for reducing or removing stale resume state during execution. Primary cleanup is still TTL or terminal job cleanup.
- No `hasCheckpoint()` method in the first pass.

## Storage

Use a small storage abstraction backed by Laravel's cache repository. Redis remains the natural production backing store for many queue/Horizon installations, but the public API should stay queue-driver agnostic.

Cache key:

```text
queue:resume:{resumeKey}
```

The cache value is only the checkpoint map. Do not add a metadata envelope in the first implementation.

TTL decisions:

- Resolve the TTL from `$resumeStateTtl` first.
- If `resumeStateTtl()` exists, it overrides the property, matching the queue `tries` / `backoff` method-over-property convention.
- If neither job TTL exists, fall back to `retryUntil()` when available.
- If no job TTL or `retryUntil()` exists, fall back to a config value.
- The config fallback default is 24 hours.
- Accept Laravel-style delay values: seconds, `DateInterval`, and `DateTimeInterface`.
- Refresh the TTL every time checkpoint state is saved.
- Save immediately on every `checkpoint()` and `forgetCheckpoint()` call.

## Worker Lifecycle Sketch

During queued command handling:

1. Deserialize the command as Laravel does today.
2. If the command implements `Resumable`, verify it uses `InteractsWithResumableState` and attach a `ResumeState` context to it.
3. Load existing checkpoint state using `resumeKey() ?? job UUID`.
4. Run the job.
5. If the job reaches a terminal outcome, clear the resume state.
6. If the job is released or interrupted, keep the resume state for the next attempt.

On `Interruptible::interrupted($signal)`, a resumable job may checkpoint and then release, return, or finish cleanup. The hook should remain cooperative; the framework should not infer a checkpoint automatically.

Concrete lifecycle anchor points:

- `CallQueuedHandler::call()` is the narrowest place to load and clear state for normal queued commands. It already owns command deserialization, `InteractsWithQueue` attachment, middleware dispatch, unique-lock cleanup, chain/batch success recording, and final auto-delete behavior.
- `Worker::notifyJobOfSignal()` reaches the currently running command through the resolved `CallQueuedHandler`. If the command implements both `Interruptible` and `Resumable`, the same attached resume context should be available inside `interrupted()`.
- The base queue `Job` only tracks `deleted`, `released`, and `failed` flags. Clearing state should therefore be keyed off those flags after command execution rather than trying to detect how the command exited.
- Exception retries are handled by `Worker::handleJobException()`, which may call `release()` after `CallQueuedHandler::call()` has already unwound via an exception. Resume state should be retained for retryable exceptions and cleared when the job is permanently failed.
- Clearing state after success should not interfere with existing chain, batch, or unique-lock behavior in `CallQueuedHandler`.

Terminal outcomes that clear resume state:

- `handle()` returns normally.
- `handle()` calls `delete()` and returns.
- `handle()` calls `fail()`.
- The worker determines the job has permanently failed.

Outcomes that keep resume state:

- The job calls `release()`.
- The job throws and the worker releases it for another attempt.
- An interrupt handler checkpoints before releasing or exiting.

## Things To Be Careful About

- Existing `release()` requeues the same payload; it does not serialize runtime mutations back into the job.
- Attempts should continue behaving normally. Resume state should not reset attempts or bypass `maxTries` / `retryUntil`.
- Successful completion, manual `delete()`, manual `fail()`, and permanent failure should clear state.
- Encrypted jobs should not imply encrypted checkpoint data. If checkpoint state may contain secrets, the storage layer needs an encryption option or clear documentation.
- Batches and chains should work without special behavior. Resume state belongs to the individual queued job/resume key.

## First Implementation Pass

1. Add the `Resumable` contract and `InteractsWithResumableState` trait.
2. Add the `ResumeState` context object.
3. Add `ResumeStateRepository` and bind a cache-backed implementation in the queue service provider.
4. Teach `CallQueuedHandler` to attach/load/clear state around class-based queued jobs implementing `Resumable`.
5. Add focused queue worker tests for:
   - state loads before `handle()`;
   - named checkpoints persist across `release()`;
   - successful completion clears state;
   - manual `delete()` clears state;
   - manual `fail()` clears state;
   - permanent failure clears state;
   - `resumeKey()` overrides the job UUID;
   - TTL falls back through job TTL, `retryUntil()`, and config;
   - checkpoint values are stored through Laravel's cache serialization.
6. Add one interruptible/resumable test showing a signal handler can checkpoint before the worker exits.

Suggested class layout:

```text
src/Illuminate/Contracts/Queue/Resumable.php
src/Illuminate/Queue/InteractsWithResumableState.php
src/Illuminate/Queue/ResumeState.php
src/Illuminate/Queue/ResumeStateRepository.php
src/Illuminate/Queue/CacheResumeStateRepository.php
```

Possible contract and trait surface:

```php
interface Resumable
{
    //
}

trait InteractsWithResumableState
{
    public function checkpoint(string $name, mixed $state): void;

    public function resumeState(?string $name = null, mixed $default = null): mixed;

    public function forgetCheckpoint(string $name): void;

    public function setResumeState(ResumeState $state): static;
}
```

`resumeKey()` remains an optional job method, not part of the marker interface or trait contract. The handler should use `method_exists($command, 'resumeKey') ? $command->resumeKey() : $job->uuid()`.

Repository responsibilities:

- Build cache keys from the resume key.
- Load all checkpoint state before the job runs.
- Persist the whole checkpoint map after each mutation.
- Forget a named checkpoint.
- Clear all state on terminal outcomes.
- Apply TTL consistently on each write.

Non-goals for the first implementation:

- Mutating the serialized job command payload.
- Automatically checkpointing local object properties.
- Cross-job locking or merge conflict resolution.
- Driver-specific Redis APIs.
- A database-backed resume state table.
- Transparent encryption without an explicit option.
- Queued listener support.
- Queued closure support.
- PHP attributes for resumable workflow settings.
