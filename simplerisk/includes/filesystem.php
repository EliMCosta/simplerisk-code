<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

/********************************************
 * FUNCTION: IS VERSION CONTROL METADATA    *
 ********************************************/
/**
 * Whether $path is a version-control metadata tree (a .git directory) or an
 * object inside one.
 *
 * Vendored checkouts ship a read-only .git tree whose objects must never be
 * web-writable, so scanning them for writability only ever produces false
 * positives — the directory-permission health checks use this to skip them.
 *
 * Two clauses are required because the RecursiveDirectoryIterator yields the
 * .git directory itself (a path ending in "/.git", matched by basename) and
 * each object within it (a path containing "/.git/", matched by strpos):
 * neither clause alone covers both.
 *
 * This is intentionally a pure function with no config/DB dependency, so it is
 * safe to load in the pre-install bootstrap, where functions.php is not yet
 * available (install.php requires this file directly; healthcheck.php requires
 * it for the post-install app path).
 */
function is_version_control_metadata($path)
{
    return basename($path) === ".git" || strpos($path, "/.git/") !== false;
}
