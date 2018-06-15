<?php
/*
 * Name:  voterdb_constants_log_tbl.php    V3.0   2/8/17
 * This include file contains constants used in accessing the database.
 */

/*
 * The log file records the attempts of NL users to get their turf.  If they
 * are successful, the name and date are recorded.  If not, the failing
 * password is also recorded, just in case the password is too complicated
 * for most NLs.
 */
/**
 * VoterDB table names
 */
define('DB_TRACK_TBL', 'track');

// column names
define('TR_INDX', 'Indx');
define('TR_COUNTY', 'County');
define('TR_TYPE', 'Type');
define('TR_DATE', 'Date');
define('TR_USER', 'User');
define('TR_IP', 'IP');
define('TR_STATUS', 'STATUS');
define('TR_INFO', 'Info');