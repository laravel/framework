# Threat model

## What this project does and where untrusted input enters

Laravel Framework (`src/Illuminate`) is a PHP web application framework. It is a library: applications built on
it handle requests from the internet, and the framework parses, validates, stores, and renders that data.

Treat the following as attacker-controlled:

- Everything in an HTTP request: the method, path, query string, headers (including `Host` and `X-Forwarded-*`),
  cookies, body, JSON, and uploaded files (name, MIME type, and contents).
- Route parameters, signed URL parameters, pagination parameters, and redirect targets that come from a request.
- Encrypted cookies, session cookies, CSRF tokens, password reset tokens, and "remember me" tokens that a client
  sends back.
- Data that an application validates with `Illuminate\Validation` before use, and data it renders with Blade's
  escaped `{{ }}` syntax.
- Email addresses, file names, and other strings that an application passes to the framework after validation.

Treat the following as trusted:

- `APP_KEY`, `.env`, the `config/` files, the service container bindings, and the application's own code.
- The contents of the database, cache, queue, session store, and Redis server, unless an attacker can write to
  them through the framework itself.
- Artisan commands and their arguments. Only operators run Artisan.

## Components that matter most / least

Highest priority:

1. Encryption, cookies, and sessions: `Illuminate\Encryption`, `Illuminate\Cookie` (`EncryptCookies`),
   `Illuminate\Session`. MAC bypass, padding or key-handling flaws, session fixation, and any path that decrypts
   or unserializes client data without the application asking for it.
2. Authentication and authorization: `Illuminate\Auth` (guards, password reset, remember-me, `Gate` and
   policies), password confirmation, and email verification.
3. Routing and URLs: `Illuminate\Routing` (signed URLs, route model binding, middleware ordering, redirects),
   CSRF and maintenance mode bypass in `Illuminate\Foundation\Http\Middleware`, and `TrustProxies`, `TrustHosts`,
   and `ValidatePostSize` in `Illuminate\Http\Middleware`.
4. Validation bypass in `Illuminate\Validation` rules that applications rely on for security, such as `email`,
   `url`, `file`, `mimes`, `image`, `exists`, `unique`, and nested array (`*`) rules.
5. SQL injection through query builder and Eloquent methods that bind values: `where`, `whereIn`, `whereJsonContains`,
   JSON path (`->`) selectors, `orderBy` direction, `upsert`, and the grammars in `Illuminate\Database\Query\Grammars`.
6. XSS through Blade's escaped output (`{{ }}`, `@json`, component attributes), `Illuminate\Support\Js`,
   `HtmlString` misuse inside the framework, and the rendered Markdown mail and notification templates.
7. Path traversal and file handling in `Illuminate\Filesystem`, `Illuminate\Http\UploadedFile`, and the local
   disk's temporary and signed URLs.
8. Request parsing in `Illuminate\Http\Request`: host, scheme, and IP detection behind proxies, and content
   negotiation.

Lower priority or out of scope:

- `vendor/`. Symfony, Flysystem, Monolog, and other dependencies have their own security processes. Report an issue
  here only when Laravel uses a dependency in a way that creates the vulnerability.
- `tests/`, `types/`, `bin/`, and `src/Illuminate/Testing` (test-only helpers that do not run in production).
- `src/Illuminate/Foundation/resources/exceptions` and other output that only appears when `APP_DEBUG=true`.
- `Illuminate\Console` and Artisan commands, because only operators can run them.

## How to exercise it

- Dependencies are installed in `/src/vendor`. Run tests with `vendor/bin/phpunit --no-coverage <path>`, for
  example `vendor/bin/phpunit --no-coverage tests/Encryption`.
- `tests/Integration` boots a full application with Orchestra Testbench. A test there that sends a request with
  `$this->get()` or `$this->post()` is the preferred reproducer for request-handling issues.
- Database tests use in-memory SQLite. MySQL, PostgreSQL, and SQL Server are not available in the container.
- Run `laravel-services-start` before tests that need Redis or Memcached.
- Run one test directory at a time. Some suites share global state, so running several directories in one PHPUnit
  process can cause errors that do not happen when each runs alone.
- Tests that need network access, such as DNS checks in `ValidationEmailRuleTest::testValidateMxRecord`, fail in
  the offline container. These failures are expected.

## How you rate severity

- Critical: unauthenticated remote code execution, authentication bypass, or recovery of `APP_KEY` in a default
  application.
- High: SQL injection through a value-binding API, XSS through escaped Blade output, forging signed URLs, encrypted
  cookies, or CSRF tokens, session fixation, or a validation rule bypass that defeats the rule's documented purpose.
- Medium: issues that need a non-default but common configuration, open redirects in framework helpers, cache
  poisoning through the `Host` header, and a denial of service from a single small request (for example, ReDoS or
  unbounded memory use).
- Low: hardening issues with no demonstrated impact.

## How reports and patches should look

- Name the affected class and method, the conditions an application must meet to be affected, and a self-contained
  PHPUnit test that fails before the fix and passes after it.
- Write patches as a `git diff` against `/src`. Follow the existing code style (`pint.json`) and keep the patch
  as small as possible.

## Anything to leave alone

These behaviors are documented as the application developer's responsibility:

- Raw SQL methods (`DB::raw`, `whereRaw`, `selectRaw`, `orderByRaw`, `DB::statement`, and similar).
- User-controlled column or table names, including `orderBy` columns. The documentation tells developers never to
  pass user input as a column name.
- Unescaped Blade output (`{!! !!}`) and `HtmlString` values that the application creates.
- Mass assignment when a model sets `$guarded = []` or lists sensitive attributes in `$fillable`.
- Anything that needs `APP_KEY`, including remote code execution through `decrypt()` with a known key.
- Information exposed when `APP_DEBUG=true`.
- Missing security headers, framework version disclosure, and username enumeration through timing.
- Volumetric denial of service.
