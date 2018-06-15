<?php
/*
 * Name:  voterdb_constants_nu.php  V3.0  1/12/17
 * This include file contains constants used in accessing the database.
 */


// Array of column names we will look for in the upload file.
define('NU_HEADER_ARRAY', serialize(array(NC_CYCLE,NC_COUNTY,NC_ACTIVE,
  NC_VANID,NC_MCID,NC_CDATE, NC_TYPE,NC_VALUE,NC_TEXT)));

// Array indicating which column names must be present
define('NU_REQUIRED_ARRAY', serialize(array(1,1,1,1,1,1,1,1,1)));

define('NU_MESSAGE_ARRAY', serialize(array('Cycle','Group Name','Active','VANID',
  'MCID','Cdate', 'Type', 'Value', 'Text',)));

// Indexes into the array above
define('NU_CYCLE', '0');
define('NU_COUNTY', '1');
define('NU_ACTIVE', '2');
define('NU_VANID', '3');
define('NU_MCID', '4');
define('NU_CDATE', '5');
define('NU_TYPE', '6');
define('NU_VALUE', '7');
define('NU_TEXT', '8');
