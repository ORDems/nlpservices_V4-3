<?php
/*
 * Name: voterdb_import_users.php   V5.0  11/27/18
 * This include file contains the code to process the ballot received status
 * from the VAN.
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_magic_word.php";
require_once "voterdb_class_get_browser.php";
require_once "voterdb_class_button.php";

use Drupal\voterdb\NlpDrupalUser;
use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpMagicWord;
use Drupal\voterdb\NlpButton;
use Drupal\voterdb\GetBrowser;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_import_users_form
 * 
 * Create the form for uploading voter ballot status.
 * 
 * @param type $form_id
 * @param type $form_state
 * @return string
 */
function voterdb_import_users_form($form_id, &$form_state) {
  if (!isset($form_state['nlp']['reenter'])) {
    $form_state['nlp']['reenter'] = TRUE;
  } 
  $br_button_obj = new NlpButton();
  $br_button_obj->setStyle();
  
  $userObj = new NlpDrupalUser();
  $roles = $userObj->getRoles();
  voterdb_debug_msg('roles', $roles);

  $br_temp_dir = 'public://temp';
  // Create the temp directory if not already there.
  file_prepare_directory($br_temp_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
  // Ask for the ballot recieved file to upload.

  // Name of the user account file to upload.
  $form['userfile'] = array(
      '#type' => 'managed_file',
      '#title' => t('User accounts file name'),
      '#description' => t('Select a user accounts file.<br>'),
      '#progress_message' => 'Uploading',
      '#upload_location' => $br_temp_dir,
      '#upload_validators' => array('file_validate_extensions' => array('txt'),),
  );
  // A submit button for the upload of voting results.
  $form['importfile'] = array(
      '#type' => 'submit',
      '#id' => 'import-file',
      '#value' => t('Process the uploaded user accounts File >>'),
  );

  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_import_users_form_submit
 * 
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_import_users_form_submit($form,&$form_state) {
  //voterdb_debug_msg('values', $form_state['vlues']);
  $form_state['nlp']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  
  $file = file_load($form_state['values']['userfile']);

  $modulePath = drupal_get_path('module','voterdb');
  // Setup the call to start a batch operation.
  $args = array (
    'uri' => $file->uri,
  );
  $batch = array(
    'operations' => array(
      array('voterdb_import_users_upload', array($args))
      ),
    'file' => $modulePath.'/voterdb_import_users_upload.php',
    'finished' => 'voterdb_import_users_finished',
    'title' => t('Processing import_users_upload.'), 
    'init_message' => t('Users import is starting.'), 
    'progress_message' => t('Processed @percentage % of user accounts file.'), 
    'error_message' => t('import_users has encountered an error.'),
  );
  voterdb_debug_msg('batch', $batch);
  batch_set($batch);

  drupal_set_message('import complete','status');
}
