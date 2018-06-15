<?php
/*
 * Name:  voterdb_constants_voter_tbl.php     V4.0  4/5/18
 * This include file contains constants used in accessing the database.
 */

/**
 * VoterDB table names
 */
define('DB_NLPVOTER_TBL', 'voter');
define('DB_NLPVOTER_GRP_TBL', 'voter_grp');
define('DB_NLPVOTER_STATUS_TBL', 'voter_status');

/**
 * nlp_voter table
 * The voter information is contained in a MySQL database.  These constants
 * define the column for each field when a record is exported with SELECT *
 */

// Column names
define('VN_VANID', 'VANID');
define('VN_LASTNAME', 'LastName');
define('VN_FIRSTNAME', 'FirstName');
define('VN_NICKNAME', 'Nickname');
define('VN_AGE', 'Age');
define('VN_SEX', 'Sex');
define('VN_STREETNO', 'StreetNo');
define('VN_STREETPREFIX', 'StreetPrefix');
define('VN_STREETNAME', 'StreetName');
define('VN_STREETTYPE', 'StreetType');
define('VN_CITY', 'City');
define('VN_COUNTY', 'County');
define('VN_CD', 'CD');
define('VN_HD', 'HD');
define('VN_PCT', 'Pct');
define('VN_HOMEPHONE', 'HomePhone');
define('VN_CELLPHONE', 'CellPhone');
define('VN_APTTYPE', 'AptType');
define('VN_APTNO', 'AptNo');
define('VN_MADDRESS', 'mAddress');
define('VN_MCITY', 'mCity');
define('VN_MSTATE', 'mState');
define('VN_MZIP', 'mZip');
define('VN_VOTING', 'Voting');
define('VN_VOTINGDISPLAY', 'VotingDisplay');
define('VN_DATEREG', 'DateReg');
define('VN_DORCURRENT', 'DORCurrent');
define('VN_PARTY', 'Party');

/**
 * Constants needed to access the nlp_voter_grp_tbl
 */
// column name
define('NV_INDX', 'indx');
define('NV_COUNTY', 'County');
define('NV_GRP_TYPE', 'Grp_Type');
define('NV_MCID', 'MCID');
define('NV_VANID', 'VANID');
define('NV_NLTURFINDEX', 'NLTurfIndex');
define('NV_VOTERSTATUS', 'Status');

/**
 * Constants needed to access the nlp_voter_status_tbl
 */
// column name.
define('VM_VANID', 'VANID');
define('VM_DORCURRENT', 'DORCurrent');
define('VM_MOVED', 'Moved');
define('VM_DECEASED', 'Deceased');
define('VM_HOSTILE', 'Hostile');