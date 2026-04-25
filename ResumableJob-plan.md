# Resumable Jobs Plan

## Why

Laravel jobs can already be released and retried, and `Interruptible` now lets a running job react to shutdown signals. What is missing is a first-class way for a job to persist small, explicit checkpoints outside the serialized queue payload so the next attempt can resume from known progress instead of starting from scratch.

Two motivating cases:

- A long job intentionally reaches a checkpoint, releases itself, and later resumes from that checkpoint.
- A job receives `SIGTERM`, records where it safely stopped, and lets the replacement worker continue from there.

The important constraint is that Laravel should not mutate and reserialize the queued command payload during processing. Resume data should live beside the job, not inside the job payload.

## Proposed Shape

Add an opt-in resumable job API:

- `Illuminate\Contracts\Queue\Resumable` marks jobs that use external resume state.
- A trait, likely `InteractsWithResumableState`, provides the ergonomic API.
- The framework loads checkpoint state before the job runs and clears it after successful completion.

Suggested job API:

```php
$this->checkpoint('first-api-request', [
    'external_id' => $externalId,
]);

$state = $this->resumeState('first-api-request');

$this->forgetCheckpoint('first-api-request');

$this->releaseFromCheckpoint(delay: 30);
```

## State Model

Each resumable job has one resume record containing multiple named checkpoint states.

Default resume identity:

- Use the queue job UUID by default.
- Allow jobs to override with `resumeKey(): string` for domain-stable workflows, such as `import:123` or `sync:account:456`.

Checkpoint values:

- Must be JSON-serializable arrays/scalars.
- Should not accept arbitrary PHP objects.
- Typed checkpoint DTOs can exist in userland by converting to/from arrays.

Example stored shape:

```php
[
    'first-api-request' => ['external_id' => 'abc'],
    'database-update' => ['user_id' => 123],
    'second-api-request' => ['cursor' => 'next-page'],
]
```

## Storage

Do not make this Redis-specific.

Use a small storage abstraction backed by Laravel's cache repository. Redis remains the natural production backing store for many queue/Horizon installations, but the public API should be queue-driver agnostic.

Possible storage key:

```text
queue:resume:{connection}:{queue}:{resumeKey}
```

The state should have a TTL. A conservative default could align with the job's retry window when available, or fall back to a configurable queue resume-state TTL.

## Worker Lifecycle Sketch

During queued command handling:

1. Deserialize the command as Laravel does today.
2. If the command implements `Resumable`, attach a resume-state repository/context to it.
3. Load existing checkpoint state using the queue connection, queue name, and `resumeKey() ?? job UUID`.
4. Run the job.
5. If the job succeeds and is not released, failed, or deleted early, clear the resume state.
6. If the job is released or interrupted, keep the resume state for the next attempt.

On `Interruptible::interrupted($signal)`, a resumable job may checkpoint and then release, return, or finish cleanup. The hook should remain cooperative; the framework should not infer a checkpoint automatically.

Concrete lifecycle anchor points:

- `CallQueuedHandler::call()` is the narrowest place to load and clear state for normal queued commands. It already owns command deserialization, `InteractsWithQueue` attachment, middleware dispatch, unique-lock cleanup, chain/batch success recording, and final auto-delete behavior.
- `Worker::notifyJobOfSignal()` reaches the currently running command through the resolved `CallQueuedHandler`. If the command implements both `Interruptible` and `Resumable`, the same attached resume context should be available inside `interrupted()`.
- The base queue `Job` only tracks `deleted`, `released`, and `failed` flags. Clearing state should therefore be keyed off those flags after command execution rather than trying to detect how the command exited.
- Exception retries are handled by `Worker::handleJobException()`, which may call `release()` after `CallQueuedHandler::call()` has already unwound via an exception. If resume state should be retained after exceptions, cleanup must happen only on successful command completion, not in a broad `finally`.

## Design Pressure Questions

