#!/usr/bin/env bash
#
# Run the PHPUnit suite against a wp-env WordPress install.
#
# Dev dependencies are installed inside the container, so vendor/ never appears
# in the repo. Docker is the only prerequisite.
#
# Usage: bin/test.sh [extra phpunit args]

set -euo pipefail

cd "$( dirname "$0" )/.."

SLUG="$( basename "$PWD" )"
ENV_CWD="wp-content/plugins/${SLUG}"

# Coverage needs Xdebug, which slows every request down, so it is opt-in:
#   COVERAGE=1 bin/test.sh --coverage-text
XDEBUG_FLAG=""
if [ "${COVERAGE:-}" = "1" ]; then
	XDEBUG_FLAG="--xdebug=coverage"
fi

echo "--- starting wp-env"
# shellcheck disable=SC2086
npx --yes @wordpress/env start $XDEBUG_FLAG

echo "--- installing dev dependencies in the container"
npx --yes @wordpress/env run tests-cli --env-cwd="$ENV_CWD" composer install --no-interaction

echo "--- running phpunit"
npx --yes @wordpress/env run tests-cli --env-cwd="$ENV_CWD" ./vendor/bin/phpunit "$@"
