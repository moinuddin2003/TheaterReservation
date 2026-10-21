<?php

return [
    /*
    | Where the staff screens (Today, Scan, Roster, Lookup) read their data from.
    |   mock     - the staff-mock*.php config files (default)
    |   database - the real tables, through the models
    | Switch by setting STAFF_DATA_SOURCE=database in .env.
    */
    'data_source' => env('STAFF_DATA_SOURCE', 'mock'),
];
