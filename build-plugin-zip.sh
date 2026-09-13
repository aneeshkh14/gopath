#!/usr/bin/env bash
#
# Build an installable WordPress plugin ZIP.
#
# WordPress identifies a plugin by its FOLDER NAME. A ZIP downloaded straight from
# GitHub unpacks to a branch-named folder (e.g. "gopath-claude-my-branch"), which
# WordPress treats as a *second, different* plugin — activating it alongside the
# original then fatals with "Cannot redeclare class GEP_Loader".
#
# This script produces gopath-exam-portal.zip containing a single top-level
# "gopath-exam-portal/" folder, so WordPress recognises it as the same plugin and
# offers "Replace current with uploaded" on the Plugins → Add New → Upload screen.
#
# Usage: ./build-plugin-zip.sh
set -euo pipefail

SLUG="gopath-exam-portal"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BUILD="$ROOT/build"
OUT="$ROOT/$SLUG.zip"

REF="${1:-HEAD}"

rm -rf "$BUILD" "$OUT"
mkdir -p "$BUILD/$SLUG"

# Export the committed tree (git already excludes .git/ and ignored files such as
# fatal_error.log), then drop the repo-only files that should not ship.
git -C "$ROOT" archive --format=tar "$REF" | tar -x -C "$BUILD/$SLUG"
rm -f "$BUILD/$SLUG/build-plugin-zip.sh" "$BUILD/$SLUG/.gitignore" "$BUILD/$SLUG/package.json" "$BUILD/$SLUG/package-lock.json"
rm -rf "$BUILD/$SLUG/tests" "$BUILD/$SLUG/.github" "$BUILD/$SLUG/docs"

( cd "$BUILD" && zip -qr "$OUT" "$SLUG" )
rm -rf "$BUILD"

echo "Built: $OUT"
unzip -l "$OUT" | sed -n '1,12p'
