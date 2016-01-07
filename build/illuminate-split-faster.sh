#!/bin/bash

split()
{
    SUBDIR=$1
    SPLIT=$2
    HEADS=$3

    mkdir -p $SUBDIR;

    pushd $SUBDIR;

    for HEAD in $HEADS
    do

        mkdir -p $HEAD

        pushd $HEAD

        git subsplit init git@github.com:laravel/framework.git
        git subsplit update

        time git subsplit publish --heads="$HEAD" --no-tags "$SPLIT"

        popd

    done

    popd
}

split auth         src/Illuminate/Auth:git@github.com:illuminate/auth.git                 "master 5.2 5.1"
split broadcasting src/Illuminate/Broadcasting:git@github.com:illuminate/broadcasting.git "master 5.2 5.1"
split bus          src/Illuminate/Bus:git@github.com:illuminate/bus.git                   "master 5.2 5.1"
split cache        src/Illuminate/Cache:git@github.com:illuminate/cache.git               "master 5.2 5.1"
split config       src/Illuminate/Config:git@github.com:illuminate/config.git             "master 5.2 5.1"
split console      src/Illuminate/Console:git@github.com:illuminate/console.git           "master 5.2 5.1"
split container    src/Illuminate/Container:git@github.com:illuminate/container.git       "master 5.2 5.1"
split contracts    src/Illuminate/Contracts:git@github.com:illuminate/contracts.git       "master 5.2 5.1"
split cookie       src/Illuminate/Cookie:git@github.com:illuminate/cookie.git             "master 5.2 5.1"
split database     src/Illuminate/Database:git@github.com:illuminate/database.git         "master 5.2 5.1"
split encryption   src/Illuminate/Encryption:git@github.com:illuminate/encryption.git     "master 5.2 5.1"
split events       src/Illuminate/Events:git@github.com:illuminate/events.git             "master 5.2 5.1"
split filesystem   src/Illuminate/Filesystem:git@github.com:illuminate/filesystem.git     "master 5.2 5.1"
split hashing      src/Illuminate/Hashing:git@github.com:illuminate/hashing.git           "master 5.2 5.1"
split http         src/Illuminate/Http:git@github.com:illuminate/http.git                 "master 5.2 5.1"
split log          src/Illuminate/Log:git@github.com:illuminate/log.git                   "master 5.2 5.1"
split mail         src/Illuminate/Mail:git@github.com:illuminate/mail.git                 "master 5.2 5.1"
split pagination   src/Illuminate/Pagination:git@github.com:illuminate/pagination.git     "master 5.2 5.1"
split pipeline     src/Illuminate/Pipeline:git@github.com:illuminate/pipeline.git         "master 5.2 5.1"
split queue        src/Illuminate/Queue:git@github.com:illuminate/queue.git               "master 5.2 5.1"
split redis        src/Illuminate/Redis:git@github.com:illuminate/redis.git               "master 5.2 5.1"
split routing      src/Illuminate/Routing:git@github.com:illuminate/routing.git           "master 5.2 5.1"
split session      src/Illuminate/Session:git@github.com:illuminate/session.git           "master 5.2 5.1"
split support      src/Illuminate/Support:git@github.com:illuminate/support.git           "master 5.2 5.1"
split translation  src/Illuminate/Translation:git@github.com:illuminate/translation.git   "master 5.2 5.1"
split validation   src/Illuminate/Validation:git@github.com:illuminate/validation.git     "master 5.2 5.1"
split view         src/Illuminate/View:git@github.com:illuminate/view.git                 "master 5.2 5.1"
