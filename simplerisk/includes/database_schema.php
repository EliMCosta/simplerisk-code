<?php

/* This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/. */

/**
 * Raw GitHub URL for a schema file in the configured database repo.
 */
function simplerisk_database_schema_url(string $filename): string
{
    $repo = defined('DB_SCHEMA_REPO') ? DB_SCHEMA_REPO : 'EliMCosta/simplerisk-database';
    $branch = defined('DB_BRANCH') ? DB_BRANCH : 'master';

    return "https://raw.githubusercontent.com/{$repo}/{$branch}/{$filename}";
}
