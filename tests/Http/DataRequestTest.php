<?php
namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Http\DataRequest;
use Illuminate\Container\Container;
use Illuminate\Validation\Factory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Validation\Factory as ValidatorFactoryContract;


class DataRequestTest extends TestCase
{
    public function testPassedValidationAssignsProperties()
    {

        $request = new class extends DataRequest {
            public $name;
            public $email;

            public function rules()
            {
                return [
                    'name' => 'required|string',
                    'email' => 'required|email',
                ];
            }
        };

        // Simulate base input
        $input = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
        ];

        // Create base Illuminate Request
        $base = Request::create('/create-dto-user-test', 'POST', $input);

        // Merge input into our custom request
        $request->initialize(
            $base->query->all(),
            $base->request->all(),
            $base->attributes->all(),
            $base->cookies->all(),
            $base->files->all(),
            $base->server->all(),
            $base->getContent()
        );

        // Inject container and validator
        $container = new Container();
        $loader = new ArrayLoader();
        $translator = new Translator($loader, 'en');
        $validatorFactory = new Factory($translator, $container);

        $container->bind(ValidatorFactoryContract::class, function () use ($validatorFactory) {
            return $validatorFactory;
        });        
        $request->setContainer($container);

        // Call validateResolved() to run validation and passedValidation()
        $request->validateResolved();

        // Now test the assignment
        $this->assertSame('Test Name', $request->name);
        $this->assertSame('test@example.com', $request->email);
    }
}
