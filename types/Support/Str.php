<?php

use Illuminate\Support\Str;

use function PHPStan\Testing\assertType;

/**
 * @var mixed $json
 * @var mixed $ulid
 * @var mixed $url
 * @var mixed $uuid
 * @var string $strStartsWith
 */
if (Str::isJson($json)) {
    assertType('non-empty-string', $json);
} else {
    assertType('mixed', $json);
}

if (Str::isUlid($ulid)) {
    assertType('non-empty-string', $ulid);
} else {
    assertType('mixed', $ulid);
}

if (Str::isUrl($url)) {
    assertType('non-empty-string', $url);
} else {
    assertType('mixed', $url);
}

if (Str::isUuid($uuid)) {
    assertType('non-empty-string', $uuid);
} else {
    assertType('mixed', $uuid);
}

if (Str::startsWith($strStartsWith, '')) {
    assertType('non-empty-string', $strStartsWith);
} else {
    assertType('string', $strStartsWith);
}

if (Str::doesntStartWith($strStartsWith, '')) {
    assertType('string', $strStartsWith);
} else {
    assertType('non-empty-string', $strStartsWith);
}

/**
 * @var string $search
 * @var string $replace
 * @var string $subject
 */
assertType('string', Str::replace($search, $replace, $subject));
assertType('array<string>', Str::replace($search, $replace, [$subject]));

assertType('\'\'', Str::camel(''));
assertType('string', Str::camel('Taylor Otwell'));

assertType('false', Str::contains('Taylor Otwell', []));
assertType('false', Str::contains('', 'Taylor'));
assertType('bool', Str::contains('Taylor Otwell', 'Taylor'));

assertType('false', Str::containsAll('Taylor Otwell', []));
assertType('bool', Str::containsAll('Taylor Otwell', ['Taylor']));

assertType('true', Str::doesntContain('Taylor Otwell', []));
assertType('true', Str::doesntContain('', 'Taylor'));
assertType('bool', Str::doesntContain('Taylor Otwell', 'Taylor'));

assertType('\'\'', Str::convertCase(''));
assertType('string', Str::convertCase('Taylor Otwell'));

assertType('\'\'', Str::deduplicate(''));
assertType('string', Str::deduplicate('Taylor Otwell'));

assertType('false', Str::endsWith('Taylor Otwell', []));
assertType('false', Str::endsWith('', 'Taylor'));
assertType('bool', Str::endsWith('Taylor Otwell', 'Taylor'));

assertType('true', Str::doesntEndWith('Taylor Otwell', []));
assertType('true', Str::doesntEndWith('', 'Taylor'));
assertType('bool', Str::doesntEndWith('Taylor Otwell', 'Taylor'));

assertType('\'\'', Str::kebab(''));
assertType('string', Str::kebab('Taylor Otwell'));

assertType('lowercase-string', Str::lower(''));
assertType('uppercase-string', Str::upper(''));

assertType('\'\'', Str::markdown(''));
assertType('string', Str::markdown('Taylor Otwell'));

assertType('\'\'', Str::inlineMarkdown(''));
assertType('string', Str::inlineMarkdown('Taylor Otwell'));

assertType('false', Str::isMatch([], 'Taylor Otwell'));
assertType('bool', Str::isMatch(['Taylor'], 'Taylor Otwell'));

assertType('numeric-string', Str::password(letters: false, symbols: false, spaces: false));
assertType('string', Str::password());

assertType('false', Str::position('Taylor Otwell', ''));
assertType('false', Str::position('', 'Taylor'));
assertType('bool', Str::position('Taylor Otwell', 'Taylor'));

assertType('string|null', Str::replaceMatches('Taylor Otwell', '', ''));
assertType('array<string>|null', Str::replaceMatches(['Taylor', 'Otwell'], '', ''));

assertType('false', Str::startsWith('Taylor Otwell', []));
assertType('false', Str::startsWith('', 'Taylor'));
assertType('bool', Str::startsWith('Taylor Otwell', 'Taylor'));

assertType('true', Str::doesntStartWith('Taylor Otwell', []));
assertType('true', Str::doesntStartWith('', 'Taylor'));
assertType('bool', Str::doesntStartWith('Taylor Otwell', 'Taylor'));

assertType('\'\'', Str::studly(''));
assertType('string', Str::studly('Taylor Otwell'));

assertType('\'\'', Str::pascal(''));
assertType('string', Str::pascal('Taylor Otwell'));

assertType('\'\'', Str::toBase64(''));
assertType('string', Str::toBase64('Taylor Otwell'));

assertType('\'\'', Str::fromBase64(''));
assertType('string', Str::fromBase64('Taylor Otwell'));

assertType('\'\'', Str::lcfirst(''));
assertType('string', Str::lcfirst('Taylor Otwell'));

assertType('\'\'', Str::ucfirst(''));
assertType('string', Str::ucfirst('Taylor Otwell'));

assertType('\'\'', Str::ucwords(''));
assertType('string', Str::ucwords('Taylor Otwell'));

assertType('array{}', Str::ucsplit(''));
assertType('string[]', Str::ucsplit('Taylor Otwell'));
