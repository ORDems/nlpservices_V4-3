<?php
/*
 * Name: voterdb_restore_nlsreports.php   V4.2   7/16/18
 * This include file contains the code to restore voter contact reports by
 * NLs in previous elections.  It creates the database for historical results
 * that might be of value for this election.
 */
//require_once "voterdb_constants_rr_tbl.php"; 
//require_once "voterdb_constants_nls_tbl.php"; 
require_once "voterdb_group.php";
require_once "voterdb_banner.php";
require_once "voterdb_debug.php";
//require_once "voterdb_van_hdr.php";
require_once "voterdb_class_button.php";

use Drupal\voterdb\NlpButton;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_nlreports
 * 
 * Verify that we have a good header with required column names.
 *
 * @param type $vu_nlreports_name - name of the file with matchback records.
 * @return field position array for required fields or FALSE if error.
 */
function voterdb_validate_nlreports($vu_nlreports_name,$vu_delimiter) {
  $vu_voter_fh = fopen($vu_nlreports_name, "r");
  if ($vu_voter_fh == FALSE) {
    drupal_set_message('Failed to open NL reports file', 'error');
    return FALSE;
  }
  $vu_voter_raw = fgets($vu_voter_fh);
  if (!$vu_voter_raw) {
    drupal_set_message('Failed to read NL reports header', 'error');
    return FALSE;
  }
  $vu_header_record1 = sanitize_string($vu_voter_raw);
  $vu_header_record = html_entity_decode($vu_header_record1);
  //extract the column headers.
  if($vu_delimiter == ',') {
    $vu_column_header = str_getcsv($vu_header_record,",",'"');
  } else {
    $vu_column_header = explode($vu_delimiter, $vu_header_record);
  }
  // Find the column associated with each required field.
  $vu_field_pos = voterdb_decode_header($vu_column_header, unserialize(NU_HEADER_ARRAY)); // van_hdr
  if ($vu_column_header[$vu_field_pos[NU_VANID]] != NC_VANID) {
    drupal_set_message('VANID is missing.', 'error');
    return FALSE;
    }
  $vu_allreq = voterdb_export_required($vu_field_pos, unserialize(NU_REQUIRED_ARRAY), unserialize(NU_MESSAGE_ARRAY));
  if ($vu_allreq) {
    return FALSE;}  // One or more required fields are missing.
  return $vu_field_pos;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_validate_file
 * 
 * Verify that the user provided a file name and that it has
 * the required fields.
 * 
 * @param type &$form_state
 * @return boolean - TRUE if successful, and the file name and field positions
 *                   are retained.
 */
function voterdb_validate_file(&$form_state) {
  // Verify a name was set.
  $fv_fname = $form_state['voterdb']['uri'];
  // Check if the restore is from a file created by MySQL or NLP Services.
  $fv_tname_txt = strtolower($fv_fname);
  $fv_tname_txt_array = explode('.', $fv_tname_txt);
  $fv_ftype_txt = end($fv_tname_txt_array);
  switch ($fv_ftype_txt) {
    case 'txt':  // Tab delimited export from NLP Services.
      $form_state['voterdb']['delimiter'] = "\t";
      break;
    case 'csv':  // CSV delimited export from MySql.
      $form_state['voterdb']['delimiter'] = ",";
      break;
    default:
      form_set_error('nlreportsfile', 'File must be of type txt or csv.');
      return;
  }
  $fv_field_pos = voterdb_validate_nlreports($fv_fname,$form_state['voterdb']['delimiter']);
  if (!$fv_field_pos) {
    form_set_error('nlreportsfile', 'Fix the problem before resubmit.');
    return FALSE;
  }
  $form_state['voterdb']['field_pos'] = $fv_field_pos;
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_form
 * 
 * Create the form for uploading voter contact, IDs and comments.
 * 
 * @param type $form_id
 * @param type $form_state
 * @return associative array - the form.
 */
function voterdb_restore_nlsreports_form($form_id, &$form_state) {
  $rr_button_obj = new NlpButton();
  $rr_button_obj->setStyle();
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['reenter'] = TRUE;
    if(!voterdb_get_group($form_state)) {return;}
  } 
  $rr_county = $form_state['voterdb']['county'];
  // Create file name for results tab-delimited file.
  $rr_temp_dir = 'public://temp';
  file_prepare_directory($rr_temp_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
  // Name of the NL report file to upload.
  $form['nlreportsfile'] = array(
    '#type' => 'managed_file',
    '#title' => t('NL reports file name'),
    '#description' => 'Select a file of historical results reported by NLs.',
    '#progress_message' => 'Uploading',
    '#upload_location' => $rr_temp_dir,
    '#upload_validators' => array('file_validate_extensions' => array('txt csv'),),
  );
  // A submit button.
  $form['uploadfile'] = array(
      '#type' => 'submit',
      '#id' => 'upload-file',
      '#value' => 'Process the uploaded reports file >>',
  );
  
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$rr_county.'" class="button ">Return to Admin page >></a></p>',
  );
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_form_validate
 * 
 * Validate the file submitted is a good export from the VAN.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_restore_nlsreports_form_validate($form,&$form_state) { 
  $rv_file = file_load($form_state['values']['nlreportsfile']);
  $form_state['voterdb']['uri'] = $rv_file->uri;
  // Validate the file supplied.
  voterdb_validate_file($form_state);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_restore_nlsreports_form_submit
 * 
 * Process the submitted file to restore the historical NL reports.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_restore_nlsreports_form_submit($form,&$form_state) {
  // Process this historical reports upload file.
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  //$rr_county = $form_state['voterdb']['county'];
  // Move the temp file to somewhere useful for the batch operation.
  $rr_mpath = drupal_get_path('module','voterdb');
  // Empty the results table.
  db_set_active('nlp_voterdb');
  db_truncate(DB_NLPRESULTS_TBL)->execute();
  db_set_active('default');
  // Set up the batch operation.
  $rr_args = array (
    'uri' => $form_state['voterdb']['uri'],
    'field_pos' => $form_state['voterdb']['field_pos'],
    'delimiter' => $form_state['voterdb']['delimiter'],
  );
  $rr_batch = array(
    'operations' => array(
      array('voterdb_restore_nlsreports_upload', array($rr_args))
      ),
    'file' => $rr_mpath.'/voterdb_restore_nlsreports_upload.php',
    'finished' => 'voterdb_restore_nlsreports_finished',
    'title' => t('Processing restore reports upload.'), 
    'init_message' => t('Reports restore upload is starting.'), 
    'progress_message' => t('Processed @percentage % of reports file.'), 
    'error_message' => t('Restore reports upload has encountered an error.'),
  );
  batch_set($rr_batch);
}