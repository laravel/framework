<?php

namespace Illuminate\Testing;

use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Mockery;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class PendingFormRequest {

    /**
     * The test being run
     *
     * @var \Illuminate\Foundation\Testing\TestCase
     */
    public $test;

    /**
     * The application instance
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * The gate instance
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * The underlying form request to be tested
     *
     * @var \Illuminate\Foundation\Http\FormRequest
     */
    protected $formRequest;

    /**
     * The list of gates executed
     *
     * @var array
     */
    protected $gatesExecuted = [];

    /**
     * List of expected gates executed
     *
     * @var array
     */
    protected $expectedGates = [];

    /**
     * List of expected validation lists
     *
     * @var array
     */
    protected $validateOn = [];

    /**
     * Determine if command has executed.
     *
     * @var bool
     */
    protected $hasExecuted = false;

    /**
     * Create a new pending console command run
     *
     * @param \PHPUnit\Framework\TestCase $test
     * @param \Illuminate\Contracts\Container\Container $app
     * @param \Illuminate\Foundation\Http\FormRequest|string $formRequest
     * @param string $route
     * @param string $method
     */
    public function __construct(PHPUnitTestCase $test, Container $app, $formRequest, $route, $method) {
        $this->app = $app;
        $this->test = $test;

        // Set up gate checking
        $this->prepGates($app->get(Gate::class));
        $this->prepFormRequest($formRequest, $route, $method);
    }

    /**
     * Prepare the pending form request's gate
     *
     * @param \Illuminate\Contracts\Auth\Access\Gate $gate
     * @return $this
     */
    private function prepGates(Gate $gate) {
        $this->expectedGates = [];
        $this->gatesExecuted = [];

        $this->gate = $gate;

        $this->gate->after(function ($user, $ability, $result, $arguments = []) {
            $this->gatesExecuted[] = [
                'ability' => $ability,
                'result' => $result,
                'arguments' => $arguments,
            ];
        });

        return $this;
    }

    /**
     * Prepare the pending form request's form request
     *
     * @param \Illuminate\Foundation\Http\FormRequest|string $formRequest
     * @param string $route
     * @param string $method
     * @return $this
     */
    private function prepFormRequest($formRequest, $route, $method) {
        // Build the form request
        $this->formRequest = tap($formRequest::create($route, $method)
            ->setContainer($this->app)
            ->setRedirector(tap(Mockery::mock(Redirector::class), function ($redirector) {
                $fakeUrlGenerator = Mockery::mock();
                $fakeUrlGenerator->shouldReceive('to', 'route', 'action', 'previous')->withAnyArgs()->andReturn(null);

                $redirector->shouldReceive('getUrlGenerator')->andReturn($fakeUrlGenerator);
            }))
            ->setUserResolver(function () {
                return $this->app->get(AuthManager::class)->user();
            })->setRouteResolver(function () {
                $router = $this->app->get(Router::class);
                $routes = Route::getRoutes();
                $route = null;
                $route = $routes->match($this->formRequest);

                // Resolve bindings
                $router->substituteBindings($route);
                $router->substituteImplicitBindings($route);

                return $route;
            }), function (FormRequest $formRequest) {
            $formRequest->files->replace([]);
            $formRequest->query->replace([]);
            $formRequest->request->replace([]);
        });

        return $this;
    }

    /**
     * Set the user for the form request
     *
     * @param \Illuminate\Database\Eloquent\Model $user
     * @return $this
     */
    public function withUser($user) {
        $this->formRequest->setUserResolver(function () use ($user) {
            return $user;
        });

        return $this;
    }

    /**
     * Execute the command
     *
     * @return $this
     */
    public function execute() {
        return $this->run();
    }

    /**
     * Execute the command
     *
     * @return $this
     */
    public function run() {
        $this->hasExecuted = true;

        // Trigger authorize and check if all gates were executed
        if (count($this->expectedGates) > 0) {
            $this->formRequest->authorize();

            $this->verifyExpectedGates();
        }

        foreach ($this->validateOn as $count => $run) {
            // "Reset" all of the form requests data to this run's data
            $this->formRequest->files->replace($run['files'] ?? []);
            $this->formRequest->query->replace($run['get'] ?? []);
            $this->formRequest->request->replace($run['post'] ?? []);

            // Grab merged files and input data
            $data = array_merge(
                $this->formRequest->all(),
                $this->formRequest->files->all()
            );
            $rules = $this->formRequest->rules();
            $messages = $this->formRequest->messages();
            $attributes = $this->formRequest->attributes();

            $validator = Validator::make($data, $rules, $messages, $attributes);
            $validator->passes();

            $errors = $validator->errors()->toArray();

            // Validate passed fields
            foreach ($run['passesOn'] as $key) {
                $this->test->assertArrayNotHasKey($key, $errors, "Failed to assert the field {$key} passed validation [Validation # {$count}] [" . json_encode($errors) . "]");
            }

            // Validate failed fields
            foreach ($run['failsOn'] as $key) {
                $this->test->assertArrayHasKey($key, $errors, "Failed to assert the field {$key} failed validation [Validation # {$count}] [" . json_encode($errors) . "]");
            }
        }

        return $this;
    }

    /**
     * Determine if expected gates were called
     *
     * @return void
     */
    protected function verifyExpectedGates() {
        $actualGates = collect($this->gatesExecuted);
        foreach ($this->expectedGates as $expectedGate) {
            $matched = $actualGates->filter(function ($actualGate) use ($expectedGate) {
                $result = true;

                // If ability is requested to be matched
                if ($expectedGate['ability'] !== null) {
                    // Check if ability matches
                    $result &= ($expectedGate['ability'] == $actualGate['ability']);
                }

                // If result is requested to be matched
                if ($expectedGate['result'] !== null) {
                    // Check if result matches
                    $result &= ($expectedGate['result'] == $actualGate['result']);
                }

                // If arguments are requested to be matched
                if ($expectedGate['arguments'] !== null) {
                    // Fail if count doesn't equal
                    if (count($expectedGate['arguments']) != count($actualGate['arguments'])) {
                        $result &= false;
                    } else {
                        // Otherwise match each element
                        for ($index = 0; $index < count($expectedGate['arguments']); $index++) {
                            if ($expectedGate['arguments'][$index] instanceof Model && $actualGate['arguments'][$index] instanceof Model) {
                                // Compare models with $model->is($other)
                                $result &= $expectedGate['arguments'][$index]->is($actualGate['arguments'][$index]);
                            } else {
                                $result &= ($expectedGate['arguments'][$index] == $actualGate['arguments'][$index]);
                            }
                        }
                    }
                }

                return $result;
            });

            $this->test->assertGreaterThan(0, $matched->count(), "Failed to assert that the gate {$expectedGate['ability']} resulted in {$expectedGate['result']}");
        }
    }

    /**
     * @return \Illuminate\Foundation\Http\FormRequest
     */
    public function getFormRequest() {
        return $this->formRequest;
    }

    /**
     * Add an expected gate to the pending list of gates
     *
     * @param string $ability
     * @param boolean|null $result
     * @param array $arguments
     * @return $this
     */
    public function expectsGate($ability, $arguments = [], $result = null) {
        $this->expectedGates[] = [
            'ability' => $ability,
            'result' => $result,
            'arguments' => $arguments,
        ];

        return $this;
    }

    /**
     * @param array $GET
     * @param array $POST
     * @param array $files
     * @param array $invalidFields
     * @param array $validFields
     * @return $this
     */
    public function withValidation(array $GET, array $POST, array $files, array $invalidFields, array $validFields) {
        $this->validateOn[] = [
            'get' => $GET,
            'post' => $POST,
            'files' => $files,
            'passesOn' => $validFields,
            'failsOn' => $invalidFields,
        ];

        return $this;
    }

    /**
     * @param array $data
     * @param array $invalidFields
     * @return $this
     */
    public function failsOn(array $data, array $invalidFields) {
        $data = $this->formatValidateOnData($data);

        return $this->withValidation($data['get'], $data['post'], $data['files'], $invalidFields, []);
    }

    /**
     * Add an expected validation item to the list
     *
     * @param array $data
     * @param array $validFields
     * @return $this
     */
    public function passesOn(array $data, array $validFields) {
        $data = $this->formatValidateOnData($data);

        return $this->withValidation($data['get'], $data['post'], $data['files'], [], $validFields);
    }

    /**
     * @param array $data
     * @return array
     */
    private function formatValidateOnData(array $data) {
        $get = [];
        $post = [];
        $files = [];
        if (isset($data['GET']) || isset($data['POST']) || isset($data['files'])) {
            $get = $data['GET'] ?? [];
            $post = $data['POST'] ?? [];
            $files = $data['files'] ?? [];
        }

        unset($data['GET']);
        unset($data['POST']);
        unset($data['files']);

        if ($this->formRequest->getMethod() == 'GET') {
            $get = array_merge($get, $data);
        } else {
            $post = array_merge($post, $data);
        }

        return [
            'get' => $get,
            'post' => $post,
            'files' => $files
        ];
    }
}