Questions this design must answer before implementation:

1. **What is the exact success signal?**  
   `CallQueuedHandler::call()` deletes a job after successful processing unless it was already deleted or released. The safest first rule is: clear resume state only after `dispatchThroughMiddleware()` returns without throwing and the job is neither released nor failed. A manual `delete()` during `handle()` should probably also clear because Laravel treats that as completed work, but this should be an explicit test.

2. **What happens on exceptions?**  
   The worker may release the job after an exception, but `CallQueuedHandler` will not reach its normal post-dispatch code. Resume state should remain intact on exceptions by default. If the job ultimately fails, a second policy is needed for whether the failed-job path clears, retains, or TTL-expires state.

3. **Can a resumable job work without `InteractsWithQueue`?**  
   A job can implement `Resumable` without using `InteractsWithQueue`, but the proposed trait needs the underlying queue job to release or derive the default UUID. First pass should require the trait to receive a context from the handler and should fail clearly if checkpoint APIs are called before attachment.

4. **How stable is the default resume identity?**  
   The queued payload UUID is stable across releases of the same payload, which fits "resume this queued attempt series." It is not stable across a fresh dispatch of the same domain operation. Jobs that need domain continuity must implement `resumeKey()`.

5. **Can two jobs share a resume key?**  
   Sharing enables replacement/reconciliation workflows, but it also creates races. First pass should document last-write-wins semantics and avoid adding locking unless the repository proves it is necessary. A future repository method could use cache locks for compare-and-swap style updates.

6. **How much state is acceptable?**  
   Cache stores have size limits and eviction policies. The API should be framed as checkpoint metadata, not arbitrary snapshots. Validation should ensure JSON encodability, but documentation should also warn that large payloads belong in durable application storage.

7. **Does encryption happen automatically?**  
   It should not silently piggyback on encrypted queued commands because resume state is a separate cache value. First pass should either leave encryption out with clear documentation, or add an explicit `queue.resumable.encrypt` option. Silent mixed behavior would be surprising.

8. **Where does TTL come from?**  
   `retryUntil()` gives an absolute timestamp when present. Otherwise, the queue system may only know attempts/backoff and worker options. A fixed config value, such as `queue.resumable.ttl`, is easier to reason about than deriving different TTLs per driver. If `retryUntil()` is sooner than the configured TTL, prefer the shorter window.

9. **How do batches and chains interact?**  
   No special behavior should be needed as long as cleanup runs before or alongside existing success bookkeeping in `CallQueuedHandler`. Tests should cover that resumable cleanup does not prevent chain dispatch or batch success recording.

10. **What about sync/fake jobs?**  
    `SyncJob` and `FakeJob` can expose UUID-like identifiers differently from real drivers. The trait's test helpers should make it possible to exercise checkpoint logic without a real worker, but the production default identity should still prefer the payload UUID.

11. **What if cache is unavailable?**  
    Because resumability is opt-in, failing to attach a repository should be loud rather than silently disabling checkpoints. A missing cache binding or unsupported store should surface as an exception when the resumable command is prepared.

12. **Should state be loaded before middleware?**  
    Yes. Middleware may need checkpoint information for throttling, skipping, or routing behavior. Attaching and loading before the pipeline also ensures signal handlers see the same context while the command is running.

## Grill-Me Pass

### API Pressure Questions

- Is `Resumable` enough as a marker, or should it also declare `resumeKey(): ?string` so the override is discoverable in contracts?
- Should checkpoint names be free-form strings, or should the trait reject empty names and names containing cache-key separators such as `:`?
- Should `resumeState($checkpoint)` return `null` for a missing checkpoint, or should it accept a default value like `resumeState('step', default: [])`?
- Should `checkpoint()` replace the whole named checkpoint, or merge with existing state? Replacement is simpler and avoids stale fields; merging can hide bugs.
- Should `forgetCheckpoint()` delete only a named checkpoint while `flushResumeState()` deletes the full resume record?
- Is `releaseFromCheckpoint()` worth adding, or is it just a convenience wrapper around `checkpoint()` plus `release()` that creates another method to document?
- Should the trait expose all checkpoints (`resumeState()` with no name) for diagnostics, or keep the API checkpoint-scoped?
- Do we need an explicit `hasCheckpoint($name)` to distinguish a missing checkpoint from one intentionally stored as `null`?

