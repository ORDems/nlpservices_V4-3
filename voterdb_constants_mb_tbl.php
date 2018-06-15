<?php
/*
 * Name:  voterdb_constants_mb_tbl.php  V4.0 3/29/18
 * This include file contains constants used in accessing the matchback table.
 */

/**
 * VoterDB table name
 */
define('DB_MATCHBACK_TBL', 'matchback');

/** * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Match back upload contants
 */
// Column names required for an export of the VoterFile of ballots received.
define('MB_VANID', 'VANID');
define('MB_BALLOT_RECEIVED', 'BallotReceived');
define('MB_PARTY', 'Party');
define('MB_COUNTY', 'CountyName');
define('MB_VANID_ALT','Voter File VANID');

// Array of column names we will look for in the export
define('MB_HEADER_ARRAY', serialize(array(
    MB_VANID, MB_BALLOT_RECEIVED, MB_PARTY, MB_COUNTY, MB_VANID_ALT
)));
// Array indicating which column names must be present
define('MB_REQUIRED_ARRAY', serialize(array(
    0, 1, 0, 1, 0
)));
define('MB_MESSAGE_ARRAY', serialize(array(
    'VanID', 'Ballot Received', 'Party', 'County', 'VANID'
)));

// Indexes into the array above
define('BR_VANID', '0');
define('BR_BALLOT_RECEIVED', '1');
define('BR_PARTY', '2');
define('BR_COUNTY', '3');
define('BR_VANID_ALT','4');

/**
 * matchback tbl
 */
// column names
define('MT_VANID', 'VANID');
define('MT_DATE_INDEX', 'DateIndex');
define('MT_COUNTY', 'County');
