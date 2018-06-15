<?php

/*
 * Name:  voterdb_constants_van.php  V4.0 4/3/18
 * This include file contains constants used to interface with the VAN.
 */

/**
 * These constants define the structure of the export from the VAN of
 * voter records in a turf for a specific NL.  The first set is the names
 * of the columns from the VAN.  The second set is an index for an array
 * where we store the column number to process each voter's records.  The
 * headers are the ones we look for.  Some may be missing.   If others exist,
 * they will be ignored and not placed in the database.
 */

// Names of the columns from an export from the VAN
define('VH_VANID', 'VANID');
define('VH_VANID_ALT','Voter File VANID');
define('VH_FNAME', 'FirstName');
define('VH_LNAME', 'LastName');
define('VH_NICKNAME', 'Nickname');
define('VH_AGE', 'Age');
define('VH_STREETNO', 'StreetNo');
define('VH_STREETPREFIX', 'StreetPrefix');
define('VH_STREETNAME', 'StreetName');
define('VH_STREETTYPE', 'StreetType');
define('VH_CITY', 'City');
define('VH_COUNTY', 'CountyName');
define('VH_HOMEPHONE', 'Home Phone');
define('VH_CELLPHONE', 'Cell Phone');
define('VH_CD', 'CD');
define('VH_HD', 'HD');
define('VH_PCT', 'PrecinctName');
define('VH_VOTERID', 'StateFileID');
define('VH_APTTYPE', 'AptType');
define('VH_APTNO', 'AptNo');
define('VH_MADDRESS', 'mAddress');
define('VH_MCITY', 'mCity');
define('VH_MSTATE', 'mState');
define('VH_MZIP', 'mZip5');
define('VH_DATEREG', 'DateReg');
define('VH_DORCURRENT', 'DORCurrent');
define('VH_PARTY', 'Party');
define('VH_SALUTATION', 'Salutation');  // Depreciated.

// Note: the headers for the voting record have to be calculated when they
// election cycle is known.

// column index (for header array below)
define('VR_VANID', '0');
define('VR_FNAME', '1');
define('VR_LNAME', '2');
define('VR_NICKNAME', '3');
define('VR_AGE', '4');
define('VR_STREETNO', '5');
define('VR_STREETPREFIX', '6');
define('VR_STREETNAME', '7');
define('VR_STREETTYPE', '8');
define('VR_CITY', '9');
define('VR_COUNTY', '10');
define('VR_HOMEPHONE', '11');
define('VR_CELLPHONE', '12');
define('VR_CD', '13');
define('VR_HD', '14');
define('VR_PCT', '15');
define('VR_VOTERID', '16');
define('VR_APTTYPE', '17');
define('VR_APTNO', '18');
define('VR_MADDRESS', '19');
define('VR_MCITY', '20');
define('VR_MSTATE', '21');
define('VR_MZIP', '22');
define('VR_DATEREG', '23');
define('VR_DORCURRENT', '24');
define('VR_G1', '25');
define('VR_G2', '26');
define('VR_P1', '27');
define('VR_P2', '28');
define('VR_VANID_ALT', '29');
define('VR_PARTY', '30');
define('VR_SALUTATION', '31');

// Defines the header array for the VAN fields we will extract from the export.
// This will eliminate any need to have the VAN export in any specific order.
define('VH_HEADER_ARRAY', serialize(array(
    VH_VANID, VH_FNAME, VH_LNAME, VH_NICKNAME,VH_AGE,
    VH_STREETNO, VH_STREETPREFIX, VH_STREETNAME, VH_STREETTYPE, VH_CITY,
    VH_COUNTY,
    VH_HOMEPHONE, VH_CELLPHONE,
    VH_CD, VH_HD, VH_PCT, VH_VOTERID,
    VH_APTTYPE, VH_APTNO,
    VH_MADDRESS,VH_MCITY,VH_MSTATE,VH_MZIP,
    VH_DATEREG,VH_DORCURRENT,
    '','','','', // Voting record headers are filled in when we know the cycle.
    VH_VANID_ALT,VH_PARTY,VH_SALUTATION
)));
// Define the headers that must be present in the VAN export, 1=required
define('VH_REQUIRED_ARRAY', serialize(array(
    0, 1, 0, 0, 1,
    0, 0, 1, 0, 0,
    1,
    1, 1,
    1, 1, 1, 0,
    0,0,
    1,0,0,0,
    1,0,
    1,0,0,0,
    0,0,0
)));
// Error text for a missing header, this text matches the options in the 
// VAN export customization.
define('VH_MESSAGE_ARRAY', serialize(array(
    'VANID', 'Name', '', 'Nickname','Age',
    '', '', 'Voting Address (Separate Fields)', '', '',
    'County',
    'Home Phone', 'Cell Phone',
    'Legislative District','Legislative District', 'Precinct Name', 'State Voter ID',
    '','',
    'Mailing Address','','','',
    'DateReg','Date Effective Registration',
    'Voting History','','','',
    'Voter File VANID','Party'
)));

