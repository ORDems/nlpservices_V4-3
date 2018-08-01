<?php
/*
 * Name: voterdb_crosstabs_and_counts.php   V4.3 7/31/18
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */

require_once "voterdb_group.php";
require_once "voterdb_van_hdr.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_crosstabs_and_counts.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpMatchback;
use Drupal\voterdb\NlpCrosstabCounts;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hdr_record
 * 
 * Get a header record from the file and verify the required columns are 
 * present.
 * 
 * @param type $rc_cnt_fh
 * 
 * @return associate array - array of fields.
 */
function voterdb_hdr_record($rc_cnt_fh) {
  $rc_header_raw = fgets($rc_cnt_fh);
  if (!$rc_header_raw) {
    return FALSE;
  }
  $rc_header_record = trim($rc_header_raw,"\n\r");
  $rc_column_header = explode("\t", $rc_header_record);
  return $rc_column_header;
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
  $vf_tmp_name = $_FILES['files']['tmp_name']['ballotcntfile'];
  
  $vf_cnt_fh = fopen($vf_tmp_name, "r");
  if ($vf_cnt_fh == FALSE) {
    drupal_set_message('Failed to open count export file', 'error');
    return FALSE;
  }
  $vf_header1 = voterdb_hdr_record($vf_cnt_fh);
  if (empty($vf_header1)) {
    drupal_set_message('Failed to read VAN count export header.', 'error');
    return FALSE;
  }
  //voterdb_debug_msg('hdr1', $vf_header1);
  $vf_header2 = voterdb_hdr_record($vf_cnt_fh);
  if (empty($vf_header2)) {
    drupal_set_message('Failed to read VAN count export header.', 'error');
    return FALSE;
  }
  //voterdb_debug_msg('hdr2', $vf_header2);
  $crosstabCountObj = new NlpCrosstabCounts();
  $pos = $crosstabCountObj->decodeCrosstabCountHdr($vf_header1,$vf_header2);
  //voterdb_debug_msg('pos', $pos);
  $vf_field_pos = $pos['pos'];
  if (!empty($pos['err'])) {
    form_set_error('ballotcntfile', 'Fix the problem before resubmit.');
    return FALSE;
  }
  $form_state['voterdb']['ballotcnt_name'] = $vf_tmp_name;
  $form_state['voterdb']['field_pos'] = $vf_field_pos;
  return TRUE;
}



/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_upload_demographics
 * 
 * Read the provided file and count the voters and those voting.  Counts are 
 * maintained by party for each county in the upload.
 * 
 * @param type $form_state
 * @return array of counts of voters and ballots received, or FALSE if error.
 */
function voterdb_upload_demographics($form_state) {
  // Retrieve the values determined when we validated the form submittal
  $ub_ballotcnt_name = $form_state['voterdb']['ballotcnt_name'];
  $ub_field_pos = $form_state['voterdb']['field_pos'];
  $ub_counts_fh = fopen($ub_ballotcnt_name, "r");
  if (!$ub_counts_fh) {
    drupal_set_message('Failed to open crosstab file', 'error');
    return FALSE;
  }
  // Discard the header records.  There are two for the counts export.
  fgets($ub_counts_fh);
  $ub_counts_raw = fgets($ub_counts_fh);
  if (!$ub_counts_raw) {
    drupal_set_message('Failed to read crosstab File Header', 'error');
    return FALSE;
  }

  do {
    $ub_counts_raw = fgets($ub_counts_fh);
    if (!$ub_counts_raw) {break;} //We've processed the last count.
    $ub_counts_record = str_replace(array(",",'"'), "",$ub_counts_raw );
    // Parse the count record into the various fields
    $ub_counts_info = explode("\t", $ub_counts_record);
    // Get the county name, party, and counts.
    $ub_county_raw = trim($ub_counts_info[$ub_field_pos['county']]);
    $ub_county = ($ub_county_raw == "Hood River")? "Hood_River": $ub_county_raw;
    if ($ub_county != 'Total People') {
      $ub_party = $ub_counts_info[$ub_field_pos['party']];

      $ub_br = $ub_counts_info[$ub_field_pos['balRet']];
      $ub_reg = $ub_counts_info[$ub_field_pos['total']];
      // Record the numbers for a party.
      $ub_cnt[$ub_county][$ub_party]['br'] = $ub_br;
      $ub_cnt[$ub_county][$ub_party]['reg'] = $ub_reg;
      // Sum the party numbers for the county.
      if (!isset($ub_cnt[$ub_county]['ALL'])) {
        $ub_cnt[$ub_county]['ALL']['br'] = 
        $ub_cnt[$ub_county]['ALL']['reg'] = 0;
      }
      $ub_cnt[$ub_county]['ALL']['br'] += $ub_br;
      $ub_cnt[$ub_county]['ALL']['reg'] += $ub_reg;
    }
  } while (TRUE);
  
  $crosstabObj = new NlpCrosstabCounts();
  foreach ($ub_cnt as $ub_county => $ub_county_values) {
    foreach ($ub_county_values as $ub_party => $ub_party_cnts) {

      $cnts = array(
        'county' => $ub_county,
        'party' => $ub_party,
        'regVoters' => $ub_party_cnts['reg'],
        'regVoted' =>  $ub_party_cnts['br'],
      );
      $crosstabObj->updateCrosstabCount($cnts);
      
    }
  }
  return $ub_cnt;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_crosstabs_and_counts_form
 * 
 * Create the form for uploading voter ballot status.
 * 
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_crosstabs_and_counts_form($form_id, &$form_state) {
  $bc_button_obj = new NlpButton();
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
     '#markup' => '<div style="width:500px;">The Demographics file is a cross tab report that counts ballots '
       . 'recieved for each party by county. The list of voters used to create '
       . 'the cross tab report should be of '
       . 'just active registered voters and the cross tab file is for the '
       . 'entire state.</div>',
  ); 
  // Name of the ballotcnt file to upload
  $form['ballotcntfile'] = array(
      '#type' => 'file',
      '#title' => t('Demographics file name'),
  );
  // A submit button for the first or only part of an upload of voting results.
  $bc_title = 'Import the Demographics file >>';
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
 * voterdb_crosstabs_and_counts_form_validate
 * 
 * Validate the file submitted is a good export from the VAN.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_crosstabs_and_counts_form_validate($form,&$form_state) {
  voterdb_validate_file($form_state);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_crosstabs_and_counts_form_submit
 * 
 * Process the submitted VAN export to count voter participation and to
 * flag the NLP voters who have returned ballots.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_crosstabs_and_counts_form_submit($form,&$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist.
  $tc_cnts = voterdb_upload_demographics($form_state);
  if (!$tc_cnts) {return;}
  drupal_set_message('The ballotcnt file has been successfully uploaded.', 'status');
}