<?php
/*
 * Name: voterdb_nlpsetup.php   V4.0 4/5/18
 * 
 * Creates the MySQL tables used by the module.  And, the table for the 
 * house district numbers is populated.  And, two basic pages are created
 * for use to display the call list and the mail list for an NL.
 */
require_once "voterdb_constants_bc_tbl.php";
require_once "voterdb_constants_goals_tbl.php";
require_once "voterdb_constants_hd_tbl.php";
require_once "voterdb_constants_ld_tbl.php";
require_once "voterdb_constants_log_tbl.php";
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_constants_nls_tbl.php";
require_once "voterdb_constants_turf_tbl.php";
require_once "voterdb_constants_van_tbl.php";
require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_constants_van_api_tbl.php";
require_once "voterdb_constants_bounce_tbl.php";
require_once "voterdb_constants_voter_tbl.php";
require_once "voterdb_constants_date_tbl.php";
require_once "voterdb_constants_nlp_instructions_tbl.php";
require_once "voterdb_constants_candidates_tbl.php";
require_once "voterdb_constants_coordinator_tbl.php";
require_once "voterdb_constants_coordinator_tbl.php";
require_once "voterdb_path.php";
require_once "voterdb_debug.php";
define('DB_COUNTY_INDEX_TYPE','INT(1)');  // Index to county name.
define('DB_UNIQUE','UNIQUE KEY ');
define('DB_PRIMARY','PRIMARY KEY ');
define('DB_INDEX',' INDEX ');
define('DB_HD_TYPE','INT(1)');
define('DB_VANID_TYPE','INT(12)');  // Primarily used as an index.
define('DB_MCID_TYPE','INT');   // Primarily used as an index.
define('DB_PCT_TYPE','VARCHAR(32)');
define('DB_COUNTY_TYPE',"ENUM_COUNTIES");
define('DB_COLLATE','COLLATE utf8_general_ci');
define('DB_PREFIX','nlp_');
// MATCHBACK -------------------------------------------------------
// Matchback tbl:  An entry for each person that has voted
define('DMB_MATCHBACK_TDEF',serialize(array(DB_MATCHBACK_TBL,'Voters whose ballots have been received.','')));
define('DMB_VANID',serialize (array(MT_VANID,DB_VANID_TYPE,'NOT NULL',DB_COLLATE,DB_PRIMARY, "COMMENT 'VAN ID'")));
define('DMB_DATE',serialize (array(MT_DATE_INDEX,'INT(1)',DB_COLLATE,"COMMENT 'Date ballot was recieved'")));
define('DMB_COUNTY',serialize (array(MT_COUNTY,DB_COUNTY_TYPE,"COMMENT 'County name'")));
define('DMB_MATCHBACK_FDEF', serialize (array(DMB_VANID,DMB_DATE,DMB_COUNTY)));
// BALLOT COUNT ----------------------------------------------------
// The count of ballots recieved for a county with counts by party.
define('DBC_BALLOTCOUNT_TDEF',serialize(array(DB_BALLOTCOUNT_TBL,
    'Ballots recieved by the county, Dems and Reps.',' UNIQUE INDEX BC_Index USING BTREE ('.BC_COUNTY.','.BC_PARTY.') ')));
define('DBC_COUNTY',serialize (array(BC_COUNTY,DB_COUNTY_TYPE, DB_COLLATE,"COMMENT 'Name of the Voter\'s county'")));
define('DBC_PARTY',serialize (array(BC_PARTY,'CHAR(4)',DB_COLLATE,"COMMENT 'Name of the Voter\'s county'")));
define('DBC_VOTERS',serialize (array(BC_REG_VOTERS,'INT',"COMMENT 'Total count of registered voters'")));
define('DBC_VOTED',serialize (array(BC_REG_VOTED,'INT',"COMMENT 'Count of ballots recieved for the county or party'")));
define('DBC_BALLOTCOUNT_FDEF', serialize (array(DBC_COUNTY,DBC_PARTY,DBC_VOTERS,DBC_VOTED)));
// NLP GOALS ----------------------------------------------------
// NL Recruitment goals for each county and each HD in the county
define('DNG_GOALS_TDEF',serialize(array(DB_NLPGOALS_TBL,'Goals for NL recruitment for HDs and County.',' UNIQUE INDEX GoalIndex USING BTREE ('.NM_COUNTY.','.NM_HD.') ')));
define('DNG_COUNTY',serialize (array(NM_COUNTY,DB_COUNTY_TYPE,'NOT NULL',DB_COLLATE, "COMMENT 'County Name'")));
define('DNG_HD',serialize (array(NM_HD,DB_HD_TYPE,'NOT NULL', DB_COLLATE,"COMMENT 'HD Number or ALL for County'")));
define('DNG_NLPGOAL',serialize (array(NM_NLPGOAL,'INT','DEFAULT 0', "COMMENT 'Goal for the HD or County'")));
define('DNG_GOALS_FDEF',serialize (array(DNG_COUNTY,DNG_HD, DNG_NLPGOAL)));
// NLP RESULTS -----------------------------------------------------
// Results reported by an NL, one result for each voter for each report
define('DNG_RESULTS_TDEF',serialize(array(DB_NLPRESULTS_TBL,'Result submitted by NL for a voter contact.',' INDEX('.NC_VANID.') ')));

