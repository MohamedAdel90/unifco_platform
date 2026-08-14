#!/usr/bin/env sh
set -eu
BASE_URL="${BASE_URL:-http://localhost:8080}"
echo "Checking liveness..."
curl -fsS "$BASE_URL/health/live" >/dev/null
echo "Checking readiness..."
curl -fsS "$BASE_URL/health/ready" >/dev/null
echo "UNIFCO staging health checks passed."
