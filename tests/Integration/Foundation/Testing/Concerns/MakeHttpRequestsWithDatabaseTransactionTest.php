<?php

namespace Illuminate\Tests\Integration\Foundation\Testing\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class MakeHttpRequestsWithDatabaseTransactionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('test_table', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    public function test_it_can_make_get_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->get('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return 'ok';
        });

        $testResponse = $this->get('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_get_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->get('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return 'ok';
        });

        $testResponse = $this->get('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_get_json_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->get('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return ['message' => 'ok'];
        });

        $testResponse = $this->getJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_get_json_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->get('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return ['message' => 'ok'];
        });

        $testResponse = $this->getJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_post_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->post('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return 'ok';
        });

        $testResponse = $this->post('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_post_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->post('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return 'ok';
        });

        $testResponse = $this->post('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_post_json_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->post('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return ['message' => 'ok'];
        });

        $testResponse = $this->postJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_post_json_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->post('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return ['message' => 'ok'];
        });

        $testResponse = $this->postJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_put_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->put('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return 'ok';
        });

        $testResponse = $this->put('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_put_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->put('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return 'ok';
        });

        $testResponse = $this->put('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_put_json_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->put('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return ['message' => 'ok'];
        });

        $testResponse = $this->putJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_put_json_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->put('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return ['message' => 'ok'];
        });

        $testResponse = $this->putJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_patch_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->patch('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return 'ok';
        });

        $testResponse = $this->patch('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_patch_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->patch('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return 'ok';
        });

        $testResponse = $this->patch('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_patch_json_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->patch('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return ['message' => 'ok'];
        });

        $testResponse = $this->patchJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_patch_json_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->patch('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return ['message' => 'ok'];
        });

        $testResponse = $this->patchJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_delete_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->delete('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return 'ok';
        });

        $testResponse = $this->delete('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_delete_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->delete('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return 'ok';
        });

        $testResponse = $this->delete('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_delete_json_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->delete('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return ['message' => 'ok'];
        });

        $testResponse = $this->deleteJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_delete_json_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->delete('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return ['message' => 'ok'];
        });

        $testResponse = $this->deleteJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_options_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->options('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return 'ok';
        });

        $testResponse = $this->options('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_options_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->options('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return 'ok';
        });

        $testResponse = $this->options('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertSee('ok');

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_options_json_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->options('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            return ['message' => 'ok'];
        });

        $testResponse = $this->optionsJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }

    public function test_it_can_make_options_json_method_request_with_database_transaction_and_commit()
    {
        $this->app['router']->options('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);

            DB::commit();

            return ['message' => 'ok'];
        });

        $testResponse = $this->optionsJson('test-route');

        $testResponse
            ->assertSuccessful()
            ->assertJson(['message' => 'ok']);

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 1);
    }

    public function test_it_can_make_head_method_request_with_database_transaction_without_commit()
    {
        $this->app['router']->get('test-route', function () {
            DB::beginTransaction();

            DB::table('test_table')->insert([
                'name' => 'test',
            ]);
        });

        $testResponse = $this->head('test-route');

        $testResponse->assertSuccessful();

        $this->assertEquals(1, DB::transactionLevel());

        $this->assertDatabaseCount('test_table', 0);
    }
}
