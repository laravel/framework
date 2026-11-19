<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Auth\Access\Gate;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Can;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

class ValidationRuleCanTest extends TestCase
{
    protected $container;
    protected $user;
    protected $router;

    protected function setUp(): void
    {
        $this->user = new stdClass;

        Container::setInstance($this->container = new Container);

        $this->container->singleton(GateContract::class, function () {
            return new Gate($this->container, function () {
                return $this->user;
            });
        });

        $this->container->bind('translator', function () {
            return new Translator(
                new ArrayLoader, 'en'
            );
        });

        Facade::setFacadeApplication($this->container);

        (new ValidationServiceProvider($this->container))->register();
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        Facade::clearResolvedInstances();

        Facade::setFacadeApplication(null);
    }

    public function testValidationFails()
    {
        $this->gate()->define('update-company', function ($user, $value) {
            $this->assertSame('1', $value);

            return false;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => '1'],
            ['company' => new Can('update-company')]
        );

        $this->assertTrue($v->fails());
    }

    public function testValidationPasses()
    {
        $this->gate()->define('update-company', function ($user, $class, $model, $value) {
            $this->assertEquals(\App\Models\Company::class, $class);
            $this->assertInstanceOf(stdClass::class, $model);
            $this->assertSame('1', $value);

            return true;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => '1'],
            ['company' => new Can('update-company', [\App\Models\Company::class, new stdClass])]
        );

        $this->assertTrue($v->passes());
    }

    public function testCustomMessageUsingDotNotationAndFqcnWorks()
    {
        $v = new Validator(
            resolve('translator'),
            [
                'company' => '1',
                'company_fqcn' => '1',
            ],
            [
                'company' => new Can('update-company', [\App\Models\Company::class, new stdClass]),
                'company_fqcn' => new Can('update-company', [\App\Models\Company::class, new stdClass]),
            ],
            [
                'company.can' => 'You dont have permission (dot notation)',
                'company_fqcn.Illuminate\Validation\Rules\Can' => 'You dont have permission (fqcn)',
            ]
        );

        $this->assertTrue($v->fails());

        $this->assertSame([
            'You dont have permission (dot notation)',
            'You dont have permission (fqcn)',
        ], $v->messages()->all());
    }

    #[DataProvider('messageProvider')]
    public function testValidationMessages(Response $response, ?string $translation, ?string $custom, string $expected)
    {
        $this->gate()->define('update-company', fn ($user, $value) => $response);

        $translator = resolve('translator');

        if ($translation) {
            $translator->addLines(['validation.can' => $translation], 'en');
        }

        $v = new Validator(
            $translator,
            ['company' => '1'],
            ['company' => new Can('update-company')],
            $custom ? ['company.can' => $custom] : []
        );

        $this->assertSame([$expected], $v->messages()->get('company'));
    }

    public static function messageProvider()
    {
        $reason = 'Archived companies cannot be updated.';

        return [
            'default with reason' => [Response::deny($reason), null, null, "The company field contains an unauthorized value. {$reason}"],
            'default without reason' => [Response::deny(), null, null, 'The company field contains an unauthorized value.'],
            'translation with placeholder' => [Response::deny($reason), 'You may not select this :attribute. :reason', null, "You may not select this company. {$reason}"],
            'translation without placeholder' => [Response::deny($reason), 'You may not select this :attribute.', null, 'You may not select this company.'],
            'custom with placeholder' => [Response::deny($reason), null, 'Choose a different :attribute. :reason', "Choose a different company. {$reason}"],
            'custom without placeholder' => [Response::deny($reason), null, 'Choose a different :attribute.', 'Choose a different company.'],
        ];
    }

    public function testEachRuleUsesItsOwnReason()
    {
        $this->gate()->define('update-company', fn ($user, $value) => Response::deny('Archived companies cannot be updated.'));
        $this->gate()->define('update-team', fn ($user, $value) => Response::deny('Only team owners may update the team.'));

        $v = new Validator(
            resolve('translator'),
            ['company' => '1', 'team' => '1'],
            ['company' => new Can('update-company'), 'team' => new Can('update-team')],
            ['company.can' => 'Invalid :attribute. :reason', 'team.can' => 'Invalid :attribute. :reason']
        );

        $this->assertSame(['Invalid company. Archived companies cannot be updated.'], $v->messages()->get('company'));
        $this->assertSame(['Invalid team. Only team owners may update the team.'], $v->messages()->get('team'));
    }

    public function testFalsyValuesArePassedToThePolicy()
    {
        $this->gate()->define('update-company', function ($user, $model, $value) {
            $this->assertInstanceOf(stdClass::class, $model);
            $this->assertSame(0, $value);

            return true;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => 0],
            ['company' => new Can('update-company', [new stdClass])]
        );

        $this->assertTrue($v->passes());
    }

    public function testArgumentsCanBeResolvedUsingCallback()
    {
        $model = new stdClass;

        $this->gate()->define('update-company', function ($user, ...$arguments) use ($model) {
            $this->assertSame([null, 'ACTIVE', $model], $arguments);

            return true;
        });

        $v = new Validator(
            resolve('translator'),
            ['company' => 'active'],
            ['company' => Rule::can('update-company', new stdClass)->using(function ($value, $attribute) use ($model) {
                $this->assertSame('company', $attribute);

                return [null, strtoupper($value), $model];
            })]
        );

        $this->assertTrue($v->passes());
    }

    /**
     * Get the Gate instance from the container.
     *
     * @return \Illuminate\Auth\Access\Gate
     */
    protected function gate()
    {
        return $this->container->make(GateContract::class);
    }
}
