<?php
/**
 * Sample [name, value] pairs for settings CRUD round-trip tests. Tests prefix
 * these with a unique suffix (see IntegrationTestCase::uniqueName) so they never
 * collide with real settings or each other, and so they get rolled back by the
 * per-test transaction.
 */
return [
    ['test_string', 'hello-world'],
    ['test_numeric', '42'],
    ['test_with_spaces', 'a b c d e'],
];
