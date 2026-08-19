<?php

namespace Illuminate\Tests\App\Models\Casts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsEnumArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\AsFluent;
use Illuminate\Database\Eloquent\Casts\AsHtmlString;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Casts\AsUri;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\App\Casts\CustomCollection;
use Illuminate\Tests\App\Casts\StringableCastBuilder;
use Illuminate\Tests\App\Casts\TestCast;
use Illuminate\Tests\Database\StringStatus;

class EloquentModelCastingStub extends Model
{
    protected $casts = [
        'floatAttribute' => 'float',
        'boolAttribute' => 'bool',
        'objectAttribute' => 'object',
        'jsonAttribute' => 'json',
        'jsonAttributeWithUnicode' => 'json:unicode',
        'dateAttribute' => 'date',
        'timestampAttribute' => 'timestamp',
        'ascollectionAttribute' => AsCollection::class,
        'asCustomCollectionAsArrayAttribute' => [AsCollection::class, CustomCollection::class],
        'asEncryptedCollectionAttribute' => AsEncryptedCollection::class,
        'asEnumCollectionAttribute' => AsEnumCollection::class.':'.StringStatus::class,
        'asEnumArrayObjectAttribute' => AsEnumArrayObject::class.':'.StringStatus::class,
        'duplicatedAttribute' => 'string',
    ];

    protected function casts(): array
    {
        return [
            'intAttribute' => 'int',
            'stringAttribute' => 'string',
            'booleanAttribute' => 'boolean',
            'arrayAttribute' => 'array',
            'collectionAttribute' => 'collection',
            'datetimeAttribute' => 'datetime',
            'asarrayobjectAttribute' => AsArrayObject::class,
            'asStringableAttribute' => AsStringable::class,
            'asHtmlStringAttribute' => AsHtmlString::class,
            'asUriAttribute' => AsUri::class,
            'asFluentAttribute' => AsFluent::class,
            'asCustomCollectionAttribute' => AsCollection::using(CustomCollection::class),
            'asEncryptedArrayObjectAttribute' => AsEncryptedArrayObject::class,
            'asEncryptedCustomCollectionAttribute' => AsEncryptedCollection::using(CustomCollection::class),
            'asEncryptedCustomCollectionAsArrayAttribute' => [AsEncryptedCollection::class, CustomCollection::class],
            'asCustomEnumCollectionAttribute' => AsEnumCollection::of(StringStatus::class),
            'asCustomEnumArrayObjectAttribute' => AsEnumArrayObject::of(StringStatus::class),
            'singleElementInArrayAttribute' => [AsCollection::class],
            'duplicatedAttribute' => 'int',
            'asToObjectCast' => TestCast::class,
            'castStringableObject' => new StringableCastBuilder(),
        ];
    }

    public function jsonAttributeValue()
    {
        return $this->attributes['jsonAttribute'];
    }

    public function jsonAttributeWithUnicodeValue()
    {
        return $this->attributes['jsonAttributeWithUnicode'];
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
