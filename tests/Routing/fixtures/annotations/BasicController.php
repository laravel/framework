<?php namespace App\Http\Controllers;

use Illuminate\Routing\Controller;

/**
 * @Resource("foobar/photos", only={"index", "update"}, names={"index": "index.name"})
 * @Controller(domain="{id}.account.com")
 * @Before("auth")
 * @Before("csrf", on={"post", "put", "delete"})
 * @Where({"id": "regex"})
 */
class BasicController extends Controller {

	/**
	 * @Before("inline")
	 * @return Response
	 */
	public function index() {}

	/**
	 * @Before("inline")
	 * @After("inline")
	 * @return Response
	 */
	public function update($id) {}

	/**
	 * @Put("/more/{id}", after="log")
	 */
	public function doMore($id) {}

}
