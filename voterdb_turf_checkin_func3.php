<?php
/*
 * Name: voterdb_turf_checkin_func3.php   V4.1  5/30/18
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */
/*
 * voterdb_validate_turf_pct, voterdb_validate_turf_hdr, 
 * voterdb_get_voters, 
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_turf_pct
 *
 * Verify that we have a turf with voters from just one precinct.
 *
 * @param type $tp_voters - array of voters in the new turf.
 * @return boolean - array with precinct and HD number.  Precinct number will
 *                   be an empty string if there are more than one precincts
 *                   in the list of voters.  The count of voters is also returned.
 */
function voterdb_validate_turf_pct($tp_voters) {
  $tp_turf_hd = $tp_turf_pct = '';
  foreach ($tp_voters as $tp_voter) {
    $tp_voter_pct = $tp_voter[VN_PCT]; 
    if ($tp_turf_pct != $tp_voter_pct) {
      if ($tp_turf_pct == '') {  // First voter.
        $tp_turf_pct = $tp_voter_pct;  // Save he first pct number.
        $tp_turf_hd = $tp_voter[VN_HD];
      } else { // missmatched precinct.
        $tp_turf_pct = '';
        break; 
      }
    }
  }
  // Return the precinct and HD for this turf.
  $vu_leg_districts[0] = $tp_turf_hd;
  $vu_leg_districts[1] = $tp_turf_pct;
  $vu_leg_districts[2] = count($tp_voters);
  return $vu_leg_districts;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_add_voting_record
 * 
 * For this election cycle, the admin configured the most recent election to
 * use to construct a voting history.   The history is just the previous four
 * elections: two primary and two general.   For a primary, the four elections 
 * are the previous two general and the associated primary.   For the general,
 * we start with the most resent primary.   This function creates the order
 * so we can find the voting record in the VoteBuilder export.
 * 
 * During configuration, a reguired voting history is specified.  From that
 * we look back three additional elections.   Also, an option election can 
 * be specified and it is used as the starting point if it is present in the 
 * export.   It can happen the the desired optional election is not updated
 * in VoteBuilder in time for creating and sending out turfs.
 * 
 * @param type $ar_column_header
 * @param type $ar_header_fields
 * @return type - updated column headers with the headers for the 
 *                 voting history we want.
 */
function voterdb_add_voting_record($ar_column_header,&$ar_header_fields) {
  $ar_header_column = array_flip($ar_column_header);
  $ar_required_record = variable_get('voterdb_required_voting_record', '');
  $ar_optional_record = variable_get('voterdb_optional_voting_record', ''); 
  // Check if the optional voting record exists, and use it.  Else, use the
  // required record.
  if(isset($ar_header_column[$ar_optional_record['name']])) {
    $ar_year = $ar_optional_record['year'];
    $ar_type = $ar_optional_record['type'];
  } else {
    $ar_year = $ar_required_record['year'];
    $ar_type = $ar_required_record['type'];
  }
  // If the starting point is a general election, look back two years for the 
  // previous election.
  if($ar_type == "General") {
    $ar_pyear = $ar_year-2;
    $ar_header_fields[VR_G1] = "General".$ar_year;
    $ar_header_fields[VR_G2] = "General".$ar_pyear;
    $ar_header_fields[VR_P1] = "Primary".$ar_year;
    $ar_header_fields[VR_P2] = "Primary".$ar_pyear;
    $ar_display_order = array(
      'G'.$ar_year => array('vote' => VR_G1),
      'P'.$ar_year => array('vote' => VR_P1),
      'G'.$ar_pyear => array('vote' => VR_G2),
      'P'.$ar_pyear => array('vote' => VR_P2),
    );
  } else {
    // Otherwise we are looking at the primary and have different records to get.
    $ar_pyear = $ar_year-2;
    $ar_lyear = $ar_year-4;
    $ar_header_fields[VR_G1] = "General".$ar_pyear;
    $ar_header_fields[VR_G2] = "General".$ar_lyear;
    $ar_header_fields[VR_P1] = "Primary".$ar_year;
    $ar_header_fields[VR_P2] = "Primary".$ar_pyear;
    $ar_display_order = array(
      'P'.$ar_year => array('vote' => VR_P1),
      'G'.$ar_pyear => array('vote' => VR_G1),
      'P'.$ar_pyear => array('vote' => VR_P2),
      'G'.$ar_lyear => array('vote' => VR_G2),
    );
  }
  return $ar_display_order;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_turf_hdr
 *
 * Verify we have a valid turf export. Return the array to associate
 * columns in the VAN export with the fields we want.
 *
 * @param type $vu_hdr_raw - the raw header as read from the file.
 * @return boolean - TRUE if the header is good.
 */
function voterdb_validate_turf_hdr($vu_hdr_raw,&$vu_display_order) {
  // Verify that we have a header record, that it is a VAN export, and
  // it has the needed fields
  $vu_header_record = sanitize_string($vu_hdr_raw);
  //extract the column headers
  $vu_column_header = explode("\t", $vu_header_record);
  $vu_header_fields = unserialize(VH_HEADER_ARRAY);
  // Update the fields we look for to include the latest voting records.
  $vu_display_order = voterdb_add_voting_record($vu_column_header,$vu_header_fields);
  $vu_field_pos = voterdb_decode_header($vu_column_header,$vu_header_fields);  // van_hdr.
  // VoterBuilder changed the name of the column.  We tolerate the legacy name for older exports.
  if ($vu_column_header[$vu_field_pos[VR_VANID_ALT]] == VH_VANID_ALT) {
    $vu_field_pos[VR_VANID] = $vu_field_pos[VR_VANID_ALT];  // Use the alt name.
  } elseif (!$vu_column_header[$vu_field_pos[VR_VANID]] == VH_VANID) {
    drupal_set_message('Not a VAN export file', 'error');
    return FALSE;
  }
  if (voterdb_export_required($vu_field_pos, unserialize(VH_REQUIRED_ARRAY), unserialize(VH_MESSAGE_ARRAY)))
    {return FALSE;}   // One or more required fields are missing
  return $vu_field_pos;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_voters 
 * 
 * Read the VAN export file containing the turf and build a temporary voter
 * array.  The header has already been read and processed.
 * 
 * @param type $ti_voter_fh - file handle of the input file.
 * @param type $form_state.
 */
function voterdb_get_voters($ti_voter_fh,&$form_state) {
  $ti_field_pos = $form_state['voterdb']['field_pos'];
  $ti_voter = array();
  $ti_voters = array();
  $ti_county = $form_state['voterdb']['county'];
  $ti_state = variable_get('voterdb_state','');
  $ti_vcount = 0;
  do {
    $ti_voter_raw = fgets($ti_voter_fh);
    if (!$ti_voter_raw) {break;}  // We've processed the last voter.
    //voterdb_debug_msg('raw voter', $ti_voter_raw, __FILE__, __LINE__);
    
    // remove any stuff that might be a security risk.
    $ti_voter_record = sanitize_string($ti_voter_raw);
    
    // Parse the voter record into the various fields.
    $ti_voter_info = explode("\t", $ti_voter_record);
    // Extracted the needed info from the voter record.
    $ti_vanid = $ti_voter_info[$ti_field_pos[VR_VANID]];
    $ti_voter[VN_VANID] = $ti_vanid;
    // Protect apostrophies in the name.
    $ti_voter[VN_LASTNAME] = str_replace("'", "&#039;", $ti_voter_info[$ti_field_pos[VR_LNAME]]);
    $ti_voter[VN_FIRSTNAME] = str_replace("'", "&#039;", $ti_voter_info[$ti_field_pos[VR_FNAME]]);
    // If the nickname field exists and it has a value for this voter, use it.
    $ti_nickname = NULL;
    if(!empty($ti_field_pos[VR_NICKNAME])) {
      if(!empty($ti_voter_info[$ti_field_pos[VR_NICKNAME]])) {
        $ti_nickname = str_replace("'", "&#039;", $ti_voter_info[$ti_field_pos[VR_NICKNAME]]);
      }
    }
    // Oregon, check if the saluatation is still in use as a nickname.  
    if($ti_state == 'Oregon') {
      if(!empty($ti_nickname)) {
        if(!empty($ti_field_pos[VR_SALUTATION])) {
          if(!empty($ti_voter_info[$ti_field_pos[VR_SALUTATION]])) {
            $ti_nickname = str_replace("'", "&#039;", $ti_voter_info[$ti_field_pos[VR_SALUTATION]]);
          }
        }
      }
    }
    if(empty($ti_nickname)) {
      $ti_nickname = $ti_voter[VN_FIRSTNAME];
    }
    $ti_voter[VN_NICKNAME] = $ti_nickname;

    $ti_voter[VN_HD] = ltrim($ti_voter_info[$ti_field_pos[VR_HD]],'0');
    // For Oregon, remove the county from the precinct name.
    if($ti_state == 'Oregon') {
      $ti_voter_Pct = $ti_voter_info[$ti_field_pos[VR_PCT]];
      $ti_pct_parts = explode('-', $ti_voter_Pct);
      $ti_pct_name =  $ti_pct_parts[1];
    } else {
      $ti_pct_name = str_replace(' ', '', $ti_field_pos[VR_PCT]); // Remove blanks.
    }
    $ti_voter[VN_PCT] = $ti_pct_name;
    $ti_voter[VN_CD] = $ti_voter_info[$ti_field_pos[VR_CD]];
    // Pick the voting records for the relevant cycles.   The fields may not
    // exist if they are at the end of the record.
    $ti_display_order = $form_state['voterdb']['display-order'];
    // For each of the four elections, construct a display with both the
    // status of voting and the party at the time.
    $ti_voting = '';
    foreach ($ti_display_order as $ti_cycle => $ti_type) {
      $ti_vfield = $ti_type['vote'];
      $ti_vote = (isset($ti_voter_info[$ti_field_pos[$ti_vfield]])) ?
        $ti_voter_info[$ti_field_pos[$ti_vfield]]: 'N';
      if (empty($ti_vote)) {
        $ti_vote = 'N';
      }
      $ti_voting .= $ti_cycle.':'.$ti_vote.' ';
    }
    // Pick up the date of registration and convert to ISO.
    $ti_date_reg = $ti_date_current = NULL;
    if (isset($ti_voter_info[$ti_field_pos[VR_DATEREG]])){
      $ti_cdate = date_create($ti_voter_info[$ti_field_pos[VR_DATEREG]]);
      $ti_date_reg = date_format($ti_cdate, 'Y-m-d');
    }
    if($ti_field_pos[VR_DORCURRENT]!=0 AND isset($ti_voter_info[$ti_field_pos[VR_DORCURRENT]])) {
      $ti_cdate = date_create($ti_voter_info[$ti_field_pos[VR_DORCURRENT]]);
      $ti_date_current = date_format($ti_cdate, 'Y-m-d');
    } 
    $ti_party = NULL;
    if(!empty($ti_field_pos[VR_PARTY]) AND isset($ti_voter_info[$ti_field_pos[VR_PARTY]])) {
      $ti_party = $ti_voter_info[$ti_field_pos[VR_PARTY]];
    } 
    
    $ti_homephone = empty($ti_voter_info[$ti_field_pos[VR_HOMEPHONE]])?NULL:$ti_voter_info[$ti_field_pos[VR_HOMEPHONE]];
    $ti_cellphone = empty($ti_voter_info[$ti_field_pos[VR_CELLPHONE]])?NULL:$ti_voter_info[$ti_field_pos[VR_CELLPHONE]];
    // Protect against an empty county field and the space in Hood River.
    $ti_vcounty = empty($ti_voter_info[$ti_field_pos[VR_COUNTY]])?$ti_county:
                  $ti_voter_info[$ti_field_pos[VR_COUNTY]];
    if($ti_vcounty == 'Hood River') {
      $ti_vcounty = 'Hood_River';
    }
    // Build the array for this voter.
    $ti_voter[VN_AGE] = $ti_voter_info[$ti_field_pos[VR_AGE]];
    $ti_voter[VN_STREETNO] = $ti_voter_info[$ti_field_pos[VR_STREETNO]];
    $ti_voter[VN_STREETPREFIX] = $ti_voter_info[$ti_field_pos[VR_STREETPREFIX]];
    $ti_voter[VN_STREETNAME] = $ti_voter_info[$ti_field_pos[VR_STREETNAME]];
    $ti_voter[VN_STREETTYPE] = $ti_voter_info[$ti_field_pos[VR_STREETTYPE]];
    $ti_voter[VN_CITY] = $ti_voter_info[$ti_field_pos[VR_CITY]];
    $ti_voter[VN_COUNTY] = $ti_vcounty;
    $ti_voter[VN_HOMEPHONE] = $ti_homephone;
    $ti_voter[VN_CELLPHONE] = $ti_cellphone;
    $ti_voter[VN_APTTYPE] = $ti_voter_info[$ti_field_pos[VR_APTTYPE]];      
    $ti_voter[VN_APTNO] = $ti_voter_info[$ti_field_pos[VR_APTNO]];
    $ti_voter[VN_MADDRESS] = $ti_voter_info[$ti_field_pos[VR_MADDRESS]];
    $ti_voter[VN_MCITY] = $ti_voter_info[$ti_field_pos[VR_MCITY]];
    $ti_voter[VN_MSTATE] = $ti_voter_info[$ti_field_pos[VR_MSTATE]];      
    $ti_voter[VN_MZIP] = $ti_voter_info[$ti_field_pos[VR_MZIP]];
    $ti_voter[VN_VOTING] = $ti_voting;
    $ti_voter[VN_DATEREG] = $ti_date_reg;
    $ti_voter[VN_DORCURRENT] = $ti_date_current;
    $ti_voter[VN_PARTY] = $ti_party;
    $ti_voters[$ti_vanid] = $ti_voter;
    $ti_vcount++;
  } while (TRUE);
  $form_state['voterdb']['voter_count'] = $ti_vcount;
  return $ti_voters;
}