### Lifecycle Pressure Questions

- `CallQueuedHandler::call()` is the right first hook point because the command is deserialized and the concrete queue job is available before middleware runs.
- State should be attached before job middleware executes so middleware can inspect or update checkpoints if needed.
- Clearing state after success should happen after `ensureNextJobInChainIsDispatched()` and `ensureSuccessfulBatchJobIsRecorded()` so a failure in those framework follow-up steps does not erase recoverable progress too early.
- Manual `delete()` needs a deliberate policy. Laravel treats delete as terminal for queue removal, so clearing resume state on delete is consistent, but it prevents jobs from deleting themselves as a pause primitive.
- Exception releases from `Worker::handleJobException()` should keep resume state; the handler can only know the job was later released indirectly through the queue job state.
- Timeout handling is not cooperative in the same way as `Interruptible`; a hard timeout may not give user code a chance to checkpoint.
- `Interruptible` works by asking the current `CallQueuedHandler` for the running command, so the attached resume context must live on the command instance, not only in a local handler variable.
- Model-not-found handling may fail before the command can be resumed. If the command cannot be hydrated, there is no safe domain `resumeKey()` lookup unless it was captured in payload metadata at dispatch time.

### Storage Pressure Questions

- Cache-backed storage is portable, but cache stores can evict data; the docs should frame resume state as durable enough for queue retry windows, not a replacement for domain persistence.
- A single cache record per resume key makes load/save simple, but concurrent jobs sharing a custom `resumeKey()` can overwrite each other without compare-and-swap or locks.
- If custom `resumeKey()` enables shared workflows, the first implementation should either document single-active-job ownership or add atomic update semantics.
- TTL should be explicit. A reasonable order is `retryUntil()` when present, a job property/method override such as `resumeStateTtl()`, then `config('queue.resume.ttl')`.
- Storing encrypted jobs does not encrypt resume state. Optional encryption can be deferred, but the plan should document that checkpoints must not contain secrets unless encrypted storage is configured.
- JSON serializability should be validated before writing to cache, ideally by encoding with `JSON_THROW_ON_ERROR` and decoding back to supported scalar / array shapes.
- Cache tags are not universally supported, so cleanup should rely on deterministic keys instead of tag flushing.

### Compatibility Pressure Questions

- Should queued closures be excluded initially? They run through `CallQueuedHandler`, but a marker interface and trait are less natural there.
- Should queued listeners be supported on pass one? `CallQueuedListener` wraps listener metadata, so the actual listener instance may not use the trait in the same way as job classes.
- Should sync queue jobs support resume state? They have a fake-ish queue job surface and no retry window; supporting them may mainly help tests.
- How should batches behave if a resumable job completes after several releases? Batch success should record once, after final success, matching current `CallQueuedHandler` behavior.
- Chains should not advance on release. Clearing resume state only after non-released success preserves the existing chain semantics.
- Unique jobs and `ShouldBeUniqueUntilProcessing` already have lock-release timing in `CallQueuedHandler`; resume cleanup must not change those lock paths.

## Things To Be Careful About

- Existing `release()` requeues the same payload; it does not serialize runtime mutations back into the job.
- Attempts should continue behaving normally. Resume state should not reset attempts or bypass `maxTries` / `retryUntil`.
- Successful completion should clear state, but manual `delete()` semantics need a deliberate decision: clearing on delete is probably right if delete means "done", but not if developers use delete as a control-flow escape.
- Failed jobs may need their resume state retained briefly for debugging or retry-from-failed workflows; this needs a policy decision.
- Encrypted jobs should not imply encrypted checkpoint data. If checkpoint state may contain secrets, the storage layer needs an encryption option or clear documentation.
- Batches and chains should work without special behavior. Resume state belongs to the individual queued job/resume key.

