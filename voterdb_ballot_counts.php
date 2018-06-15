<?php
/*
 * Name: voterdb_ballot_counts.php   V4.0 2/19/18
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */
require_once "voterdb_constants_bc_tbl.php"; // Ballot Count.
require_once "voterdb_constants_mb_tbl.php"; // Matchback.
require_once "voterdb_constants_van_tbl.php"; // VAN export structures.
//require_once "voterdb_constants_county_tbl.php";
require_once "voterdb_get_county_names.php";
require_once "voterdb_group.php";
require_once "voterdb_van_hdr.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hdr_record_check
 * 
 * Get a header record from the file and verify the required columns are 
 * present.
 * 
 * @param type $rc_cnt_fh
 * @param type $rc_header_array
 * @param type $rc_required_array
 * @param type $rc_message_array
 * @return associate array - array of fields for the upload with the column
 *                           where information is located.
 */
function voterdb_hdr_record_check($rc_cnt_fh,$rc_header_array, $rc_required_array, $rc_message_array) {
  $rc_header_raw = fgets($rc_cnt_fh);
  if (!$rc_header_raw) {
    drupal_set_message('Failed to read VAN count export header.', 'error');
    return FALSE;
  }
  $rc_header_record = trim($rc_header_raw,"\n\r");
  //extract the column headers.
  $rc_column_header = explode("\t", $rc_header_record);
  $rc_field_pos = voterdb_decode_header($rc_column_header, unserialize($rc_header_array));
  $rc_allreq = voterdb_export_required($rc_field_pos, unserialize($rc_required_array), unserialize($rc_message_array));
  if ($rc_allreq) {return FALSE;}   // One or more required fields are missing.
  return $rc_field_pos;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_ballotcnt
 * 
 * Verify that we have a good header.  The export of a cross tab report has
 * two lines.  So, two records are read and both are used to find the needed
 * columns.
 *
 * @param type $vu_ballotcnt_name - name of the file with ballotcnt records.
 * @return field position array for required fields or FALSE if error.
 */
function voterdb_validate_ballotcnt($vu_ballotcnt_name) {
  $vu_cnt_fh = fopen($vu_ballotcnt_name, "r");
  if ($vu_cnt_fh == FALSE) {
    drupal_set_message('Failed to open count export file', 'error');
    return FALSE;
  }
  $vu_field_pos = voterdb_hdr_record_check($vu_cnt_fh,VC1_HEADER_ARRAY, VC1_REQUIRED_ARRAY, VC1_MESSAGE_ARRAY);
  if(!$vu_field_pos) {return FALSE;}
  $vu_field_pos2 = voterdb_hdr_record_check($vu_cnt_fh,VC2_HEADER_ARRAY, VC2_REQUIRED_ARRAY, VC2_MESSAGE_ARRAY);
  if(!$vu_field_pos2) {return FALSE;}
  //Pick up the column for the ballot return date from the second hdr.
  $vu_field_pos[VI_BALRET] = $vu_field_pos2[VI2_BALRET];
  return $vu_field_pos;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_file
 * 
 * Verify that the user provided a file name and that it is a VAN export with
 * the required fields.
 * 
 * @param type &$form_state
 * @return boolean - TRUE if successful, and the file name and field positions
 *                   are retained.
 */
function voterdb_validate_file(&$form_state) {
  // Verify a name was set
  if (!isset($_FILES['files']['name']['ballotcntfile'])) {
    form_set_error('ballotcntfile', 'A ballotcnt file is required');
    return FALSE;
    }
  // Verify we have a good VAN export file with the needed fields
  $fv_tmp_name = $_FILES['files']['tmp_name']['ballotcntfile'];
  $fv_field_pos = voterdb_validate_ballotcnt($fv_tmp_name);
  if (!$fv_field_pos) {
    form_set_error('ballotcntfile', 'Fix the problem before resubmit.');
    return FALSE;
  }
  $form_state['voterdb']['ballotcnt_name'] = $fv_tmp_name;
  $form_state['voterdb']['field_pos'] = $fv_field_pos;
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_initialize_counts
 * 
 * Set all the counters to zero.  There is a counter for each county/party
 * combination.
 * 
 * @return int
 */
function voterdb_initialize_counts() {
  $ic_counties = voterdb_get_county_names();
  $ic_party_ids = unserialize(VP_PARTY_CODE_ARRAY);
  $ic_parties[] = 'ALL';
  foreach ($ic_party_ids as $ic_party_id) {
    $ic_parties[] = $ic_party_id;
  }
  foreach ($ic_counties as $ic_county_name) {
    foreach ($ic_parties as $ic_party) {
      if ($ic_party!='') {
        $ic_cnts[$ic_county_name][$ic_party]['br'] = 
            $ic_cnts[$ic_county_name][$ic_party]['reg'] = 0;
      }
    }
  }
  return $ic_cnts;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_write_ballotcnt
 * 
 * A ballot count record is updsted in the database.   The merge function 
 * is used because the record may already exist.   The upload fo the 
 * crosstab file is done every morning to refresh counts.
 * 
 * @param type $wb_cnts
 * @return type
 */
function voterdb_write_ballotcnt($wb_cnts) {
  db_set_active('nlp_voterdb');
  db_merge(DB_BALLOTCOUNT_TBL)
    ->key(array(
      BC_COUNTY => $wb_cnts[BC_COUNTY],
      BC_PARTY => $wb_cnts[BC_PARTY]))
    ->fields(array(
      BC_REG_VOTERS => $wb_cnts[BC_REG_VOTERS],
      BC_REG_VOTED => $wb_cnts[BC_REG_VOTED]))
    ->execute();
  db_set_active('default');
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_upload_ballotcnt
 * 
 * Read the provided file and count the voters and those voting.  Counts are 
 * maintained by party for each county in the upload.
 * 
 * @param type $form_state
 * @return array of counts of voters and ballots received, or FALSE if error.
 */
function voterdb_upload_ballotcnt($form_state) {
  // Retrieve the values determined when we validated the form submittal
  $ub_ballotcnt_name = $form_state['voterdb']['ballotcnt_name'];
  $ub_field_pos = $form_state['voterdb']['field_pos'];
  $ub_counts_fh = fopen($ub_ballotcnt_name, "r");
  if (!$ub_counts_fh) {
    drupal_set_message('Failed to open Matchback file', 'error');
    return FALSE;
  }
  // Discard the header records.  There are two for the counts export.
  fgets($ub_counts_fh);
  $ub_counts_raw = fgets($ub_counts_fh);
  if (!$ub_counts_raw) {
    drupal_set_message('Failed to read Matchback File Header', 'error');
    return FALSE;
  }
  // Get the previous ballotcnt date if it exists.
  $ub_cnt = voterdb_initialize_counts();
  $ub_party_names = unserialize(VP_PARTY_NAME_ARRAY);
  $ub_party_codes = unserialize(VP_PARTY_CODE_ARRAY);
  $ub_i = 0;
  foreach ($ub_party_names as $ub_party_name) {
    $ub_party_id[$ub_party_name] = $ub_party_codes[$ub_i++];
  }
  do {
    $ub_counts_raw = fgets($ub_counts_fh);
    if (!$ub_counts_raw) {break;} //We've processed the last count.
    $ub_counts_record = str_replace(array(",",'"'), "",$ub_counts_raw );
    // Parse the count record into the various fields
    $ub_counts_info = explode("\t", $ub_counts_record);
    // Get the county name, party, and counts.
    $ub_county_raw = trim($ub_counts_info[VI_COUNTY]);
    $ub_county = ($ub_county_raw == "Hood River")? "Hood_River": $ub_county_raw;
    if ($ub_county != 'Total People' AND isset($ub_cnt[$ub_county])) {
      $ub_party = $ub_counts_info[$ub_field_pos[VI_PARTY]];
      $ub_party_code = $ub_party_id[$ub_party];
      $ub_br = $ub_counts_info[$ub_field_pos[VI_BALRET]];
      $ub_reg = $ub_counts_info[$ub_field_pos[VI_TOTAL]];
      // Record the numbers for a party.
      $ub_cnt[$ub_county][$ub_party_code]['br'] = $ub_br;
      $ub_cnt[$ub_county][$ub_party_code]['reg'] = $ub_reg;
      // Sum the party numbers for the county.
      if (!isset($ub_cnt[$ub_county]['ALL'])) {
        $ub_cnt[$ub_county]['ALL']['br'] = 
        $ub_cnt[$ub_county]['ALL']['reg'] = 0;
      }
      $ub_cnt[$ub_county]['ALL']['br'] += $ub_br;
      $ub_cnt[$ub_county]['ALL']['reg'] += $ub_reg;
    }
  } while (TRUE);
  foreach ($ub_cnt as $ub_county => $ub_county_values) {
    foreach ($ub_county_values as $ub_party => $ub_party_cnts) {
      $ub_cnts[BC_COUNTY] = $ub_county;
      $ub_cnts[BC_PARTY] = $ub_party;
      $ub_cnts[BC_REG_VOTERS] = $ub_party_cnts['reg'];
      $ub_cnts[BC_REG_VOTED] = $ub_party_cnts['br'];
      voterdb_write_ballotcnt($ub_cnts);
    }
  }
  return $ub_cnt;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballots_received_form
 * 
 * Create the form for uploading voter ballot status.
 * 
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_ballot_counts_form($form_id, &$form_state) {
  $bc_button_obj = new NlpButton;
  $bc_button_obj->setStyle();
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['reenter'] = TRUE;
  }
  $bc_county = $form_state['voterdb']['county'];
  // Create the form to display of all the NLs
  $bc_banner = voterdb_build_banner ($bc_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $bc_banner
  ); 
   $form['hint'] = array(
     '#type' => 'markup',
     '#markup' => '<div style="width:500px;">The counts file is a cross tab report that counts ballots '
       . 'recieved for each party by county. The list of voters used to create '
       . 'the cross tab report should be of '
       . 'just active registered voters and the cross tab file is for the '
       . 'entire state.</div>',
  ); 
  // Name of the ballotcnt file to upload
  $form['ballotcntfile'] = array(
      '#type' => 'file',
      '#title' => t('Counts file name'),
  );
  // A submit button for the first or only part of an upload of voting results.
  $bc_title = 'Upload the next Crosstab and Counts >>';
  $form['uploadfile'] = array(
      '#type' => 'submit',
      '#id' => 'upload-file',
      '#value' => $bc_title,
  );
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$bc_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballot_counts_form_validate
 * 
 * Validate the file submitted is a good export from the VAN.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_ballot_counts_form_validate($form,&$form_state) {
  voterdb_validate_file($form_state);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballot_counts_form_submit
 * 
 * Process the submitted VAN export to count voter participation and to
 * flag the NLP voters who have returned ballots.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_ballot_counts_form_submit($form,&$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist.
  $tc_cnts = voterdb_upload_ballotcnt($form_state);
  if (!$tc_cnts) {return;}
  drupal_set_message('The ballotcnt file has been successfully uploaded.', 'status');
}