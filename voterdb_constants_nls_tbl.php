<?php

/*
 * Name:  voterdb_constants_nls_tbl.php  V4.1 6/13/18
 * This include file contains constants used in accessing the database for
 * information about an NLS.
 */

/**
 * VoterDB table names
 */
define('DB_NLS_TBL', 'nls');
define('DB_NLS_GRP_TBL', 'nls_grp');
define('DB_NLSSTATUS_TBL', 'nls_status');
define('DB_NLSSTATUS_HISTORY_TBL', 'nls_status_history');

/** * * * * * * * * * * * * * * * * * * * * * * * * * *
 * nls table
 *
 * These constants are the names of the columns in the MySQL table for
 * the NLs.  The indexes are used when one uses a SELECT * to retrieve
 * a record.
 */

// Column name
define('NH_MCID', 'MCID');
define('NH_FNAME', 'FirstName');
define('NH_LNAME', 'LastName');
define('NH_NICKNAME', 'Nickname');
define('NH_COUNTY', 'County');
define('NH_HD', 'HD');
define('NH_PCT', 'Pct');
define('NH_ADDR', 'Address');
define('NH_EMAIL', 'Email');
define('NH_PHONE', 'Phone');
define('NH_HOMEPHONE', 'HomePhone');
define('NH_CELLPHONE', 'CellPhone');

/**
 * Constants needed to access the NLS Group Table
 */

// Column name
define('NG_COUNTY', 'County');
define('NG_MCID', 'MCID');

/**
 * nlsstatus tbl
 */

// column names
define('NN_MCID', 'MCID');
define('NN_COUNTY', 'County');
define('NN_LOGINDATE', 'Login_Date');
define('NN_CONTACT', 'Contact');
define('NN_NLSIGNUP', 'NLSignup');
define('NN_TURFCUT', 'Turfcut');
define('NN_TURFDELIVERED', 'TurfDelivered');
define('NN_RESULTSREPORTED', 'ResultsReported');
define('NN_ASKED', 'Asked');
define('NN_NOTES', 'Notes');

define("NN_NOTES_SIZE",'81');  // Length of the notes field in database.
define("NN_NOTES_LENGTH",'75');  // Notes max length for users.
define("NN_NOTES_WRAP",'25');  // Notes max length for single line.

// Table column name list.
define('NN_NLSSTATUS_LIST', serialize (array(NN_MCID,NN_COUNTY,
  NN_LOGINDATE,NN_CONTACT,NN_NLSIGNUP,NN_TURFCUT,NN_TURFDELIVERED,
  NN_RESULTSREPORTED,NN_ASKED,NN_NOTES)));

// Data
define('CT_CANVASS', '0');
define('CT_MINIVAN', '1');
define('CT_PHONE', '2');
define('CT_MAIL', '3');
define('CV_CANVASS', 'canvass');
define('CV_MINIVAN', 'minivan');
define('CV_PHONE', 'phone');
define('CV_MAIL', 'mail');
define('CT_CONTACT_ARRAY', serialize(array(CV_CANVASS, CV_MINIVAN, CV_PHONE, CV_MAIL)));
define('CT_CONTACT_TYPE',"ENUM('".CV_CANVASS."','".CV_MINIVAN."','".CV_PHONE."','".CV_MAIL."')");

// Asked.
define('AT_DEFAULT', '-');  // Not yet asked.
define('AT_ASKED', 'Asked');  // Asked and agreed.
define('AT_YES', 'Yes');  // Asked and agreed.
define('NS_NO_V', 'No');  // Asked and refused for this cycle.
define('NS_QUIT_V', 'Quit');  // Asked and quit as NL.
define('AT_ASKED_ARRAY', serialize(array(AT_DEFAULT ,AT_ASKED, AT_YES, NS_NO_V, NS_QUIT_V)));
define('AT_ASKED_TYPE',"ENUM('".AT_ASKED."','".AT_YES."','".NS_NO_V."','".NS_QUIT_V."')");
define('NS_YES_I','2');

/**
 * nls status history tbl
 */

// column names
define('NY_HINDEX', 'HIndex');
define('NY_DATE', 'Date');
define('NY_MCID', 'MCID');
define('NY_COUNTY', 'County');
define('NY_CYCLE', 'Cycle');
define('NY_STATUS', 'Status');
define('NY_NLFNAME', 'NLfname');
define('NY_NLLNAME', 'NLlname');

// Permitted status.
define('NY_ASKED', 'Asked');
define('NY_DECLINED', 'Declined');
define('NY_SIGNEDUP', 'Signed up');
define('NY_TURFCHECKEDIN', 'Checked in turf');
define('NY_DELIVEREDTURF', 'Delivered turf');
define('NY_REPORTEDRESULTS', 'Reported results');
define('NY_QUIT', 'Quit');
define('NY_NL_HISTORY_ARRAY', serialize(array(NY_ASKED, NY_DECLINED, NY_SIGNEDUP, NY_TURFCHECKEDIN, NY_DELIVEREDTURF,NY_REPORTEDRESULTS, NY_QUIT)));
define('NY_NL_HISTORY_TYPE',"ENUM('".NY_ASKED."','".NY_DECLINED."','".NY_SIGNEDUP."','".NY_TURFCHECKEDIN."','".NY_DELIVEREDTURF."','".NY_REPORTEDRESULTS."','".NY_QUIT."')");