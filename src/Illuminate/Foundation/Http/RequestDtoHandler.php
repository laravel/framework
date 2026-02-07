<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Access\Response;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Precognition;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class RequestDtoHandler
{
    protected Request $request;

    protected Validator $validator;

    public function __construct(
        protected string $dtoClass,
    ) {
        $this->request = Container::getInstance()->make('request');
    }

    public function handle(): mixed
    {
        $this->prepareForValidation();

        if (! $this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $this->validator = $this->getValidatorInstance();

        if ($this->request->isPrecognitive()) {
            $this->validator->after(Precognition::afterValidationHook($this->request));
        }

        if ($this->validator->fails()) {
            $this->failedValidation();
        }

        $this->passedValidation();

        return $this->buildDto($this->validator->validated());
    }

    protected function buildDto(array $validated): mixed
    {
        $dtoClass = $this->dtoClass;

        return new $dtoClass(...$validated);
    }

    protected function prepareForValidation(): void
    {
        if (method_exists($this->dtoClass, 'prepareForValidation')) {
            Container::getInstance()->call(
                [$this->dtoClass, 'prepareForValidation'],
                ['request' => $this->request]
            );
        }
    }

    protected function passesAuthorization(): bool
    {
        if (method_exists($this->dtoClass, 'authorize')) {
            $result = Container::getInstance()->call(
                [$this->dtoClass, 'authorize'],
                ['request' => $this->request]
            );

            return $result instanceof Response ? $result->authorize() : $result;
        }

        return true;
    }

    protected function failedAuthorization(): void
    {
        if (method_exists($this->dtoClass, 'failedAuthorization')) {
            Container::getInstance()->call(
                [$this->dtoClass, 'failedAuthorization'],
                ['request' => $this->request]
            );

            return;
        }

        throw new AuthorizationException;
    }

    protected function getValidatorInstance(): Validator
    {
        $factory = Container::getInstance()->make(ValidationFactory::class);

        if (method_exists($this->dtoClass, 'validator')) {
            $validator = Container::getInstance()->call(
                [$this->dtoClass, 'validator'],
                ['factory' => $factory]
            );
        } else {
            $validator = $this->createDefaultValidator($factory);
        }

        if (method_exists($this->dtoClass, 'after')) {
            $validator->after(Container::getInstance()->call(
                [$this->dtoClass, 'after'],
                ['validator' => $validator]
            ));
        }

        return $validator;
    }

    protected function createDefaultValidator(ValidationFactory $factory): Validator
    {
        $validator = $factory->make(
            $this->validationData(),
            $this->validationRules(),
            $this->messages(),
            $this->attributes(),
        )->stopOnFirstFailure($this->shouldStopOnFirstFailure());

        if ($this->request->isPrecognitive()) {
            $validator->setRules(
                $this->request->filterPrecognitiveRules($validator->getRulesWithoutPlaceholders())
            );
        }

        return $validator;
    }

    protected function validationRules(): array
    {
        if (method_exists($this->dtoClass, 'rules')) {
            return Container::getInstance()->call([$this->dtoClass, 'rules']);
        }

        return [];
    }

    protected function validationData(): array
    {
        if (method_exists($this->dtoClass, 'validationData')) {
            return Container::getInstance()->call(
                [$this->dtoClass, 'validationData'],
                ['request' => $this->request]
            );
        }

        return $this->request->all();
    }

    protected function messages(): array
    {
        if (method_exists($this->dtoClass, 'messages')) {
            return Container::getInstance()->call([$this->dtoClass, 'messages']);
        }

        return [];
    }

    protected function attributes(): array
    {
        if (method_exists($this->dtoClass, 'attributes')) {
            return Container::getInstance()->call([$this->dtoClass, 'attributes']);
        }

        return [];
    }

    protected function shouldStopOnFirstFailure(): bool
    {
        if (method_exists($this->dtoClass, 'shouldStopOnFirstFailure')) {
            return Container::getInstance()->call([$this->dtoClass, 'shouldStopOnFirstFailure']);
        }

        return false;
    }

    protected function failedValidation(): void
    {
        if (method_exists($this->dtoClass, 'failedValidation')) {
            Container::getInstance()->call(
                [$this->dtoClass, 'failedValidation'],
                ['validator' => $this->validator]
            );

            return;
        }

        $exception = $this->validator->getException();

        throw new $exception($this->validator);
    }

    protected function passedValidation(): void
    {
        if (method_exists($this->dtoClass, 'passedValidation')) {
            Container::getInstance()->call([$this->dtoClass, 'passedValidation']);
        }
    }
}
