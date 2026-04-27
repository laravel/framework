<?php

declare(strict_types=1);

namespace Illuminate\Tests\Integration\Workflow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Illuminate\Workflow\WorkflowName;
use Illuminate\Workflow\WorkflowOrchestratorOriginalGangsterNotGood;
use Illuminate\Workflow\WorkflowStep;
use PHPUnit\Framework\Attributes\Test;

use function array_column;

final class WorkflowOrchestratorOriginalGangsterNotGoodTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('workflow_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('external_id');
            $table->string('profile_name')->nullable();
        });
    }

    #[Test]
    public function attributed_workflow_replays_completed_steps_from_state(): void
    {
        Http::fake([
            'https://example.com/profiles/cus_123' => Http::response(['name' => 'Taylor']),
            'https://example.com/accounts/cus_123' => Http::response(['status' => 'active']),
        ]);

        Log::shouldReceive('info')->once()->with('Customer synced.', [
            'customer_id' => 1,
            'account_status' => 'active',
        ]);

        $customer = WorkflowCustomer::query()->create([
            'external_id' => 'cus_123',
        ]);

        $snapshots = [];

        // Given a workflow that has already fetched the model and completed the first HTTP call.
        $orchestrator = WorkflowOrchestratorOriginalGangsterNotGood::make(
            new SyncCustomerWorkflow($customer->id),
            persistState: function (array $snapshot) use (&$snapshots): void {
                $snapshots[] = $snapshot;
            },
        );

        $firstSnapshot = $orchestrator->run();
        $secondSnapshot = $orchestrator->run();

        self::assertSame('sync-customer', $firstSnapshot['workflow']);
        self::assertSame('customer', $firstSnapshot['step']);
        self::assertSame('profile', $secondSnapshot['step']);
        self::assertSame(['customer', 'profile'], array_column($snapshots, 'step'));
        self::assertNull($customer->refresh()->profile_name);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.com/profiles/cus_123');

        // When a later process rebuilds the orchestrator from persisted workflow state.
        $orchestrator = WorkflowOrchestratorOriginalGangsterNotGood::make(
            new SyncCustomerWorkflow($customer->id),
            state: $secondSnapshot['state'],
        );

        $replayedSnapshots = $this->runUntilComplete($orchestrator);

        // Then previously completed steps are skipped and only the remaining side effects run.
        self::assertSame([
            'customerUpdated',
            'account',
            'logWritten',
            null,
        ], array_column($replayedSnapshots, 'step'));

        self::assertSame('Taylor', $customer->refresh()->profile_name);
        self::assertSame('completed', $orchestrator->lastSnapshot()['status']);
        self::assertArrayHasKey('logWritten', $orchestrator->state());

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://example.com/accounts/cus_123');
    }

    /**
     * @return list<array{status: string, workflow: string, step: string|null, payload: mixed, state: array<string, mixed>}>
     */
    private function runUntilComplete(WorkflowOrchestratorOriginalGangsterNotGood $orchestrator): array
    {
        $snapshots = [];

        do {
            $snapshots[] = $orchestrator->run();
        } while (! $orchestrator->completed());

        return $snapshots;
    }
}

class WorkflowCustomer extends Model
{
    public $timestamps = false;

    protected $table = 'workflow_customers';

    protected $guarded = [];
}

#[WorkflowName('sync-customer')]
class SyncCustomerWorkflow
{
    public function __construct(private int $customerId)
    {
    }

    #[WorkflowStep(1, 'customer')]
    public function fetchCustomer(): array
    {
        return WorkflowCustomer::query()->findOrFail($this->customerId)->toArray();
    }

    #[WorkflowStep(2, 'profile')]
    public function fetchProfile(array $customer): array
    {
        return Http::get('https://example.com/profiles/'.$customer['external_id'])
            ->throw()
            ->json();
    }

    #[WorkflowStep(3, 'customerUpdated')]
    public function updateCustomer(array $customer, array $profile): void
    {
        WorkflowCustomer::query()
            ->whereKey($customer['id'])
            ->update(['profile_name' => $profile['name']]);
    }

    #[WorkflowStep(4, 'account')]
    public function fetchAccount(array $customer): array
    {
        return Http::get('https://example.com/accounts/'.$customer['external_id'])
            ->throw()
            ->json();
    }

    #[WorkflowStep(5, 'logWritten')]
    public function writeLog(array $customer, array $account): void
    {
        Log::info('Customer synced.', [
            'customer_id' => $customer['id'],
            'account_status' => $account['status'],
        ]);
    }
}
