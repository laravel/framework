<?php

use Illuminate\Database\Seeder;
use \Illuminate\Support\Facades\DB;
class OrdersAndProductsSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // orders table 1 row is enough for us
        DB::table('orders')->insert([
            'reference' => '12345678',
        ]);

        // products in orders
        DB::table('product_orders')->insert([
            ['name' =>'imac','qty'=>'1','price'=>'1500','order_id'=>1],
            ['name' =>'galaxy s9','qty'=>'2','price'=>'1000','order_id'=>1],
            ['name' =>'apple watch','qty'=>'3','price'=>'1200','order_id'=>1],
        ]);

    }
}
