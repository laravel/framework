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

## Things To Be Careful About

- Existing `release()` requeues the same payload; it does not serialize runtime mutations back into the job.
- Attempts should continue behaving normally. Resume state should not reset attempts or bypass `maxTries` / `retryUntil`.
- Successful completion should clear state, but manual `delete()` semantics need a deliberate decision: clearing on delete is probably right if delete means "done", but not if developers use delete as a control-flow escape.
- Failed jobs may need their resume state retained briefly for debugging or retry-from-failed workflows; this needs a policy decision.
- Encrypted jobs should not imply encrypted checkpoint data. If checkpoint state may contain secrets, the storage layer needs an encryption option or clear documentation.
- Batches and chains should work without special behavior. Resume state belongs to the individual queued job/resume key.

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

## Open Decisions

- Exact method names: `checkpoint`, `resumeState`, `forgetCheckpoint`, `releaseFromCheckpoint`.
- Default TTL and whether it should be configured globally.
- Whether failed jobs retain or clear resume state by default.
- Whether checkpoint storage should support optional encryption in the first version.
