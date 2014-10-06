<?php namespace App\Http\Controllers;

/**
 * @Resource("foobar/photos", only={"index", "update"}, names={"index": "index.name"})
 * @Controller(domain="{id}.account.com")
 * @Middleware("FooMiddleware")
 * @Middleware("BarMiddleware")
 * @Where({"id": "regex"})
 */
class BasicController {

	/**
	 * @Middleware("BazMiddleware")
	 * @return Response
	 */
	public function index() {}

	/**
	 * @return Response
	 */
	public function update($id) {}

	/**
	 * @Put("/more/{id}", after="log")
	 * @Middleware("QuxMiddleware")
	 */
	public function doMore($id) {}

}
