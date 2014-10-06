<?php namespace App\Http\Controllers;

/**
 * @Resource("foobar/photos", only={"index", "update"}, names={"index": "index.name"})
 * @Controller(domain="{id}.account.com")
 * @Before("auth")
 * @Before("csrf", on={"post", "put", "delete"})
 * @Where({"id": "regex"})
 */
class BasicController {

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
