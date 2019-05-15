<?php
/*
 * Name: voterdb_import_matchbacks.php   V4.3 7/27/18
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */

require_once "voterdb_group.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_matchback.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpMatchback;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_ballots_received_form
 * 
 * Create the form for uploading voter ballot status.
 * 
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_import_matchbacks_form($form_id, &$form_state) {
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['reenter'] = TRUE;
    if(!voterdb_get_group($form_state)) {return;}
  } 
  $br_button_obj = new NlpButton();
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
      '#value' => t('Process the uploaded Matchback File >>'),
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
function voterdb_import_matchbacks_form_validate($form,&$form_state) {
  $rv_file = file_load($form_state['values']['matchbackfile']);
  $form_state['voterdb']['uri'] = $rv_file->uri;
  // Validate the file supplied.
  
  $matchbackObj = new NlpMatchback();
  $rv_voter_fh = fopen($rv_file->uri, "r");
  if ($rv_voter_fh == FALSE) {
    drupal_set_message('Failed to open Matchback file', 'error');
    return FALSE;
  }
  $rv_voter_raw = fgets($rv_voter_fh);
  if (!$rv_voter_raw) {
    drupal_set_message('Failed to read Matchback File Header', 'error');
    return FALSE;
  }
  $rv_header_record = sanitize_string($rv_voter_raw);
  //extract the column headers.
  $rv_column_header = explode("\t", $rv_header_record);
  
  $rv_field_pos = $matchbackObj->decodeMatchbackHdr($rv_column_header);
  //voterdb_debug_msg('pos', $rv_field_pos);
  if(!$rv_field_pos['ok']) {
    foreach ($rv_field_pos['err'] as $errMsg) {
      drupal_set_message($errMsg,'warning');
    }
    form_set_error('upload', 'Fix the problem before resubmit.');
    return FALSE;
  }
  
  
  $form_state['voterdb']['matchback_name'] = $rv_file->uri;
  $form_state['voterdb']['field_pos'] = $rv_field_pos['pos'];
  //$form_state['voterdb']['dates'] = $matchbackObj->getBrDates();
  
  
  //voterdb_validate_file($form_state);
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
function voterdb_import_matchbacks_form_submit($form,&$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  $tc_mpath = drupal_get_path('module','voterdb');

  // Setup the call to start a batch operation.
  $tc_args = array (
    'uri' => $form_state['voterdb']['uri'],
    'field_pos' => $form_state['voterdb']['field_pos'],
    //'date_indexes' => $form_state['voterdb']['dates'],
  );
  $tc_batch = array(
    'operations' => array(
      array('voterdb_import_matchbacks_upload', array($tc_args))
      ),
    'file' => $tc_mpath.'/voterdb_import_matchbacks_upload.php',
    'finished' => 'voterdb_import_matchbacks_finished',
    'title' => t('Processing import_matchbacks upload.'), 
    'init_message' => t('Matchback import is starting.'), 
    'progress_message' => t('Processed @percentage % of ballots received file.'), 
    'error_message' => t('Import_matchbacks has encountered an error.'),
  );
  batch_set($tc_batch);
}