<?php
/*
 * Name:  voterdb_constants_ld_tbl.php  V3.0 2/8/17
 * This include file contains constants used in accessing the database for
 * the legislative district associated with an NL.  This is used only to 
 * repair the HD and Pct of an NL with a corrupted MyCampaign record.  It
 * is a workaround for a bug in VoteBuilder
 */
/**
 * VoterDB table names
 */
define('DB_LEG_DISTRICT_TBL', 'leg_district');

/**
 * nls_leg_district
 */
// column names
define('LD_COUNTY', 'County');
define('LD_MCID', 'MCID');
define('LD_FNAME', 'FName');
define('LD_LNAME', 'LName');
define('LD_HD', 'HD');
define('LD_PCT', 'Pct');

// Index.
define('LD_INDEX', 'LD_Index');  // COUNTY + MCID.