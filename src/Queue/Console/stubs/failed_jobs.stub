<?php

use Illuminate\Database\Migrations\Migration;

class CreateFailedJobsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('failed_jobs', function($t)
		{
			$t->increments('id');
			$t->text('connection');
			$t->text('queue');
			$t->text('payload');
			$t->timestamp('failed_at');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('failed_jobs');
	}

}