/**
 * These constants define the structure of the MyCampaign export of people
 * who are prospective NLs.  They are either PCPs or were, are or
 * have been NLs.  The first set is the text of the columns in the header.
 * The second set are indexes into an array to keep track of which column has
 * the information and which export options are present.
 */
// Names of the columns from an export of MyCampagn list
define('DH_MCID', 'My Campaign ID');
define('DH_FNAME', 'FirstName');
define('DH_LNAME', 'LastName');
define('DH_NICKNAME', 'Nickname');
define('DH_COUNTY', 'CountyName');
define('DH_HD', 'HD');
define('DH_PCT', 'PrecinctName');
define('DH_ADDR', 'Address');
define('DH_EMAIL', 'PreferredEmail');
define('DH_PHONE', 'Preferred Phone');
define('DH_HOMEPHONE', 'Home Phone');
define('DH_CELLPHONE', 'Cell Phone');
define('DH_CITY', 'City');
define('DH_SALUTATION', 'Salutation'); // Depreciated.
// This array defines the title of columns we want in the export of the 
// MyCampaign list of prospective NLs.
define('DH_HEADER_ARRAY', serialize(array(
    DH_MCID, DH_FNAME, DH_LNAME, DH_NICKNAME,
    DH_COUNTY,
    DH_HD, DH_PCT, DH_ADDR, DH_EMAIL,
    DH_PHONE, DH_HOMEPHONE, DH_CELLPHONE, DH_CITY, DH_SALUTATION
)));
// Columns required in the array above
define('DH_REQUIRED_ARRAY', serialize(array(
    0, 1, 0, 0,
    1,
    1, 1, 1, 1,
    1, 1, 1, 1, 0
)));
// Text of an error message when a column is missing.  The text matches the
// option for customizing a text export.
define('DH_MESSAGE_ARRAY', serialize(array(
    'MCID', 'Name', '', 'Nickname',
    'County',
    'Legislative Districts', 'PrecinctName', 'Primary Address (MyCampaign)', 'Email',
    'Preferred Phone', 'Home Phone', 'Cell Phone', 'City'
)));
// column index for header array above
define('HR_MCID', '0');
define('HR_FNAME', '1');
define('HR_LNAME', '2');
define('HR_NICKNAME', '3');
define('HR_COUNTY', '4');
define('HR_HD', '5');
define('HR_PCT', '6');
define('HR_ADDR', '7');
define('HR_EMAIL', '8');
define('HR_PHONE', '9'); 
define('HR_HOMEPHONE', '10');
define('HR_CELLPHONE', '11');
define('HR_CITY', '12');
define('HR_SALUTATION', '13');

/**
 * These constants define the structure of the export from the VAN of
 * voter records in a turf for a specific NL.  The first set is the names
 * of the columns from the VAN.  The second set is an index for an array
 * where we store the column number to process each voter's records.  The
 * headers are the ones we look for.  Some may be missing.   If others exist,
 * they will be ignored and not placed in the database.
 */

// Names of the columns from an export from the VAN of the voting counts.
define('VC1_COUNTY', 'County');
define('VC1_PARTY', 'Party');
define('VC1_VOTEDATE', 'Vote Return Date');
define('VC1_TOTAL', 'Total People');

// Names of the columns from an export from the VAN of the voting counts.
define('VC2_BALLOTS_RETURNED', 'Bal Ret');

// This array defines the title of columns we want in the export of the 
define('VC1_HEADER_ARRAY', serialize(array(
    VC1_COUNTY, VC1_PARTY, VC1_VOTEDATE, VC1_TOTAL
)));
// Columns required in the array above
define('VC1_REQUIRED_ARRAY', serialize(array(
    1, 1, 1, 1
)));
// Text of an error message when a column is missing.  The text matches the
// option for customizing a text export.
define('VC1_MESSAGE_ARRAY', serialize(array(
    'County', 'Party', 'Vote Return Date', 'Total People'
)));

// Names of the columns from an export from the VAN of the voting counts.
define('VI_COUNTY', '0');
define('VI_PARTY', '1');
define('VI_BALRET', '2');
define('VI_TOTAL', '3');


define('VC2_HEADER_ARRAY', serialize(array(
    VC2_BALLOTS_RETURNED
)));
// Columns required in the array above
define('VC2_REQUIRED_ARRAY', serialize(array(
    1,0
)));
// Text of an error message when a column is missing.  The text matches the
// option for customizing a text export.
define('VC2_MESSAGE_ARRAY', serialize(array(
    'Bal Ret','Unknown'
)));
// Names of the columns from an export from the VAN of the voting counts.
define('VI2_BALRET', '0');
define('VI2_UNKOWN', '1');

define('VP_PARTY_NAME_ARRAY', serialize(array(
  'Democrats','Republicans','Non-Affiliated','Pacific Green',
  'Libertarian','Constitution','Other','Independent Party',
  'Working Families','Progressive'
)));  

define('VP_PARTY_CODE_ARRAY', serialize(array(
  'D','R','N','G',
  'L','C','O','I',
  'W','P'
)));  

