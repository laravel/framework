# Encryptable

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]


Laravel package to encrypt / decrypt the database tables / columns


## Installation

Via Composer

``` bash
$ composer require alkhachatryan/encryptable:dev-master
```

## Usage

```php
class User extends Model
{
    use Encryptable;

    /**
     * Fillable columns
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * Columns which should be encrypted
     */
    protected $encryptable = ['name', 'email'];
}
```


## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Alexey Khachatryan][link-author]

## License

license. Please see the [license file](LICENSE) for more information.

[ico-version]: https://img.shields.io/packagist/v/alkhachatryan/encryptable.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/alkhachatryan/encryptable.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/alkhachatryan/encryptable/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/alkhachatryan/encryptable
[link-downloads]: https://packagist.org/packages/alkhachatryan/encryptable
[link-author]: https://github.com/alkhachatryan

