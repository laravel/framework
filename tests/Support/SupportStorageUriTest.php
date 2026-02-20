<?php

namespace Illuminate\Tests\Support;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\StorageUri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SupportStorageUriTest extends TestCase
{
    public function test_can_create_from_disk_and_path()
    {
        $uri = new StorageUri('local', 'path/to/file.jpg');

        $this->assertEquals('local', $uri->disk());
        $this->assertEquals('path/to/file.jpg', $uri->path());
    }

    public function test_can_create_with_null_disk()
    {
        $uri = new StorageUri(null, 'path/to/file.jpg');

        $this->assertNull($uri->disk());
        $this->assertEquals('path/to/file.jpg', $uri->path());
    }

    public function test_path_is_normalized_by_removing_leading_slash()
    {
        $uri = new StorageUri('local', '/path/to/file.jpg');

        $this->assertEquals('path/to/file.jpg', $uri->path());
    }

    public function test_of_parses_valid_uri()
    {
        $uri = StorageUri::of('storage://local/path/to/file.jpg');

        $this->assertEquals('local', $uri->disk());
        $this->assertEquals('path/to/file.jpg', $uri->path());
    }

    public function test_parse_parses_valid_uri()
    {
        $uri = StorageUri::parse('storage://s3/avatars/photo.png');

        $this->assertEquals('s3', $uri->disk());
        $this->assertEquals('avatars/photo.png', $uri->path());
    }

    public function test_parse_throws_on_invalid_uri()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid storage URI');