define('DNR_RINDEX',serialize (array(NC_RINDEX,'INT','AUTO_INCREMENT',DB_PRIMARY,"COMMENT 'Auto increment key for report'")));
define('DNR_RECORDED',serialize (array(NC_RECORDED,'DATE',"COMMENT 'Date report was recorded in VoteBuilder'")));
define('DNR_CYCLE',serialize (array(NC_CYCLE,'CHAR(10)','NOT NULL',DB_COLLATE,"COMMENT 'Election Cycle ID'")));
define('DNR_COUNTY',serialize (array(NC_COUNTY,DB_COUNTY_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'Group Name'")));
define('DNR_ACTIVE',serialize (array(NC_ACTIVE,'TINYINT(1)','NOT NULL',DB_COLLATE,"COMMENT 'Displayable report'")));
define('DNR_MCID',serialize (array(NC_MCID,DB_MCID_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'MCID of NL'")));
define('DNR_VANID',serialize (array(NC_VANID,DB_VANID_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'VAN ID'")));
define('DNR_CDATE',serialize (array(NC_CDATE,'DATE','NOT NULL',DB_COLLATE,"COMMENT 'Date of voter contact'")));
define('DNR_TYPE',serialize (array(NC_TYPE,'VARCHAR(16)',DB_COLLATE,"COMMENT 'Type of contact'")));
define('DNR_VALUE',serialize (array(NC_VALUE,'VARCHAR(22)',DB_COLLATE,"COMMENT 'Code for contact result'")));
define('DNR_TEXT',serialize (array(NC_TEXT,'VARCHAR('.NC_COMMENT_MAX.')',DB_COLLATE,"COMMENT 'Text for contact result'")));
define('DNG_RESULTS_FDEF', serialize (array(DNR_RINDEX,DNR_RECORDED,DNR_CYCLE,DNR_COUNTY,
    DNR_ACTIVE,DNR_VANID,DNR_MCID,DNR_CDATE,DNR_TYPE,DNR_VALUE,DNR_TEXT)));
// NLP VOTER -------------------------------------------------------
// Record identifing a voter assigned to an NL
define('DNG_VOTER_TDEF',serialize(array(DB_NLPVOTER_TBL,'Information about a voter assigned to a turf.','')));
define('DNV_VANID',serialize (array(VN_VANID,DB_VANID_TYPE,DB_PRIMARY,DB_COLLATE, "COMMENT 'VAN ID of this voter'")));
define('DNV_LNAME',serialize (array(VN_LASTNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Voter\'s first name'")));
define('DNV_FNAME',serialize (array(VN_FIRSTNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Voter\'s last name'")));
define('DNV_NICKNAME',serialize (array(VN_NICKNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Voter\'s nick name'")));
define('DNV_AGE',serialize (array(VN_AGE,'CHAR(3)',DB_COLLATE,"COMMENT 'Voter\'s age'")));
define('DNV_SEX',serialize (array(VN_SEX,'CHAR(1)',DB_COLLATE,"COMMENT 'Voter\'s sex'")));
define('DNV_STREETNO',serialize (array(VN_STREETNO,'INT',DB_COLLATE, "COMMENT 'Voter\'s Street Number'")));
define('DNV_STREETPREFIX',serialize (array(VN_STREETPREFIX,'CHAR(4)',DB_COLLATE, "COMMENT 'Voter\'s Street Prefix, eg SW'")));
define('DNV_STREETNAME',serialize (array(VN_STREETNAME,'VARCHAR(30)',DB_COLLATE, "COMMENT 'Voter\'s Street Name'")));
define('DNV_STREETTYPE',serialize (array(VN_STREETTYPE,'VARCHAR(10)',DB_COLLATE, "COMMENT 'Voter\'s Street Type, eg Ave'")));
define('DNV_APTTYPE',serialize (array(VN_APTTYPE,'VARCHAR(10)',DB_COLLATE, "COMMENT 'Voter\'s Apartment Type, eg UNIT'")));
define('DNV_APTNO',serialize (array(VN_APTNO,'VARCHAR(10)',DB_COLLATE,"COMMENT 'Voter\'s Appartment Number'")));
define('DNV_CITY',serialize (array(VN_CITY,'VARCHAR(20)',DB_COLLATE,"COMMENT 'Voter\'s City'")));
define('DNV_COUNTY',serialize (array(VN_COUNTY,DB_COUNTY_TYPE,DB_COLLATE,"COMMENT 'Voter\'s County'")));
define('DNV_CD',serialize (array(VN_CD,'INT(1)',DB_COLLATE,"COMMENT 'Voter\'s Congressional District Number'")));
define('DNV_HD',serialize (array(VN_HD,DB_HD_TYPE,DB_COLLATE,"COMMENT 'Voter\'s House District Number'")));
define('DNV_PCT',serialize (array(VN_PCT,DB_PCT_TYPE,DB_COLLATE,"COMMENT 'Voter\'s Precinct Name'")));
define('DNV_HOMEPHONE',serialize (array(VN_HOMEPHONE,'BIGINT',DB_COLLATE)));
define('DNV_CELLPHONE',serialize (array(VN_CELLPHONE,'BIGINT',DB_COLLATE)));
define('DNV_MADDRESS',serialize (array(VN_MADDRESS,'VARCHAR(60)',DB_COLLATE, "COMMENT 'Voter\'s Mailing Address'")));
define('DNV_MCITY',serialize (array(VN_MCITY,'VARCHAR(20)',DB_COLLATE,"COMMENT 'Voter\'s Mailing City'")));
define('DNV_MSTATE',serialize (array(VN_MSTATE,'CHAR(3)',DB_COLLATE,"COMMENT 'Voter\'s Mailing State'")));
define('DNV_MZIP',serialize (array(VN_MZIP,'CHAR(6)',DB_COLLATE,"COMMENT 'Voter\'s Mailing ZIP'")));
define('DNV_VOTING',serialize (array(VN_VOTING,'VARCHAR(40)',DB_COLLATE,"COMMENT 'Voting record, for printout'")));
define('DNV_DATEREG',serialize (array(VN_DATEREG,'DATE',DB_COLLATE,"COMMENT 'Date voter first registered'")));
define('DNV_DORCURRENT',serialize (array(VN_DORCURRENT,'DATE',DB_COLLATE,"COMMENT 'Current date voter registered'")));
define('DNV_PARTY',serialize (array(VN_PARTY,'CHAR(2)',DB_COLLATE,"COMMENT 'Voter\'s party'")));
define('DNG_VOTER_FDEF', serialize (array(DNV_VANID,DNV_LNAME,DNV_FNAME, 
  DNV_NICKNAME,DNV_AGE,DNV_SEX,DNV_STREETNO,DNV_STREETPREFIX,DNV_STREETNAME,
  DNV_STREETTYPE,DNV_APTTYPE,DNV_APTNO, DNV_CITY,
  DNV_COUNTY,DNV_CD,DNV_HD,DNV_PCT,
  DNV_HOMEPHONE,DNV_CELLPHONE,DNV_MADDRESS,DNV_MCITY,DNV_MSTATE, DNV_MZIP,
  DNV_VOTING,DNV_DATEREG,DNV_DORCURRENT,DNV_PARTY)));
// NLP VOTER GRP --------------------------------------------------
// The Group table associates a voter with either a County NLP program or
// a campaign and a turf.   Voters can overlap between a county and a campaign.
define('DVG_VOTERGRP_TDEF',serialize(array(DB_NLPVOTER_GRP_TBL,'Information that associates a voter with a group.','')));
define('DVG_INDX',serialize (array(NV_INDX,'INT','AUTO_INCREMENT',DB_PRIMARY, DB_COLLATE,"COMMENT 'Unique id for this turf (autoindex)'")));
define('DVG_VANID',serialize (array(NV_VANID,DB_VANID_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'VAN ID of voter'")));
define('DVG_COUNTY',serialize (array(NV_COUNTY,DB_COUNTY_TYPE,DB_COLLATE, "COMMENT 'Group Name'")));
define('DVG_MCID',serialize (array(NV_MCID,DB_MCID_TYPE,DB_COLLATE,"COMMENT 'MCID of NL that owns the turf'")));
define('DVG_TURFINDEX',serialize (array(NV_NLTURFINDEX,'INT',DB_COLLATE,"COMMENT 'Index for the turf record'")));
define('DVG_VOTERSTATUS',serialize (array(NV_VOTERSTATUS,'CHAR(1)',DB_COLLATE,"COMMENT 'Status of the voter, ie, moved'")));
define('DVG_VOTERGRP_FDEF', serialize (array(DVG_INDX,DVG_VANID,DVG_COUNTY, 
  DVG_MCID,DVG_TURFINDEX,DVG_VOTERSTATUS)));
// NLP VOTER STATUS --------------------------------------------------
// The status table keeps track of NL reports of either a voter had moved
// or has been said to be deceased.  These two values are sticky for a specific
// current date of registration.
define('DVM_VOTERSTATUS_TDEF',serialize(array(DB_NLPVOTER_STATUS_TBL,'Information that keeps track of reports for moved or decessed.',' UNIQUE INDEX MIndex USING BTREE ('.VM_VANID.','.VM_DORCURRENT.') ')));
define('DVM_VANID',serialize (array(VM_VANID,DB_VANID_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'VAN ID of voter'")));
define('DVM_DORCURRENT',serialize (array(VM_DORCURRENT,'DATE',DB_COLLATE,"COMMENT 'Current date voter registered'")));
define('DVM_MOVED',serialize (array(VM_MOVED,'BOOLEAN','DEFAULT 0',"COMMENT 'Status of the voter, ie, moved'")));
define('DVM_DECEASED',serialize (array(VM_DECEASED,'BOOLEAN','DEFAULT 0',"COMMENT 'Status of the voter, ie, deceased'")));
define('DVM_HOSTILE',serialize (array(VM_HOSTILE,'BOOLEAN','DEFAULT 0',"COMMENT 'Status of the voter, ie, hostile'")));
define('DVM_VOTERSTATUS_FDEF', serialize (array(DVM_VANID,DVM_DORCURRENT, DVM_MOVED,DVM_DECEASED,DVM_HOSTILE)));
// NLS -------------------------------------------------------------
// A table of contact information for the potential NLs.
define('DNL_NLS_TDEF',serialize(array(DB_NLS_TBL,'The potential NLs.','')));
define('DNL_MCID',serialize (array(NH_MCID,DB_MCID_TYPE,'NOT NULL',DB_PRIMARY,DB_COLLATE,"COMMENT 'MCID of NL'")));
define('DNL_LNAME',serialize (array(NH_LNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Last Name of NL'")));
define('DNL_FNAME',serialize (array(NH_FNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'First Name of NL'")));
define('DNL_NICKNAME',serialize (array(NH_NICKNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Nickname of NL - used for login'")));
define('DNL_COUNTY',serialize (array(NH_COUNTY,DB_COUNTY_TYPE,DB_COLLATE,"COMMENT 'County Name of NL'")));
define('DNL_HD',serialize (array(NH_HD,DB_HD_TYPE,DB_COLLATE,"COMMENT 'HD number of NL'")));
define('DNL_PCT',serialize (array(NH_PCT,DB_PCT_TYPE,DB_COLLATE,"COMMENT 'Precinct name of NL'")));
define('DNL_ADDR',serialize (array(NH_ADDR,'VARCHAR(128)',DB_COLLATE,"COMMENT 'Home address of NL'")));
define('DNL_EMAIL',serialize (array(NH_EMAIL,'VARCHAR(60)',DB_COLLATE,"COMMENT 'Email address of NL'")));
define('DNL_PHONE',serialize (array(NH_PHONE,'BIGINT',DB_COLLATE,"COMMENT 'Prefered phone number - used in display'")));
define('DNL_HOMEPHONE',serialize(array(NH_HOMEPHONE,'BIGINT',DB_COLLATE,"COMMENT 'Home number - not currently used'")));
define('DNL_CELLPHONE',serialize(array(NH_CELLPHONE,'BIGINT',DB_COLLATE,"COMMENT 'Cell phone number - not currently used'")));
define('DNL_NLS_FDEF', serialize (array(DNL_MCID,DNL_FNAME,DNL_LNAME,DNL_NICKNAME,DNL_COUNTY,
    DNL_HD,DNL_PCT,DNL_ADDR,DNL_EMAIL,DNL_PHONE,DNL_HOMEPHONE,DNL_CELLPHONE)));
// NLS GRP ---------------------------------------------------------
// A table that associatea a NL with a group.   An NL can volunteer for both
// a county and for a campaign.
define('DSG_NLSGRP_TDEF',serialize(array(DB_NLS_GRP_TBL,'An association between and NL and a group',' UNIQUE INDEX NL_Index USING BTREE ('.NG_COUNTY.','.NG_MCID.') ')));
define('DSG_MCID',serialize (array(NG_MCID,DB_MCID_TYPE,DB_COLLATE,"COMMENT 'MCID of the NL'")));
define('DSG_COUNTY',serialize (array(NG_COUNTY,DB_COUNTY_TYPE,DB_COLLATE,"COMMENT 'Group Name'")));
define('DSG_NLSGRP_FDEF', serialize (array(DSG_COUNTY,DSG_MCID)));
// NLS STATUS ------------------------------------------------------
// Status of the NL - indicates participation and completion
define('DNS_NLSSTATUS_TDEF',serialize(array(DB_NLSSTATUS_TBL,
  'Information about an NLs participation.',' UNIQUE INDEX NS_Index USING BTREE ('.NN_MCID.','.NN_COUNTY.') ')));
define('DNS_MCID',serialize (array(NN_MCID,DB_MCID_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'MCID of NL'")));
define('DNS_COUNTY',serialize (array(NN_COUNTY,DB_COUNTY_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'County Name of NL'")));
define('DNS_LOGINDATE',serialize (array(NN_LOGINDATE,'DATE',DB_COLLATE,"COMMENT 'Date the NL logged in'")));
define('DNS_CONTACT',serialize (array(NN_CONTACT,CT_CONTACT_TYPE,DB_COLLATE,"COMMENT 'Type of contact to be made by NL'")));
define('DNS_NLSIGNUP',serialize (array(NN_NLSIGNUP,'CHAR(1)',DB_COLLATE,"COMMENT 'NL signed up for this election'")));
define('DNS_TURFCUT',serialize (array(NN_TURFCUT,'CHAR(1)',DB_COLLATE,"COMMENT 'Turf checked in for this NL'")));
define('DNS_TURFDEL',serialize (array(NN_TURFDELIVERED,'CHAR(1)',DB_COLLATE,"COMMENT 'Materials delivered to this NL'")));
define('DNS_RESULTSRPD',serialize (array(NN_RESULTSREPORTED,'CHAR(1)',DB_COLLATE,"COMMENT 'NL has reported results of voter contact'")));
define('DNS_ASKED',serialize (array(NN_ASKED,AT_ASKED_TYPE,"COMMENT 'NL has reported results of voter contact'")));
define('DNS_NOTES',serialize (array(NN_NOTES,'VARCHAR('.NN_NOTES_SIZE.')',DB_COLLATE,"COMMENT 'Note about voter from NL'")));
define('DNS_NLSSTATUS_FDEF', serialize (array(DNS_MCID,DNS_COUNTY,
    DNS_LOGINDATE, DNS_CONTACT,DNS_NLSIGNUP,DNS_TURFCUT,DNS_TURFDEL,
    DNS_RESULTSRPD, DNS_ASKED, DNS_NOTES)));
// NLS STATUS HISTORY ------------------------------------------------------
// History of NL participation.
define('DNS_NLSSTATUS_HISTORY_TDEF',serialize(array(DB_NLSSTATUS_HISTORY_TBL,
  'Information about an NLs participation.',' INDEX('.NY_MCID.') ')));
define('DNH_HINDEX',serialize (array(NY_HINDEX,'INT(11)','AUTO_INCREMENT',DB_PRIMARY,"COMMENT 'Index to the list.'")));
define('DNH_DATE',serialize (array(NY_DATE,'DATE','NOT NULL',DB_COLLATE,"COMMENT 'Date of change in NL status'")));
define('DNH_MCID',serialize (array(NY_MCID,DB_MCID_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'MCID of NL'")));
define('DNH_COUNTY',serialize (array(NY_COUNTY,DB_COUNTY_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'County Name of NL'")));
define('DNH_CYCLE',serialize (array(NY_CYCLE,'CHAR(10)',DB_COLLATE,"COMMENT 'Election cycle for this participation'")));
define('DNH_STATUS',serialize (array(NY_STATUS,NY_NL_HISTORY_TYPE,DB_COLLATE,"COMMENT 'Status of NL participation for a cycle.'")));
define('DNH_NLFNAME',serialize (array(NY_NLFNAME,'CHAR(30)',DB_COLLATE,"COMMENT 'NL first name'")));
define('DNH_NLLNAME',serialize (array(NY_NLLNAME,'CHAR(30)',DB_COLLATE,"COMMENT 'NL Last name'")));
define('DNS_NLSSTATUS_HISTORY_FDEF', serialize (array(DNH_HINDEX,DNH_DATE, 
    DNH_MCID,DNH_COUNTY,DNH_CYCLE, DNH_STATUS,DNH_NLFNAME,DNH_NLLNAME)));
// TURF ------------------------------------------------------------
// Contains information about a turf checked in for an NL
define('DNF_TURF_TDEF',serialize(array(DB_NLSTURF_TBL,'Information about a turf checked in for an NL.','')));
define('DNF_TURFINDEX',serialize (array(TT_INDEX,'INT','AUTO_INCREMENT',DB_PRIMARY,"COMMENT 'Unique index for this turf'")));
define('DNF_COUNTY',serialize (array(TT_COUNTY,DB_COUNTY_TYPE,DB_COLLATE,"COMMENT 'Group name - either County or Campaign'")));
define('DNF_MCID',serialize (array(TT_MCID,DB_MCID_TYPE,DB_COLLATE,"COMMENT 'MCID of NL'")));
define('DNF_FNAME',serialize (array(TT_NLLNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'First Name of NL, ie Nickname'")));
define('DNF_LNAME',serialize (array(TT_NLFNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Last Name of NL'")));
define('DNF_CYCLE',serialize (array(TT_CYCLE,'CHAR(10)','NOT NULL',DB_COLLATE,"COMMENT 'Election Cycle turf was checked in'")));
define('DNF_DELIVERED',serialize (array(TT_DELIVERED,'DATE',DB_COLLATE,"COMMENT 'Date the turf delivered to NL'")));
define('DNF_LASTACCESS',serialize (array(TT_LASTACCESS,'DATE',DB_COLLATE,"COMMENT 'Date the turf accessed by NL'")));
define('DNF_REMINDERNEEDED',serialize (array(TT_REMINDERNEEDED,'CHAR(1)',DB_COLLATE,"COMMENT 'Remind NL to report results'")));
define('DNF_TURFNAME',serialize (array(TT_NAME,'VARCHAR(160)',DB_COLLATE,"COMMENT 'Name of the turf given when checked in.'")));
define('DNF_TURFPDF',serialize (array(TT_PDF,'VARCHAR(160)',DB_COLLATE,"COMMENT 'Name of the PDF file if given at checkin'")));
define('DNF_TURFMAIL',serialize (array(TT_MAIL,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Name of the Mail file created at checkin'")));
define('DNF_TURFCALL',serialize (array(TT_CALL,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Name of the call file created at data entry'")));
define('DNF_TURFHD',serialize (array(TT_HD,DB_HD_TYPE,DB_COLLATE,"COMMENT 'HD of the turf'")));
define('DNF_TURFPCT',serialize (array(TT_PCT,DB_PCT_TYPE,DB_COLLATE,"COMMENT 'Precinct of the turf - turfs do not span precincts'")));
define('DNF_TURF_FDEF', serialize (array(DNF_TURFINDEX,DNF_COUNTY,
  DNF_MCID,DNF_FNAME,DNF_LNAME,DNF_DELIVERED,DNF_LASTACCESS,DNF_REMINDERNEEDED,
  DNF_TURFNAME,DNF_TURFPDF,DNF_TURFMAIL,DNF_TURFCALL,DNF_TURFHD,DNF_TURFPCT)));
// LEG DISTRICT ----------------------------------------------------
//
define('DLD_LEGDISTRICT_TDEF',serialize(array(DB_LEG_DISTRICT_TBL,'Fix for MyCampaign failure to assign legislative district to NL','')));
define('DLD_COUNTY',serialize (array(LD_COUNTY,DB_COUNTY_TYPE,'NOT NULL', DB_COLLATE, "COMMENT 'County name'")));
define('DLD_MCID',serialize (array(LD_MCID,DB_MCID_TYPE,'NOT NULL',DB_PRIMARY,"COMMENT 'MCID of the NL'")));
define('DLD_FNAME',serialize (array(LD_FNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'First Name of NL (Nickname)'")));
define('DLD_LNAME',serialize (array(LD_LNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Last name of NL'")));
define('DLD_HD',serialize (array(LD_HD,DB_HD_TYPE,DB_COLLATE,"COMMENT 'HD of the NL'")));
define('DLD_PCT',serialize (array(LD_PCT,DB_PCT_TYPE,DB_COLLATE,"COMMENT 'Precinct of the NL'")));
define('DLD_LEGDISTRICT_FDEF', serialize (array(DLD_COUNTY,DLD_MCID,DLD_FNAME,DLD_LNAME,DLD_HD,DLD_PCT)));
// TRACK -----------------------------------------------------------
// A log file of NLs login and of Turf check in.  Information to help
// determine why NLs have a problem reporting data.
define('DTR_TRACK_TDEF',serialize(array(DB_TRACK_TBL,'A log of NLS activity reporting results, and a track of turf checkin','')));
define('DTR_INDX',serialize (array(TR_INDX,'INT','AUTO_INCREMENT',DB_PRIMARY, "COMMENT 'Index for tracking NL activity'")));
define('DTR_COUNTY',serialize (array(TR_COUNTY,DB_COUNTY_TYPE,"COMMENT 'Group Name'")));
define('DTR_TYPE',serialize (array(TR_TYPE,'CHAR(10)',DB_COLLATE,"COMMENT 'Type of event (arbitraty)'")));
define('DTR_DATE',serialize (array(TR_DATE,'DATETIME',DB_COLLATE,"COMMENT 'Date event was logged'")));
define('DTR_USER',serialize (array(TR_USER,'VARCHAR(62)',DB_COLLATE,"COMMENT 'User ID of logged in user'")));
define('DTR_IP',serialize (array(TR_IP,'VARCHAR(45)',DB_COLLATE,"COMMENT 'IP address of person attempting to login'")));
define('DTR_STATUS',serialize (array(TR_STATUS,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Status indicator of event'")));
define('DTR_INFO',serialize (array(TR_INFO,'VARCHAR(256)',DB_COLLATE,"COMMENT 'Invalid password attempted by failed login'")));
define('DTR_TRACK_FDEF',serialize (array(DTR_INDX,DTR_COUNTY,DTR_TYPE,DTR_DATE,
    DTR_USER,DTR_IP,DTR_STATUS,DTR_INFO)));
// VAN ACCOUNT ------------------------------------------------------
// VAN account informaton for counties and campaigns
define('DVN_ACCOUNT_TDEF',serialize(array(DB_VAN_API_TBL,'The VAN login for counties and campaigns.','')));
define('DVN_COUNTY',serialize (array(AI_COUNTY,DB_COUNTY_TYPE,DB_PRIMARY,"COMMENT 'Group Name'")));
define('DVN_URL',serialize (array(AI_ACCOUNT,'VARCHAR(128)',DB_COLLATE,"COMMENT 'VoteBuilder URL instance'")));
define('DVN_USER',serialize (array(AI_ACCOUNT,'VARCHAR(30)',DB_COLLATE,"COMMENT 'VAN API User'")));
define('DVN_KEY',serialize (array(AI_KEY,'VARCHAR(30)',DB_COLLATE,"COMMENT 'VAN API Key'")));
define('DVN_ACCOUNT_FDEF', serialize (array(DVN_COUNTY,DVN_URL,DVN_USER,DVN_KEY)));
// HD --------------------------------------------------------------
// List of House Districts in a county.
define('DHD_HD_TDEF',serialize(array(DB_HD_TBL,'List of House District in each county.',' UNIQUE INDEX HD_Index USING BTREE ('.HD_COUNTY.','.HD_NUMBER.') ')));
define('DHD_COUNTY',serialize (array(HD_COUNTY,DB_COUNTY_TYPE, "COMMENT 'Index to county name'")));
define('DHD_NUMBER',serialize (array(HD_NUMBER,DB_HD_TYPE,'NOT NULL',DB_COLLATE,"COMMENT 'House District Number'")));
define('DHD_HD_FDEF', serialize (array(DHD_COUNTY,DHD_NUMBER)));
// Date --------------------------------------------------------------
// List of date strings for ballot received date.
define('DUY_DATE_TDEF',serialize(array(DB_DATE_TBL,'List of unique dates for ballot returned.','')));
define('DUY_INDEX',serialize (array(DA_INDEX,'INT(1)','NOT NULL',DB_COLLATE,DB_PRIMARY, "COMMENT 'Index to a BR date'")));
define('DUY_DATE',serialize (array(DA_DATE,'DATE','NOT NULL', DB_COLLATE, "COMMENT 'A date in text'")));
define('DUY_DATE_FDEF', serialize (array(DUY_INDEX,DUY_DATE)));
// Coodinator --------------------------------------------------------------
// List of date strings for ballot received date.
define('DCR_COORDINATOR_TDEF',serialize(array(DB_COORDINATOR_TBL,'List of coordinators for NLP.','')));
define('DCR_INDEX',serialize (array(CR_CINDEX,'INT','NOT NULL',DB_COLLATE ,'AUTO_INCREMENT', DB_PRIMARY, "COMMENT 'Unique index of this Coordinator'")));
define('DCR_COUNTY',serialize (array(CR_COUNTY,DB_COUNTY_TYPE,'NOT NULL', DB_COLLATE, "COMMENT 'County name'")));
define('DCR_FIRSTNAME',serialize (array(CR_FIRSTNAME,'VARCHAR(30)','NOT NULL', DB_COLLATE, "COMMENT 'First name of coordinator'")));
define('DCR_LASTNAME',serialize (array(CR_LASTNAME,'VARCHAR(30)','NOT NULL', DB_COLLATE, "COMMENT 'Last name of coordinator'")));
define('DCR_EMAIL',serialize (array(CR_EMAIL,'VARCHAR(60)','NOT NULL', DB_COLLATE, "COMMENT 'Coordinator\'s email'")));
define('DCR_PHONE',serialize (array(CR_PHONE,'VARCHAR(20)','NOT NULL', DB_COLLATE, "COMMENT 'Coordinator\'s phone'")));
define('DCR_SCOPE',serialize (array(CR_SCOPE,"ENUM('County','HD','Pct')",'NOT NULL', DB_COLLATE, "COMMENT 'Scope of role for coordinator: County, HD or Precincts'")));
define('DCR_HD',serialize (array(CR_HD,DB_HD_TYPE,'NOT NULL', DB_COLLATE, "COMMENT 'Coordinator\'s HD if not county role'")));
define('DCR_PARTIAL',serialize (array(CR_PARTIAL,'INT(1)','NOT NULL', DB_COLLATE, "COMMENT 'Indicates there is a list of precincts.'")));
define('DCR_COORDINATOR_FDEF', serialize (array(DCR_INDEX,DCR_COUNTY, DCR_FIRSTNAME,DCR_LASTNAME,DCR_EMAIL,DCR_PHONE,DCR_SCOPE,DCR_HD,DCR_PARTIAL)));
// Instructions --------------------------------------------------------------
// List of date strings for ballot received date.
define('DNI_INSTRUCTIONS_TDEF',serialize(array(DB_INSTRUCTIONS_TBL,'Database of NLP instructions for the county.',' UNIQUE INDEX OIndex USING BTREE ('.NI_COUNTY.','.NI_TYPE.') ')));
define('DNI_COUNTY',serialize (array(NI_COUNTY,DB_COUNTY_TYPE,'NOT NULL', DB_COLLATE, "COMMENT 'County name'")));
define('DNI_TYPE',serialize (array(NI_TYPE,"ENUM('canvass','postcard','absentee')",'NOT NULL', DB_COLLATE, "COMMENT 'Type of instructions: canvass or postcard'")));
define('DNI_FILENAME',serialize (array(NI_FILENAME,'VARCHAR(160)', DB_COLLATE, "COMMENT 'Title for the instructions'")));
define('DNI_TITLE',serialize (array(NI_TITLE,'VARCHAR(20)','NOT NULL', DB_COLLATE, "COMMENT 'File name of the instructions'")));
define('DNI_BLURB',serialize (array(NI_FILENAME,'VARCHAR(256)', DB_COLLATE, "COMMENT 'Text describing instructions'")));
define('DNI_INSTRUCTIONS_FDEF', serialize (array(DNI_COUNTY,DNI_TYPE,DNI_FILENAME)));
// Pct Coordinator ---------------------------------------------------------
// List of date strings for ballot received date.
define('DPC_PCP_COORDINATOR_TDEF',serialize(array(DB_PCT_COORDINATOR_TBL,'Database of precincts for a coordinator.',
  ' UNIQUE INDEX GType USING BTREE ('.PC_CINDEX.','.PC_PCT.'), INDEX Pct USING BTREE ('.PC_PCT.')')));
define('DPC_CINDEX',serialize (array(PC_CINDEX,'INT','NOT NULL',DB_COLLATE,  "COMMENT 'Index of this Coordinator'")));
define('DPC_PCT',serialize (array(PC_PCT,DB_PCT_TYPE,'NOT NULL', DB_COLLATE, "COMMENT 'Assigned precinct'")));
define('DPC_PCP_COORDINATOR_FDEF', serialize (array(DPC_CINDEX,DPC_PCT)));
// Candidates --------------------------------------------------------------
// List of date strings for ballot received date.
define('DCS_CANDIDATES_TDEF',serialize(array(DB_CANDIDATES_TBL,'List of Candidates.','')));
define('DCS_INDEX',serialize (array(CR_CINDEX,'INT','NOT NULL',DB_COLLATE ,'AUTO_INCREMENT', DB_PRIMARY, "COMMENT 'Unique index of this Candidate'")));
define('DCS_NAME',serialize (array(CD_CNAME,'VARCHAR(30)','NOT NULL', DB_COLLATE, "COMMENT 'Last name of candidate'")));
define('DCS_WEIGHT',serialize (array(CD_WEIGHT,'TINYINT', "COMMENT 'Weight of display order'")));
define('DCS_SCOPE',serialize (array(CD_SCOPE,"ENUM('State','CD','County','HD','Pcts')",'NOT NULL', DB_COLLATE, "COMMENT 'Scope of candidates election district'")));
define('DCS_COUNTY',serialize (array(CD_COUNTY,DB_COUNTY_TYPE, DB_COLLATE, "COMMENT 'County name'")));
define('DCS_CD',serialize (array(CD_CD,'INT(1)', DB_COLLATE, "COMMENT 'House District'")));
define('DCS_HD',serialize (array(CD_HD,DB_HD_TYPE, DB_COLLATE, "COMMENT 'House District'")));
define('DCS_PCTS',serialize (array(CD_PCTS,'VARCHAR(240)', DB_COLLATE, "COMMENT 'List of precincts'")));
define('DCS_CANDIDATES_FDEF', serialize (array(DCS_INDEX,DCS_NAME,DCS_WEIGHT, DCS_SCOPE, DCS_CD ,DCS_COUNTY, DCS_HD,DCS_PCTS)));
// Bounce notification table ------------------------------------------------
// Keeps track of NL emails that have bounced.
define('DBN_BOUNCE_NOTIFIED_TDEF',serialize(array(DB_BOUNCE_REPORT_NOTIFY_TBL,'List of NL emails that have bounced.',' INDEX('.BA_NLEMAIL.') ')));
define('DBN_REPORTID',serialize (array(BA_REPORT_ID,'INT(10)','NOT NULL',DB_PRIMARY,"COMMENT 'Report id for a bounced email.'")));
define('DBN_BLOCKEDID',serialize (array(BA_BLOCK_ID,'INT(10)',"COMMENT 'Id for a blocked email.'")));
define('DBN_NOTIFIED',serialize (array(BA_NOTIFIED,"ENUM('Y','N')",'NOT NULL',DB_COLLATE,"COMMENT 'Sender was notified of blocked email'")));
define('DBN_DATE',serialize (array(BA_DATE,'DATETIME','NOT NULL',"COMMENT 'County Name of NL'")));
define('DBN_COUNTY',serialize (array(BA_COUNTY,DB_COUNTY_TYPE,'NOT NULL',"COMMENT 'Date email bounce reported'")));
define('DBN_NLFNAME',serialize (array(BA_NLFNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'NL first name'")));
define('DBN_NLLNAME',serialize (array(BA_NLLNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'NL Last name'")));
define('DBN_NLEMAIL',serialize (array(BA_NLEMAIL,'VARCHAR(60)',DB_COLLATE,"COMMENT 'NLs email that bounced'")));
define('DBN_SFNAME',serialize (array(BA_SFNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Senders first name'")));
define('DBN_SLNAME',serialize (array(BA_SLNAME,'VARCHAR(30)',DB_COLLATE,"COMMENT 'Senders last name'")));
define('DBN_SEMAIL',serialize (array(BA_SEMAIL,'VARCHAR(60)',DB_COLLATE,"COMMENT 'Email of sender who was notified of bounce'")));
define('DBN_CODE',serialize (array(BA_CODE,'VARCHAR(32)',DB_COLLATE,"COMMENT 'Bounce reason code.'")));
define('DBN_DESCRIPTION',serialize (array(BA_DESCRIPTION,'VARCHAR(255)',DB_COLLATE,"COMMENT 'Description of reason for bounce.'")));
define('DBN_BOUNCE_NOTIFIED_FDEF', serialize (array(DBN_REPORTID,DBN_BLOCKEDID,
    DBN_NOTIFIED,DBN_DATE,DBN_COUNTY,
    DBN_NLFNAME, DBN_NLLNAME,DBN_NLEMAIL,
    DBN_SFNAME,DBN_SLNAME,DBN_SEMAIL,
    DBN_CODE,DBN_DESCRIPTION)));
// -----------------------------------------------------------------
// The TBLS array is the list of tables used by NLP Service in the voterdb
// database.   Each entry contains the name and the description.  The FIELDS
// array contains the description of each field in the table.  The order is
// the same as for the TBLS array.
define('DB_TBLS_ARRAY', serialize (array (
  DNL_NLS_TDEF,DNG_VOTER_TDEF,DNF_TURF_TDEF,
  DNG_RESULTS_TDEF,DMB_MATCHBACK_TDEF,DNS_NLSSTATUS_TDEF,
  DVG_VOTERGRP_TDEF,DBC_BALLOTCOUNT_TDEF,DTR_TRACK_TDEF,
  DSG_NLSGRP_TDEF,DLD_LEGDISTRICT_TDEF,DNG_GOALS_TDEF,
  DVN_ACCOUNT_TDEF,DHD_HD_TDEF,DUY_DATE_TDEF,
  DCR_COORDINATOR_TDEF,DNI_INSTRUCTIONS_TDEF,DPC_PCP_COORDINATOR_TDEF,
  DCS_CANDIDATES_TDEF,DNS_NLSSTATUS_HISTORY_TDEF,DVM_VOTERSTATUS_TDEF)));
define('DB_FIELDS_ARRAY', serialize (array (
  DNL_NLS_FDEF,DNG_VOTER_FDEF,DNF_TURF_FDEF,
  DNG_RESULTS_FDEF,DMB_MATCHBACK_FDEF,DNS_NLSSTATUS_FDEF,
  DVG_VOTERGRP_FDEF,DBC_BALLOTCOUNT_FDEF,DTR_TRACK_FDEF,
  DSG_NLSGRP_FDEF,DLD_LEGDISTRICT_FDEF,DNG_GOALS_FDEF,
  DVN_ACCOUNT_FDEF,DHD_HD_FDEF,DUY_DATE_FDEF,
  DCR_COORDINATOR_FDEF,DNI_INSTRUCTIONS_FDEF,DPC_PCP_COORDINATOR_FDEF,
  DCS_CANDIDATES_FDEF,DNS_NLSSTATUS_HISTORY_FDEF,DVM_VOTERSTATUS_FDEF)));
// Tables to be defined in the Drupal database rather than voterdb.
define('DB_DRUPAL_TBLS_ARRAY', serialize (array (
  DBN_BOUNCE_NOTIFIED_TDEF)));
define('DB_DRUPAL_FIELDS_ARRAY', serialize (array (
  DBN_BOUNCE_NOTIFIED_FDEF)));

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_tables
 * 
 * For each table, get all the field definitions and create the table.  Drop
 * the table is one already exists before creating it again.  This removes 
 * any residual information from a previous election cycle.
 * 
 * @param type $bg_tbl_names - the array describing the table.
 * @param type $bg_field_names - the array describing the fields in the table.
 * @param type $bg_database - either default or nlp_voterdb.
 * @return string - nothing is returned
 */
function voterdb_build_tables($bg_enum,$bg_tbl_names,$bg_field_names,$bg_database) {
  $bg_tables_def = unserialize($bg_tbl_names);
  $bg_fields_def = unserialize($bg_field_names);
  $bg_tbl = 0;
  foreach ($bg_tables_def as $bg_table_def) {
    $bg_field_info = unserialize($bg_fields_def[$bg_tbl]);
    $bg_table_info = unserialize($bg_table_def);
    $bg_table_name = $bg_table_info[0];
    $bg_table_comment = $bg_table_info[1];
    $bg_tbl_multicolumn = $bg_table_info[2];
    drupal_set_message($bg_table_name,'status');
    $bg_create_query = "";
    $bg_field_names_cnt = count($bg_field_info);
    $bg_fcnt = 1;
    foreach ($bg_field_info as $bg_field_def) {
      $bg_fields = unserialize($bg_field_def);
      $bg_type_cnt  = count($bg_fields);
      $bg_tcnt = 1;
      foreach ($bg_fields as $bg_field) {
        if ($bg_field == DB_COUNTY_TYPE) {
          $bg_create_query .= $bg_enum;
        } else {
          $bg_create_query .= $bg_field;
        }
        if ($bg_tcnt < $bg_type_cnt) {$bg_create_query .= ' ';}
        $bg_tcnt++;
      }
      if ($bg_fcnt<$bg_field_names_cnt) {$bg_create_query .= ' , ';}
      $bg_fcnt++;
    }
    // Create the table
    db_set_active($bg_database);
    try {
      db_query("DROP TABLE IF EXISTS {".$bg_table_name."} ");
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    try {
      $bg_cquery = "CREATE TABLE {".$bg_table_name."} ( ";
      $bg_cquery .= $bg_create_query;
      if ($bg_tbl_multicolumn != '') {
        $bg_cquery .= ", ".$bg_tbl_multicolumn;
        } 
      $bg_cquery .= ") DEFAULT ".DB_COLLATE;
      $bg_cquery .= " ENGINE = INNODB COMMENT = '".$bg_table_comment."'";

      db_query($bg_cquery);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');

    $bg_tbl++;
  }
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_build
 * 
 * Build the list of county names and establish the index for each.
 * Populate the HD table with the house district numbers for each participating 
 * county.  The HD table must already be created.
 *
 */
function voterdb_hd_build($hb_county_defs) {
  
  foreach ($hb_county_defs as $hb_county_name=>$hd_hd_def) { 

    $hb_hd_cnt = 0;
    foreach ($hd_hd_def as $hb_hd_num) {
      
      // Insert a record for the HD number in the county.
      db_set_active('nlp_voterdb');
      try {
        db_insert(DB_HD_TBL)
          ->fields(array(
            HD_COUNTY => $hb_county_name,
            HD_NUMBER => $hb_hd_num,
          ))
          ->execute();
        }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return 0;
        }
      db_set_active('default');
    }
    $hb_hd_cnt++;
  }
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_goals_build
 * 
 * Fill up the Goals tables with values to simplify code checking later.
 * The values are all zero to start.
 * 
 * Nothing is returned.
 */
function voterdb_goals_build($gb_county_defs) {
  foreach ($gb_county_defs as $gb_county=>$gb_hd_def) {
    //$gb_county_info = unserialize($hd_county_def);
    db_set_active('nlp_voterdb');
    try {
      db_insert(DB_NLPGOALS_TBL)
        ->fields(array(
          NM_COUNTY => $gb_county,
          NM_HD => 0,  // indicates the entire county.
          NM_NLPGOAL => 0,
        ))
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }

    foreach ($gb_hd_def as $gb_hd_num) {
      // Insert a record for the HD number in the county.
      db_set_active('nlp_voterdb');
      try {
        db_insert(DB_NLPGOALS_TBL)
          ->fields(array(
            NM_COUNTY => $gb_county,
            NM_HD => $gb_hd_num,
            NM_NLPGOAL => 0,
          ))
          ->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return FALSE;
      }
      db_set_active('default');
    }
  }
}

function voterdb_create_front_page($fp_name,$fp_counties) {
  $fp_county_names = array_keys($fp_counties);
  $fp_module_path = drupal_get_path('module','voterdb');
  $fp_front_page = $fp_module_path."/voterdb_".$fp_name.'.txt';
  $fp_front_page_fh = fopen($fp_front_page,"w");
  $fp_tbl_start = '<p>Please click the your county name below to take you to the appropriate Neighborhood Leader Login.</p>
     <table style="width: 550px;"><tbody>';
  fwrite($fp_front_page_fh, $fp_tbl_start);

  $fp_col = 0;
  foreach ($fp_county_names as $fp_county_name) {
    if($fp_col == 0) {
      fwrite($fp_front_page_fh, '<tr>');
    } 
    $fp_td = '<td style="width: 100px;"><a href="/nlpdataentry?County='.
      $fp_county_name.'">'.$fp_county_name.'</a></td>';
    fwrite($fp_front_page_fh, $fp_td);
    if ($fp_col == 5) {
      fwrite($fp_front_page_fh, '</tr>');
      $fp_col = -1;
    }
    $fp_col++;
  }
  if($fp_col != 0) {
    for ($i = $fp_col; $i < 5; $i++) {
      fwrite($fp_front_page_fh, '<td></td>');
    }
  }
  $fp_tbl_end = '</tbody></table><!--break-->';
  fwrite($fp_front_page_fh, $fp_tbl_end);
  fclose($fp_front_page_fh);
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_create_node
 * 
 * Create a standard Drupal page for display of some content that the user
 * may want to print.  And create a page for the login challenge.
 * 
 * @param type $cn_name
 * @param type $cn_title
 * @param type $cn_title - type of code, php or html
 */
function voterdb_create_node($cn_name,$cn_title,$cn_type,$cn_promote) {
  // Check if the Drupal page exists.
  $cn_url = drupal_lookup_path('source', $cn_name, NULL);
  $cn_nid = 0;
  if ($cn_url) {
    $cn_nid_array = explode('/', $cn_url);
    $cn_nid = $cn_nid_array[1];
  }
  // Read the PHP code for the body.
  $cn_body_text = "";
  $cn_module_path = drupal_get_path('module','voterdb');
  $cn_suffix = ($cn_type=='php')?'php':'txt';
  $cn_call_file_name = $cn_module_path."/voterdb_".$cn_name.'.'.$cn_suffix;

  $cn_call_file_fh = fopen($cn_call_file_name,"r");
  do {
    $cn_php_line = fgets($cn_call_file_fh);
    if (!$cn_php_line) {break;}
    $cn_body_text .= $cn_php_line;
  } while (TRUE);
  fclose($cn_call_file_fh);
  if ($cn_nid == 0) {
    // Call List page does not exist, create one
    $cn_node = new stdClass();
    $cn_node->type = "page";
    node_object_prepare($cn_node);
    $cn_format = ($cn_type=='php')?'php_code':'full_html';
    $cn_node->title    = $cn_title;
    $cn_node->promote = ($cn_promote)?NODE_PROMOTED:NODE_NOT_PROMOTED;
    $cn_node->language = LANGUAGE_NONE;
    $cn_node->body[$cn_node->language][0]['value']   = $cn_body_text;
    $cn_node->body[$cn_node->language][0]['format']  = $cn_format;
    $cn_node->path = array('alias' => $cn_name);
    node_save($cn_node);
    $cn_nid = $cn_node->nid;
  } else {
    // The Call List page already exists, update it
    $cn_node = node_load($cn_nid,NULL,NULL);
    $cn_node->body[$cn_node->language][0]['value']   = $cn_body_text;
    node_save($cn_node);
  }
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_prepare_county
 *
 * Build a database of NLs. Read the file exported from MyCampaign and 
 * process the submitted fields.
 * 
 * @param type 
 * @return boolean - TRUE if no errors in upload.
 */
function voterdb_prepare_county($pc_file_name){
  $pc_county_fh = fopen($pc_file_name, "r");
  if ($pc_county_fh == FALSE) {
    voterdb_debug_msg("Failed to open the county name File",'');
    return FALSE;
  }
  $pc_counties = array();
  do {
    $pc_county_raw = fgets($pc_county_fh);
    if (!$pc_county_raw) {break;}  // Break out of DO loop at end of file.
    // Remove any stuff that might be a security risk.
    $pc_county_sanitized = sanitize_string($pc_county_raw);
    $pc_county_record = trim(preg_replace('/\s+/',' ', $pc_county_sanitized));
    $pc_county_info = explode(",", $pc_county_record);
    $pc_count = count($pc_county_info);
    if($pc_count < 2) {
      voterdb_debug_msg('county', $pc_county_raw);
      drupal_set_message('There must be at least one HD.','error');
      return FALSE;
    }
    // replace the blank with an underscore and remove any periods.
    $pc_rcounty = $pc_county_info[0];
    $pc_county = str_replace(array(' ','.'), array('_',''), $pc_rcounty);
    //$pc_county = str_replace(' ', '_', $pc_rcounty);
    $pc_hds = array();
    for ($pc_i=1;$pc_i<$pc_count;$pc_i++) {
      $pc_hd = $pc_county_info[$pc_i];
      $pc_hds[] = $pc_hd;
    }
    sort($pc_hds);
    $pc_counties[$pc_county] = $pc_hds;
  } while (TRUE);  
  ksort($pc_counties);
  fclose($pc_county_fh);
  return $pc_counties; 
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_county_enum
 * 
 * Create an enumeration type for MySQL from the associative array
 * of county names.
 * 
 * @param type $ce_county_defs
 * @return string
 */
function voterdb_county_enum($ce_county_defs) {
  $ce_enum = "ENUM(";
  $ce_counties = array_keys($ce_county_defs);
  $ce_first = TRUE;
  foreach ($ce_counties as $ce_county) {
    if ($ce_first) {
      $ce_first = FALSE;
    } else {
      $ce_enum .= ",";
    }
    $ce_enum .= "'".$ce_county."'";
  }
  $ce_enum .= ")";
  return $ce_enum;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_setup_form
 *
 * Warn the user that this function will rebuild the voterdb database
 * and lose all existing information.
 *
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_setup_form($form_id, &$form_state) {
  $form = array();
  $form['partipation'] = array (
    '#type' => 'markup',
    '#markup' => '<b>** WARNING **</b> This procedure rebuilds the database
      for the NLP program.
      All data associated with a prior election will be lost.  If you want
      to retain any data, please use the <b>voterdb_download_data</b> page to
      export and save the NL entered canvas results.'
  );
  $form['deletedatabase'] = array(
    '#type' => 'checkbox',
    '#title' =>
        t('By selecting this option you agree to rebuild the database.'),
    '#required' => TRUE,
  );
  // File with county names and HDs contained in county.
  $form['countynames'] = array(
    '#type' => 'file',
    '#title' => t('CSV with county names and list of HDs in the county.'),
    '#size' => 75,
    //'#required' => TRUE,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => 'Next >>'
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_setup_form_validate
 *
 * Verif that the use supplied a CSV file with the county names and associated
 * house districts.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_setup_form_validate($form, &$form_state) {
  $sv_name = $_FILES['files']['name']['countynames'];
  $sv_tmp_fn = $_FILES['files']['tmp_name']['countynames'];
  if (empty($sv_tmp_fn)) {
    form_set_error('countynames', 'A file is required.');
  }
    $sv_cname_txt = strtolower($sv_name);
    $sv_cname_txt_array = explode('.', $sv_cname_txt);
    $sv_ctype_txt = end($sv_cname_txt_array);
    if (!($sv_ctype_txt == 'txt' OR $sv_ctype_txt == 'csv' )) {
      form_set_error('countynames', 'The county name must be a txt or csv file.');
      return;
    }
  $sv_counties = voterdb_prepare_county($sv_tmp_fn);
  if(!$sv_counties) {
    form_set_error('countynames', 'Fix the county name file.');
    return;
  }
  $form_state['voterdb']['counties'] = $sv_counties;
  
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_setup_form_submit
 *
 * Delete any existing tables and build new ones.   All the field entries
 * will be built with appropriate attributes.  Then build the table with the
 * list of house districts for each supported county.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_setup_form_submit($form, &$form_state) {
  $fs_counties = $form_state['voterdb']['counties'];
  $output = '<p><span style="font-size:24px; color:#0033ff;
             font-family:trebuchet ms,helvetica,sans-serif;"> Setup</span></p>';
  $fs_enum = voterdb_county_enum($fs_counties);
  //Build the voterdb database tables.
  voterdb_build_tables($fs_enum,DB_TBLS_ARRAY,DB_FIELDS_ARRAY,'nlp_voterdb');
  // Build the tables in the Drupal database.
  voterdb_build_tables($fs_enum,DB_DRUPAL_TBLS_ARRAY,DB_DRUPAL_FIELDS_ARRAY,'default');
  // Initial values for static fields.
  voterdb_hd_build($fs_counties);
  voterdb_goals_build($fs_counties);
  // Create the nodes we use for navigation and display.
  voterdb_create_node(VO_CALLLIST_PAGE,'GOTV Call List','php',FALSE);
  voterdb_create_node(VO_MAILLIST_PAGE,'Post Card Mailing List','php',FALSE);
  voterdb_create_node(VO_ERROR_PAGE,'NLP Login','txt',FALSE);
  voterdb_create_front_page(VO_FRONT_PAGE, $fs_counties);
  voterdb_create_node(VO_FRONT_PAGE,'Neighborhood Leader Login','txt',TRUE);
  return $output;
}