#!/bin/sh
# commitlint.sh - Enforce Conventional Commits in commit messages
set -e

# Set up cleanup trap for any temporary files
trap 'rm -f /tmp/commitlint-* 2>/dev/null || true' EXIT

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "${YELLOW}🔍 Validating commit message format...${NC}"

# Check if this is a merge commit
if [ -f .git/MERGE_HEAD ]; then
    echo "${GREEN}✅ Merge commit detected, skipping validation${NC}"
    exit 0
fi

# Read the commit message
COMMIT_MSG_FILE="$1"
if [ -z "$COMMIT_MSG_FILE" ]; then
    echo "Usage: $0 <commit-msg-file>"
    exit 1
fi

if [ ! -f "$COMMIT_MSG_FILE" ]; then
    echo "${RED}❌ Commit message file not found: $COMMIT_MSG_FILE${NC}"
    exit 1
fi

COMMIT_MSG=$(head -n1 "$COMMIT_MSG_FILE")

# Skip empty commits
if [ -z "$COMMIT_MSG" ] || [ "$COMMIT_MSG" = "" ]; then
    echo "${RED}❌ Empty commit message${NC}"
    exit 1
fi

# Skip special commits that don't need conventional format validation
# Also skip versioned commits like 10.x or [10.x] (to match workflow logic)
if printf "%s" "$COMMIT_MSG" | grep -Eq "^(Merge|WIP|Revert|[0-9]+\\.x|\\[[0-9]+\\.x\\])"; then
    echo "${GREEN}✅ Special commit type detected, skipping conventional format validation${NC}"
    exit 0
fi

# Conventional Commits regex (type[optional scope]: subject)
CONVENTIONAL_REGEX='^(build|chore|ci|docs|feat|fix|perf|refactor|revert|style|test)(\([a-zA-Z0-9_-]+\))?: .{1,}'

# Log the commit message for debugging (safely quoted)
printf "${YELLOW}Checking message: %s${NC}\n" "$COMMIT_MSG"

if printf "%s" "$COMMIT_MSG" | grep -Eq "$CONVENTIONAL_REGEX"; then
    echo "${GREEN}✅ Commit message format is valid${NC}"
    exit 0
else
    echo ""
    echo "${RED}❌ Commit message does not follow Conventional Commits format!${NC}"
    echo ""
    echo "${YELLOW}Expected format:${NC}"
    echo "  ${GREEN}type(scope): description${NC}"
    echo ""
    echo "${YELLOW}Valid types:${NC}"
    echo "  feat:     A new feature"
    echo "  fix:      A bug fix"
    echo "  docs:     Documentation only changes"
    echo "  style:    Changes that do not affect the meaning of the code"
    echo "  refactor: A code change that neither fixes a bug nor adds a feature"
    echo "  test:     Adding missing tests or correcting existing tests"
    echo "  chore:    Changes to the build process or auxiliary tools"
    echo "  perf:     A code change that improves performance"
    echo "  ci:       Changes to CI configuration files and scripts"
    echo "  build:    Changes that affect the build system"
    echo "  revert:   Reverts a previous commit"
    echo ""
    echo "${YELLOW}Examples:${NC}"
    echo "  ${GREEN}feat(auth): add user authentication${NC}"
    echo "  ${GREEN}fix(api): resolve validation error in user endpoint${NC}"
    echo "  ${GREEN}docs: update API documentation${NC}"
    echo ""
    echo "See: https://laravel.com/docs/12.x/contributions#semantic-commits and https://www.conventionalcommits.org/en/v1.0.0/"
    exit 1
fi
