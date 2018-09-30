#!/usr/bin/env bash

set -e
set -x

CURRENT_BRANCH="5.7"

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

for REMOTE in \
    Broadcasting \
    Bus \
    Cache \
    Config \
    Console \
    Container \
    Contracts \
    Cookie \
    Database \
    Encryption \
    Events \
    Filesystem \
    Hashing \
    Http \
    Log \
    Mail \
    Notifications \
    Pagination \
    Pipeline \
    Queue \
    Redis \
    Routing \
    Session \
    Support \
    Translation \
    Validation \
    View
do
    echo "Splitting $REMOTE"

    LOWERCASE_REMOTE=$(echo $REMOTE | tr '[:upper:]' '[:lower:]')

    remote $LOWERCASE_REMOTE git@github.com:illuminate/$LOWERCASE_REMOTE.git
    split "src/Illuminate/$REMOTE" $LOWERCASE_REMOTE

    echo "$REMOTE: done"
done
