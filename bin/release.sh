#!/usr/bin/env bash

set -e

# Make sure the release tag is provided.
if (( "$#" != 1 ))
then
    echo "Tag has to be provided."

    exit 1
fi

# Make sure the working directory is clear.
if [ -z "$(git status --porcelain)" ]; then
else
    echo "Your working directory is dirty. Did you forget to commit your changes?"

    exit 1
fi

RELEASE_BRANCH="master"
CURRENT_BRANCH=$(git branch --show-current)
VERSION=$1

# Make sure current branch and release branch match
if (( $RELEASE_BRANCH != $CURRENT_BRANCH ))
then
    echo "Release branch ($RELEASE_BRANCH) does not matches the current active branch ($CURRENT_BRANCH)."

    exit 1
fi

# Always prepend with "v"
if [[ $VERSION != v*  ]]
then
    VERSION="v$VERSION"
fi

# Tag Framework
git pull
git tag $VERSION
git push origin --tags

# Tag Components
for REMOTE in auth broadcasting bus cache config console container contracts cookie database encryption events filesystem hashing http log mail notifications pagination pipeline queue redis routing session support testing translation validation view
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
