<?php

/*
 * Name:  voterdb_constants_bounce.php  V3.1 8/18/17
 * This include file contains constants used to interface with the bounce module.
 */

/**
 * Bounce table names
 */
define('DB_BOUNCE_BLOCK_TBL', 'bounce_blocked');
define('DB_BOUNCE_NON_DELIVERY_TBL', 'bounce_non_delivery_report');
define('DB_BOUNCE_CODE_SCORE_TBL', 'bounce_code_score');
define('DB_BOUNCE_REPORT_NOTIFY_TBL', 'bounce_notified');

/**
 * bounce block tbl
 */
// column names
define('BB_BLOCKED_ID', 'blocked_id');
define('BB_MAIL', 'mail');
define('BB_CREATED', 'created');

/**
 * bounce non-delivery report tbl
 */
// column names
define('BN_REPORT_ID', 'report_id');
define('BN_MAIL', 'mail');
define('BN_CODE', 'code');
define('BN_ANALYST', 'analyst');
define('BN_REPORT', 'report');
define('BN_STATUS', 'status');
define('BN_CREATED', 'created');

/**
 * bounce code score tbl
 */
// column names
define('BC_CODE', 'code');
define('BC_TYPE', 'type');
define('BC_SCORE', 'score');
define('BC_DESCRIPTION', 'description');

/**
 * bounce notify tbl
 */
// column names
define('BA_REPORT_ID', 'report_id');
define('BA_BLOCK_ID', 'blocked_id');
define('BA_NOTIFIED', 'notified');
define('BA_DATE', 'date');
define('BA_COUNTY', 'county');
define('BA_NLFNAME', 'NLfname');
define('BA_NLLNAME', 'NLlname');
define('BA_NLEMAIL', 'NLemail');
define('BA_SFNAME', 'sfname');
define('BA_SLNAME', 'slname');
define('BA_SEMAIL', 'semail');
define('BA_CODE', 'code');
define('BA_DESCRIPTION', 'description');

define('BQ_NOTIFIED', 'Y');
define('BQ_NOT_NOTIFIED', 'N');