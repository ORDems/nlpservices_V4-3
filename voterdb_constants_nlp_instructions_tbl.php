<?php
/*
 * Name:  voterdb_constants_nlp_instructions_tbl.php  V4.1  2/14/18
 * This include file contains constants used in accessing the database.
 */

/**
 * VoterDB table names
 */
define('DB_INSTRUCTIONS_TBL', 'instructions');

/** * * * * * * * * * * * * * * * * * * * * * * * * * *
 * nlp_instructions tbl
 */
// column names
define('NI_COUNTY', 'County');
define('NI_TYPE', 'Type');  // ENUM type.
define('NI_FILENAME', 'FileName');
define('NI_TITLE', 'Title');
define('NI_BLURB', 'Blurb');
// Enum types.
define('NE_CANVASS', 'canvass');
define('NE_POSTCARD', 'postcard');
define('NE_ABSENTEE', 'absentee');