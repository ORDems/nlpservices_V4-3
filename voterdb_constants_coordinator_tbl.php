<?php
/*
 * Name:  voterdb_constants_coordinator_tbl.php  V3.0  2/8/17
 * This include file contains constants used in accessing the date table.
 */
/**
 * VoterDB table name.
 */
define('DB_COORDINATOR_TBL', 'coordinator');
define('DB_PCT_COORDINATOR_TBL', 'pct_coordinator');
/**
 * nlp_date_br_tbl
 */
// column names
define('CR_CINDEX', 'CIndex');
define('CR_COUNTY', 'County');
define('CR_FIRSTNAME', 'FirstName');
define('CR_LASTNAME', 'LastName');
define('CR_EMAIL', 'Email');
define('CR_PHONE', 'Phone');
define('CR_SCOPE', 'Scope');
define('CR_HD', 'HD');
define('CR_PARTIAL', 'Partial');

// Values for scope.
define('CS_COUNTY', 'County');
define('CS_HD', 'HD');
define('CS_PCT', 'Pct');

// column names
define('PC_CINDEX', 'CIndex');
define('PC_PCT', 'Pct');