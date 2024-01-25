#!/usr/bin/env bash

set -e

RELEASE_BRANCH="10.x"
VERSION=$1

# Tag Framework
git tag $VERSION
git push origin --tags

# Tag Components
for REMOTE in auth broadcasting bus cache collections conditionable config console container contracts cookie database encryption events filesystem hashing http log macroable mail notifications pagination pipeline process queue redis routing session support testing translation validation view
do
    echo ""
    echo ""
    echo "Releasing $REMOTE";

    TMP_DIR="/tmp/laravel-split"
    REMOTE_URL="git@github.com:illuminate/$REMOTE.git"

    rm -rf $TMP_DIR;
    mkdir $TMP_DIR;

    (
        cd $TMP_DIR;

        git clone $REMOTE_URL .
        git checkout "$RELEASE_BRANCH";

        git tag $VERSION
        git push origin --tags
    )
done
