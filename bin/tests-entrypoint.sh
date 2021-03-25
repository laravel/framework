#!/usr/bin/env bash

set -e

php -r "\$tries = 0; while (true) { try { \$tries++; if (\$tries > 30) { throw new RuntimeException('MySQL never became available'); } sleep(1); new PDO('mysql:host=${DB_HOST};dbname=forge', 'root', '', [PDO::ATTR_TIMEOUT => 3]); break; } catch (PDOException \$e) {} }"

exec vendor/bin/phpunit $@