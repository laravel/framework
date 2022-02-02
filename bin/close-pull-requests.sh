#!/usr/bin/env bash

set -e

COMMENT='Thank you for your pull request.
However, you have submitted this PR on the Illuminate organization which is a read-only sub split of \`laravel/framework\`.
Please submit your PR on the https://github.com/laravel/framework repository.

Thanks!'

for REPO in $(gh repo list illuminate --public --topic="read-only" --json "nameWithOwner" --jq '.[].nameWithOwner'); do

    for PR in $(gh pr list -R $REPO --json "number" --jq '.[].number'); do

        # Add a comment
        gh pr comment $PR -R $REPO -b $COMMENT

        # Close the pull request
        gh pr close $PR -R $REPO

        # Lock the pull request too?
        # gh api -X PUT repos/$REPO/issues/$PR/lock

    done

done