        StorageUri::parse(':::invalid');
    }

    public function test_parse_throws_on_wrong_scheme()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid storage URI scheme [https]');

        StorageUri::parse('https://example.com/file.jpg');
    }

    public function test_parse_throws_when_path_is_missing()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Storage URI is missing a path');

        StorageUri::parse('storage://local');
    }

    public function test_make_creates_uri_for_default_disk()
    {
        $uri = StorageUri::make('documents/report.pdf');

        $this->assertNull($uri->disk());
        $this->assertEquals('documents/report.pdf', $uri->path());
    }

    public function test_on_disk_creates_uri_for_specific_disk()
    {
        $uri = StorageUri::onDisk('s3', 'uploads/image.png');

        $this->assertEquals('s3', $uri->disk());
        $this->assertEquals('uploads/image.png', $uri->path());
    }

    public function test_extension_returns_file_extension()
    {
        $this->assertEquals('jpg', StorageUri::make('photo.jpg')->extension());
        $this->assertEquals('pdf', StorageUri::make('docs/report.pdf')->extension());
        $this->assertEquals('gz', StorageUri::make('archive.tar.gz')->extension());
        $this->assertEquals('', StorageUri::make('no-extension')->extension());
    }

    public function test_dirname_returns_directory_path()
    {
        $this->assertEquals('path/to', StorageUri::make('path/to/file.jpg')->dirname());
        $this->assertEquals('', StorageUri::make('file.jpg')->dirname());
        $this->assertEquals('deeply/nested/path', StorageUri::make('deeply/nested/path/file.txt')->dirname());
    }

    public function test_basename_returns_filename_with_extension()
    {
        $this->assertEquals('file.jpg', StorageUri::make('path/to/file.jpg')->basename());
        $this->assertEquals('file.jpg', StorageUri::make('file.jpg')->basename());
        $this->assertEquals('archive.tar.gz', StorageUri::make('backups/archive.tar.gz')->basename());
    }

    public function test_filename_returns_filename_without_extension()
    {
        $this->assertEquals('file', StorageUri::make('path/to/file.jpg')->filename());
        $this->assertEquals('file', StorageUri::make('file.jpg')->filename());
        $this->assertEquals('archive.tar', StorageUri::make('backups/archive.tar.gz')->filename());
        $this->assertEquals('no-extension', StorageUri::make('no-extension')->filename());
    }

    public function test_with_disk_returns_new_instance_with_different_disk()
    {
        $original = StorageUri::onDisk('local', 'file.jpg');
        $modified = $original->withDisk('s3');

        $this->assertEquals('local', $original->disk());
        $this->assertEquals('s3', $modified->disk());
        $this->assertEquals('file.jpg', $modified->path());
        $this->assertNotSame($original, $modified);
    }

    public function test_with_path_returns_new_instance_with_different_path()
    {
        $original = StorageUri::onDisk('local', 'old/path.jpg');
        $modified = $original->withPath('new/path.png');

        $this->assertEquals('old/path.jpg', $original->path());
        $this->assertEquals('new/path.png', $modified->path());
        $this->assertEquals('local', $modified->disk());
        $this->assertNotSame($original, $modified);
    }

    public function test_to_uri_returns_string_representation()
    {
        $this->assertEquals(
            'storage://local/path/to/file.jpg',
            StorageUri::onDisk('local', 'path/to/file.jpg')->toUri()
        );
    }

    public function test_to_uri_handles_null_disk()
    {
        $this->assertEquals(
            'storage:///path/to/file.jpg',
            StorageUri::make('path/to/file.jpg')->toUri()
        );
    }

    public function test_to_string_returns_uri()
    {
        $uri = StorageUri::onDisk('s3', 'uploads/file.pdf');

        $this->assertEquals('storage://s3/uploads/file.pdf', (string) $uri);
    }

    public function test_to_array_returns_disk_and_path()
    {
        $uri = StorageUri::onDisk('local', 'path/to/file.jpg');

        $this->assertEquals([
            'disk' => 'local',
            'path' => 'path/to/file.jpg',
        ], $uri->toArray());
    }

    public function test_to_array_handles_null_disk()
    {
        $uri = StorageUri::make('file.jpg');

        $this->assertEquals([
            'disk' => null,
            'path' => 'file.jpg',
        ], $uri->toArray());
    }

    public function test_json_serialize_returns_uri_string()
    {
        $uri = StorageUri::onDisk('local', 'file.jpg');

        $this->assertEquals('storage://local/file.jpg', $uri->jsonSerialize());
    }

    public function test_to_json_returns_json_encoded_uri()
    {
        $uri = StorageUri::onDisk('local', 'file.jpg');

        $this->assertEquals('"storage:\/\/local\/file.jpg"', $uri->toJson());
    }

    public function test_cast_using_returns_casts_attributes_implementation()
    {
        $cast = StorageUri::castUsing([]);

        $this->assertInstanceOf(CastsAttributes::class, $cast);
    }

    public function test_roundtrip_parsing()
    {
        $original = StorageUri::onDisk('local', 'path/to/file.jpg');
        $parsed = StorageUri::parse($original->toUri());

        $this->assertEquals($original->disk(), $parsed->disk());
        $this->assertEquals($original->path(), $parsed->path());
        $this->assertEquals($original->toUri(), $parsed->toUri());
    }

    public function test_complex_paths()
    {
        $uri = StorageUri::onDisk('s3', 'users/123/avatars/profile-2024.jpg');

        $this->assertEquals('users/123/avatars/profile-2024.jpg', $uri->path());
        $this->assertEquals('users/123/avatars', $uri->dirname());
        $this->assertEquals('profile-2024.jpg', $uri->basename());
        $this->assertEquals('profile-2024', $uri->filename());
        $this->assertEquals('jpg', $uri->extension());
    }

    public function test_storage_uri_is_stringable()
    {
        $uri = StorageUri::onDisk('local', 'file.jpg');

        $this->assertInstanceOf(\Stringable::class, $uri);
    }

    public function test_storage_uri_implements_required_interfaces()
    {
        $uri = StorageUri::make('file.jpg');

        $this->assertInstanceOf(\Illuminate\Contracts\Support\Arrayable::class, $uri);
        $this->assertInstanceOf(\Illuminate\Contracts\Support\Jsonable::class, $uri);
        $this->assertInstanceOf(\JsonSerializable::class, $uri);
        $this->assertInstanceOf(\Illuminate\Contracts\Database\Eloquent\Castable::class, $uri);
    }
}
