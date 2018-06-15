<?php
/*
 * Name:  voterdb_constants_rr_tbl.php  V4.0 4/5/18
 * This include file contains constants used in accessing the database.
 */
/**
 * VoterDB NL results table.
 */
define('DB_NLPRESULTS_TBL', 'results');
define('RA_F2F','Face-to-Face');
define('RA_DECEASED','Deceased');
define('RA_HOSTILE','Hostile');
define('RA_MOVED','Moved');

define('DE_RESULT_ARRAY', serialize(array(
    'Select Result',RA_F2F, 'Left Lit', 'Post Card',
    'Phone Contact', 'Voice Mail', 'Disconnected', 'Not at this Number',
    RA_DECEASED, RA_HOSTILE, 'Inaccessible', RA_MOVED, 'Refused Contact')));
define ('RE_F2F','1');
define ('RE_DECEASED','8');
define ('RE_HOSTILE','9');
define ('RE_MOVED','11');

define('TA_Contact','Contact');
define('TA_SURVEY','Survey');
define('DE_TYPE_ARRAY', serialize(array(TA_Contact,'Comment', 'ID',TA_SURVEY)));
define ('RT_CONTACT','0');
define ('RT_COMMENT','1');
define ('RT_ID','2');
define ('RT_SURVEY','3');

/**
 * nlpresults tbl
 */
// Column names.
define('NC_RINDEX', 'Rindex');
define('NC_RECORDED', 'Recorded');
define('NC_CYCLE', 'Cycle');
define('NC_COUNTY', 'County');
define('NC_ACTIVE', 'Active');
define('NC_MCID', 'MCID');
define('NC_VANID', 'VANID');
define('NC_CDATE', 'Cdate');
define('NC_TYPE', 'Type');
define('NC_VALUE', 'Value');
define('NC_TEXT', 'Text');

define ('NC_COMMENT_MAX','190');

// Array of column names we will look for in the upload file.
define('NU_HEADER_ARRAY', serialize (array(NC_RECORDED,NC_CYCLE,NC_COUNTY,NC_ACTIVE,
  NC_VANID,NC_MCID,NC_CDATE, NC_TYPE,NC_VALUE,NC_TEXT)));

// Array indicating which column names must be present
define('NU_REQUIRED_ARRAY', serialize(array(1,1,1,1,1,1,1,1,1,1)));

define('NU_MESSAGE_ARRAY', serialize(array('Recorded','Cycle','County','Active','VANID',
  'MCID','Cdate', 'Type', 'Value', 'Text',)));

// Indexes into the array above
define('NU_RECORDED', '0');
define('NU_CYCLE', '1');
define('NU_COUNTY', '2');
define('NU_ACTIVE', '3');
define('NU_VANID', '4');
define('NU_MCID', '5');
define('NU_CDATE', '6');
define('NU_TYPE', '7');
define('NU_VALUE', '8');
define('NU_TEXT', '9');