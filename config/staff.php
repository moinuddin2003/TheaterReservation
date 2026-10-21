<?php

return [
    /*
    | Where the staff screens (Today, Scan, Roster, Lookup) read their data from.
    |   database - the real tables, through the models (default, nothing to set in .env)
    |   mock     - the staff-mock*.php config files (used by the mock-based tests)
    */
    'data_source' => 'database',
];