## Tentative Decisions For A First Pass

1. Keep `Resumable` as a marker interface for now. Treat `resumeKey()` as an optional method, matching existing Laravel job conventions like `middleware()`, `backoff()`, and `retryUntil()`.
2. Store checkpoint state as one JSON-compatible associative array per resume identity.
3. Make `checkpoint($name, $state)` replace the named checkpoint, not merge it.
4. Have `resumeState($name, $default = null)` return the default when no checkpoint exists.
5. Reject empty checkpoint names and non-JSON-serializable checkpoint values.
6. Do not add `releaseFromCheckpoint()` in the first implementation unless examples show it materially improves ergonomics.
7. Clear state only on successful, non-released completion in the first pass. Revisit manual delete and failure cleanup after tests document current behavior.
8. Document that custom `resumeKey()` values are single-writer unless the storage layer later grows atomic mutation support.
9. Defer optional encryption, queued closure support, and queued listener support until the core job lifecycle is proven.

## First Implementation Pass

1. Add the `Resumable` contract and `InteractsWithResumableState` trait.
2. Add a small cache-backed resume-state repository.
3. Teach `CallQueuedHandler` to attach/load/clear state around commands implementing `Resumable`.
4. Add focused queue worker tests for:
   - state loads before `handle()`;
   - named checkpoints persist across `release()`;
   - successful completion clears state;
   - `resumeKey()` overrides the job UUID;
   - checkpoint values reject non-JSON-serializable data.
5. Add one interruptible/resumable test showing a signal handler can checkpoint before the worker exits.

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
    public function checkpoint(string $name, array|bool|float|int|string|null $state = null): void;

    public function resumeState(?string $name = null, mixed $default = null): mixed;

    public function forgetCheckpoint(string $name): void;

    public function releaseFromCheckpoint(DateTimeInterface|DateInterval|int $delay = 0): void;

    public function resumeKey(): ?string;
}
```

Open point: `resumeKey()` may not belong in the trait if it encourages accidental `null` overrides. It may be cleaner to make the handler use `method_exists($command, 'resumeKey') ? $command->resumeKey() : $job->uuid()`.

Repository responsibilities:

- Build namespaced keys from connection, queue, and resume key.
- Load all checkpoint state before the job runs.
- Persist the whole checkpoint map after each mutation.
- Forget a named checkpoint.
- Clear all state on successful completion.
- Validate JSON encodability before writing.
- Apply TTL consistently on each write.

Non-goals for the first implementation:

- Mutating the serialized job command payload.
- Automatically checkpointing local object properties.
- Cross-job locking or merge conflict resolution.
- Driver-specific Redis APIs.
- A database-backed resume state table.
- Transparent encryption without an explicit option.

Additional negative tests for unsupported or deferred behavior:

- manual `delete()` does not accidentally clear or preserve state without an explicit policy;
- failed jobs retain state until a cleanup policy is chosen;
- two jobs sharing a custom `resumeKey()` document last-write-wins behavior if atomic updates are deferred.

## Open Decisions

- Exact method names: `checkpoint`, `resumeState`, `forgetCheckpoint`, `releaseFromCheckpoint`.
- Default TTL and whether it should be configured globally.
- Whether failed jobs retain or clear resume state by default.
- Whether checkpoint storage should support optional encryption in the first version.
- Whether manual `delete()` inside `handle()` should always clear state.
- Whether `resumeState()` without a checkpoint name should return the whole checkpoint map.
- Whether the marker interface should require any methods or remain empty like other opt-in queue contracts.
- Whether queued listeners and closures are in scope for the first implementation.
