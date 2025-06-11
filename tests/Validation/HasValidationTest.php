<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Validation\HasValidation;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;

class HasValidationTest extends TestCase
{
    public function testHasValidationPassesIfNoRulesProvided()
    {
        $this->expectNotToPerformAssertions();

        $object = new DummyObject();
        $object->validate();
    }

    public function testHasValidationPassesForDefinedRules()
    {
        $this->expectNotToPerformAssertions();

        $object = new DummyUser();
        $object->email = 'test@laravel.com';
        $object->name = 'Taylor';
        $object->validate();
    }

    public function testHasValidationThrowsExceptionForDefinedRules()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The name field must be at least 3 characters.');

        $object = new DummyUser();
        $object->email = 'test@laravel.com';
        $object->name = 'a';
        $object->validate();
    }

    public function testHasValidationPassesForModelDefinedRules()
    {
        $this->expectNotToPerformAssertions();

        $object = new DummyUserModel();
        $object->email = 'test@laravel.com';
        $object->name = 'Taylor';
        $object->validate();
    }

    public function testHasValidationThrowsExceptionForModelDefinedRules()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The name field must be at least 3 characters.');

        $object = new DummyUserModel();
        $object->email = 'test@laravel.com';
        $object->name = 'a';
        $object->validate();
    }

    public function testHasValidationPassesForDefinedArrayRules()
    {
        $this->expectNotToPerformAssertions();

        $object = new DummyReviewList();
        $object->author = 'Taylor';
        $object->reviews = collect([5, 4]);
        $object->validate();
    }

    public function testHasValidationThrowsExceptionForDefinedArrayRules()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The reviews.0 field must be an integer.');

        $object = new DummyReviewList();
        $object->author = 'Taylor';
        $object->reviews = collect(['a', 'b']);
        $object->validate();
    }

    public function testHasValidationUsesCustomMessage()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The brand is required for creating a new car.');

        $object = new DummyCar();
        $object->validate();
    }
}

class DummyObject
{
    use HasValidation;

    public string $name;
}

class DummyUser
{
    use HasValidation;

    public string $email;

    public string $name;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'min:3'],
        ];
    }
}

class DummyUserModel extends Model
{
    use HasValidation;

    protected $fillable = ['email', 'name'];

    protected function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'min:3'],
        ];
    }
}

class DummyReviewList
{
    use HasValidation;

    public string $author;

    public Collection $reviews;

    protected function rules(): array
    {
        return [
            'author' => ['required', 'string', 'min:3'],
            'reviews' => ['required', 'array'],
            'reviews.*' => ['required', 'int'],
        ];
    }
}

class DummyCar
{
    use HasValidation;

    public ?string $brand = null;

    protected function rules(): array
    {
        return [
            'brand' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'brand.required' => 'The brand is required for creating a new car.',
        ];
    }
}
