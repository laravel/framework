#!/usr/bin/env bash

set -e
set -x

CURRENT_BRANCH="11.x"

function split()
{
    SHA1=`./bin/splitsh-lite --prefix=$1`
    git push $2 "$SHA1:refs/heads/$CURRENT_BRANCH" -f
}

function remote()
{
    git remote add $1 $2 || true
}

git pull origin $CURRENT_BRANCH

remote auth git@github.com:illuminate/auth.git
remote broadcasting git@github.com:illuminate/broadcasting.git
remote bus git@github.com:illuminate/bus.git
remote cache git@github.com:illuminate/cache.git
remote collections git@github.com:illuminate/collections.git
remote conditionable git@github.com:illuminate/conditionable.git
remote config git@github.com:illuminate/config.git
remote console git@github.com:illuminate/console.git
remote container git@github.com:illuminate/container.git
remote contracts git@github.com:illuminate/contracts.git
remote cookie git@github.com:illuminate/cookie.git
remote database git@github.com:illuminate/database.git
remote encryption git@github.com:illuminate/encryption.git
remote events git@github.com:illuminate/events.git
remote filesystem git@github.com:illuminate/filesystem.git
remote hashing git@github.com:illuminate/hashing.git
remote http git@github.com:illuminate/http.git
remote log git@github.com:illuminate/log.git
remote macroable git@github.com:illuminate/macroable.git
remote mail git@github.com:illuminate/mail.git
remote notifications git@github.com:illuminate/notifications.git
remote pagination git@github.com:illuminate/pagination.git
remote pipeline git@github.com:illuminate/pipeline.git
remote process git@github.com:illuminate/process.git
remote queue git@github.com:illuminate/queue.git
remote redis git@github.com:illuminate/redis.git
remote routing git@github.com:illuminate/routing.git
remote session git@github.com:illuminate/session.git
remote support git@github.com:illuminate/support.git
remote testing git@github.com:illuminate/testing.git
remote translation git@github.com:illuminate/translation.git
remote validation git@github.com:illuminate/validation.git
remote view git@github.com:illuminate/view.git

split 'src/Illuminate/Auth' auth
split 'src/Illuminate/Broadcasting' broadcasting
split 'src/Illuminate/Bus' bus
split 'src/Illuminate/Cache' cache
split 'src/Illuminate/Collections' collections
split 'src/Illuminate/Conditionable' conditionable
split 'src/Illuminate/Config' config
split 'src/Illuminate/Console' console
split 'src/Illuminate/Container' container
split 'src/Illuminate/Contracts' contracts
split 'src/Illuminate/Cookie' cookie
split 'src/Illuminate/Database' database
split 'src/Illuminate/Encryption' encryption
split 'src/Illuminate/Events' events
split 'src/Illuminate/Filesystem' filesystem
split 'src/Illuminate/Hashing' hashing
split 'src/Illuminate/Http' http
split 'src/Illuminate/Log' log
split 'src/Illuminate/Macroable' macroable
split 'src/Illuminate/Mail' mail
split 'src/Illuminate/Notifications' notifications
split 'src/Illuminate/Pagination' pagination
split 'src/Illuminate/Pipeline' pipeline
split 'src/Illuminate/Process' process
split 'src/Illuminate/Queue' queue
split 'src/Illuminate/Redis' redis
split 'src/Illuminate/Routing' routing
split 'src/Illuminate/Session' session
split 'src/Illuminate/Support' support
split 'src/Illuminate/Testing' testing
split 'src/Illuminate/Translation' translation
split 'src/Illuminate/Validation' validation
split 'src/Illuminate/View' view
