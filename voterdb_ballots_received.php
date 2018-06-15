<?php
/*
 * Name: voterdb_ballots_received.php   V4.0 2/19/18
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */
require_once "voterdb_constants_bc_tbl.php";
require_once "voterdb_constants_mb_tbl.php";
require_once "voterdb_group.php";
require_once "voterdb_van_hdr.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_dates.php";
require_once "voterdb_class_button.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_matchback
 * 
 * Verify that we have a good header.
 *
 * @param type $vu_matchback_name - name of the file with matchback records.
 * @return field position array for required fields or FALSE if error.
 */
function voterdb_validate_matchback($vu_matchback_name) {
  $vu_voter_fh = fopen($vu_matchback_name, "r");
  if ($vu_voter_fh == FALSE) {
    drupal_set_message('Failed to open Matchback file', 'error');
    return FALSE;
  }
  $vu_voter_raw = fgets($vu_voter_fh);
  if (!$vu_voter_raw) {
    drupal_set_message('Failed to read Matchback File Header', 'error');
    return FALSE;
  }
  $vu_header_record = sanitize_string($vu_voter_raw);
  //extract the column headers.
  $vu_column_header = explode("\t", $vu_header_record);
  $vu_field_pos = voterdb_decode_header($vu_column_header, unserialize(MB_HEADER_ARRAY));
  if ($vu_column_header[$vu_field_pos[BR_VANID]] == MB_VANID) {
    drupal_set_message('Identifying Column name: '.MB_VANID, 'status');
  } elseif ($vu_column_header[$vu_field_pos[BR_VANID_ALT]] == MB_VANID_ALT) {
    drupal_set_message('Identifying Column name: '.MB_VANID_ALT, 'status');
    $vu_field_pos[BR_VANID] = $vu_field_pos[BR_VANID_ALT];  // Use the alt name.
  } else {
    drupal_set_message('Not a VAN export file', 'error');
    return FALSE;
  } 
  $vu_allreq = voterdb_export_required($vu_field_pos, unserialize(MB_REQUIRED_ARRAY), unserialize(MB_MESSAGE_ARRAY));
  if ($vu_allreq) {return FALSE;}  // One or more required fields are missing.
  $vu_state = variable_get('voterdb_state', 'Select');
  if ($vu_state == "Oregon") {
    if(empty($vu_field_pos[BR_PARTY])) {
      drupal_set_message('Party is missing', 'error');
    }
  }
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
  $fv_fname = $form_state['voterdb']['uri'];
  // Verify a name was set.
  $fv_field_pos = voterdb_validate_matchback($fv_fname);
  if (!$fv_field_pos) {
    form_set_error('matchbackfile', 'Fix the problem before resubmit.');
    return FALSE;
  }
  $form_state['voterdb']['matchback_name'] = $fv_fname;
  $form_state['voterdb']['field_pos'] = $fv_field_pos;
  return TRUE;
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
function voterdb_ballots_received_form($form_id, &$form_state) {
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['reenter'] = TRUE;
    if(!voterdb_get_group($form_state)) {return;}
  } 
  $br_button_obj = new NlpButton;
  $br_button_obj->setStyle();
  $br_county = $form_state['voterdb']['county'];
  $br_temp_dir = 'public://temp';
  // Create the temp directory if not already there.
  file_prepare_directory($br_temp_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
  // Ask for the ballot recieved file to upload.
  $form['hint'] = array(
      '#type' => 'markup',
      '#markup' => 'Hint: The upload will be faster if the file is sorted by VANID.  ',
  );
  // Name of the matchback file to upload.
  $form['matchbackfile'] = array(
      '#type' => 'managed_file',
      '#title' => t('Matchback file name'),
      '#description' => t('Select a Ballot Recieved file.<br>'),
      '#progress_message' => 'Uploading',
      '#upload_location' => $br_temp_dir,
      '#upload_validators' => array('file_validate_extensions' => array('txt'),),
  );
  // A submit button for the upload of voting results.
  $form['uploadfile'] = array(
      '#type' => 'submit',
      '#id' => 'upload-file',
      '#value' => t('Process the uploaded Ballot Received File >>'),
  );
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$br_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballots_received_form_validate
 * 
 * Validate the file submitted is a good export from the VAN.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_ballots_received_form_validate($form,&$form_state) {
  $rv_file = file_load($form_state['values']['matchbackfile']);
  $form_state['voterdb']['uri'] = $rv_file->uri;
  // Validate the file supplied.
  voterdb_validate_file($form_state);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballots_received_form_submit
 * 
 * Process the submitted VAN export to count voter participation and to
 * flag the NLP voters who have returned ballots.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_ballots_received_form_submit($form,&$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  $tc_mpath = drupal_get_path('module','voterdb');
  $tc_date_indexes = voterdb_get_brindexes();   // dates.
  // Setup the call to start a batch operation.
  $tc_args = array (
    'uri' => $form_state['voterdb']['uri'],
    'field_pos' => $form_state['voterdb']['field_pos'],
    'date_indexes' => $tc_date_indexes,
  );
  $tc_batch = array(
    'operations' => array(
      array('voterdb_ballots_received_upload', array($tc_args))
      ),
    'file' => $tc_mpath.'/voterdb_ballots_received_upload.php',
    'finished' => 'voterdb_ballots_received_finished',
    'title' => t('Processing ballot received upload.'), 
    'init_message' => t('Ballot received upload is starting.'), 
    'progress_message' => t('Processed @percentage % of ballots received file.'), 
    'error_message' => t('Ballot received upload has encountered an error.'),
  );
  batch_set($tc_batch);
}