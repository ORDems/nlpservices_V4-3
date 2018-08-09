<?php
/*
 * Name: voterdb_nlpconfig_func.php   V4.3 8/8/18
 * Sets the global variables for an election cycle.
 */

use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\ApiResponseCodes;
use Drupal\voterdb\ApiAuthentication;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\NlpSurveyQuestion;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_create_folders
 * 
 * 
 */
function voterdb_create_folders() {
  $cf_sub_folders = array(VO_TURFPDF_DIR,VO_CALLLIST_DIR,VO_MAILLIST_DIR,VO_INSTRUCTIONS_DIR);
  // Create a temp folder for files that have a timed existence.

  $cf_temp_dir = 'public://temp';

  file_prepare_directory($cf_temp_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);

  // Create a folder for more permanent files for NLP Services.
  $cf_voterdb_dir = 'public://'.VO_DIR;
  file_prepare_directory($cf_voterdb_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
  
  $countyNamesObj = new NlpCounties();
  $cf_county_array = $countyNamesObj->getCountyNames();
  
  foreach ($cf_county_array as $cf_county) {
    // Create the county folder.
    $cf_county_dir = $cf_voterdb_dir.'/'.$cf_county;
    file_prepare_directory($cf_county_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    foreach ($cf_sub_folders as $cf_sub_folder) {
      $cf_sub_dir = $cf_county_dir.'/'.$cf_sub_folder;
      file_prepare_directory($cf_sub_dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    }
  }
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_check_date
 * 
 * Verify that the date is in the form:  yyyy-mm-dd.  The month is 
 * between 1 and 12 inclusive.  The day is within the allowed number of days 
 * for the given month. Leap years are taken into consideration. The year is 
 * between 1 and 32767 inclusive. 
 * 
 * @param type $cd_date - string with date to be checked.
 * 
 * @return boolean - FALSE if error.
 */
function voterdb_check_date($cd_date) {
  $cd_date_fields = explode('-',$cd_date);
  if(count($cd_date_fields)!=3) {return FALSE;}
  if(strlen($cd_date_fields[0])!=4) {return FALSE;}
  if(strlen($cd_date_fields[1])!=2) {return FALSE;}
  if(strlen($cd_date_fields[2])!=2) {return FALSE;}
  // Check that the fields are within legal range.
  $cd_er = checkdate($cd_date_fields[1],$cd_date_fields[2],$cd_date_fields[0]);
  return $cd_er;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_form
 * 
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_build_form(&$form,&$form_state) {
  $form = array(); 
  // Create a two column table for configuration blocks.
  $form['tbl'] = array (
    '#markup' => " \r ".'<table style = "width:1050px;">',
  );
  $form['body'] = array(
    '#markup' => " \n ".'<tbody><tr><td  style="width:350px;  vertical-align: top;">',
  );
  
  
  //
  // - - - Voting record block. - - - - - - - - - - - - - -
  //

  $form['voting'] = array(
    '#title' => 'Voting Record Configuration',
    '#prefix' => " \n".'<div style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $ad_required_record = variable_get('voterdb_required_voting_record', '');
  $ad_required_name = 'Primary16';
  if(!empty($ad_required_record)) {
    $ad_required_name = $ad_required_record['name'];
  }
  $form['voting']['voterdb_vrecord'] = array(
    '#type' => 'textfield',
    '#id' => 'vrecord',
    '#title' => t('Required voting record field'),
    '#default_value' => $ad_required_name,
    '#size' => 10,
    '#maxlength' => 10,
    '#description' => t("A text value to identify a required voting record field."),
    '#required' => TRUE,
  );
  $ad_optional_record = variable_get('voterdb_optional_voting_record', '');
  $ad_optional_name = '';
  if(!empty($ad_optional_record)) {
    $ad_optional_name = $ad_optional_record['name'];
  }
  $form['voting']['voterdb_orecord'] = array(
    '#type' => 'textfield',
    '#id' => 'orecord',
    '#title' => t('Optional voting record field'),
    '#default_value' => $ad_optional_name,
    '#size' => 10,
    '#maxlength' => 10,
    '#description' => t("A text value to identify an election record to use if present."),
  );
  // Election cycle dates.
  $form['cycle-reset'] = array(
    '#title' => 'Election Cycle Reset',
    '#prefix' => " \n".'<div  style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $form['cycle-reset']['reset-confirm'] = array(
    '#type' => 'checkbox',
    '#id' => 'cycle-reset',
    '#title' => t('Reset the database for a new cycle'),
    '#description' => t("Be sure you have saved the current cycle results."),
  );
  // Column break.
   $form['column-break2'] = array(
    '#markup' => " \n ".'</td><td   style="width:350px;  vertical-align: top;">',
   );
   
  //
  // - - - Election cycle dates. - - - - - - - - - - - - - -
  //

  $form['ecycle'] = array(
    '#title' => 'Election Cycle Date Configuration',
    '#prefix' => " \n".'<div  style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $form['ecycle']['voterdb_ecycle'] = array(
    '#type' => 'textfield',
    '#id' => 'ecycle',
    '#title' => t('Election cycle title'),
    '#default_value' => variable_get('voterdb_ecycle', 'yyyy-mm-t'),
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t("A text value to identify this election cycle in the form yyyy-mm-t (t can be G, P, S,or U)"),
    '#required' => TRUE,
  );
  $form['ecycle']['voterdb_cycle_name'] = array(
    '#type' => 'textfield',
    '#id' => 'cycle_name',
    '#title' => t('Election cycle name'),
    '#default_value' => variable_get('voterdb_cycle_name', 'November 6, 2018'),
    '#size' => 30,
    '#maxlength' => 120,
    '#description' => t("A descriptive name for this election cycle."),
    '#required' => TRUE,
  );
  $form['ecycle']['voterdb_edate'] = array(
    '#type' => 'textfield',
    '#id' => 'edate',
    '#title' => t('Election Date'),
    '#default_value' => variable_get('voterdb_edate', '2017-05-16'),
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t("Date of the election:  yyyy-mm-dd format"),
    '#required' => TRUE,
  );
  $form['ecycle']['voterdb_bdate'] = array(
    '#type' => 'textfield',
    '#id' => 'bdate',
    '#title' => t('Date of Ballot Drop'),
    '#default_value' => variable_get('voterdb_bdate', '2017-04-26'),
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t("Date the ballots drop:  yyyy-mm-dd format"),
  );
  $form['ecycle']['voterdb_ndate'] = array(
    '#type' => 'textfield',
    '#id' => 'ndate',
    '#title' => t('Reminder Date'),
    '#default_value' => variable_get('voterdb_ndate', '2017-04-19'),
    '#size' => 16,
    '#maxlength' => 16,
    '#description' => t("Date to remind coordinators that NL has not reported results:  yyyy-mm-dd format"),
    '#required' => TRUE,
  );
  
  
    
  //
  // - - - Site configuration info. - - - - - - - - - - - - - -
  //

  $form['site'] = array(
    '#title' => 'Site Configuration',
    '#prefix' => " \n".'<div style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  $ad_state = array('Select'=>'Select','Indiana'=>'Indiana','Oregon'=>'Oregon');
  $form['site']['voterdb_state'] = array(
    '#type' => 'select',
    '#title' => t('Select a state.'),
    '#options' => $ad_state,
    '#default_value' => variable_get('voterdb_state', 'Select'),
    '#required' => TRUE,
  );

  
  $form['site']['voterdb_email'] = array(
    '#type' => 'textfield',
    '#id' => 'email',
    '#title' => t('From email address'),
    '#default_value' => variable_get('voterdb_email', 'notifications@nlpservices.org'),
    '#size' => 40,
    '#maxlength' => 60,
    '#description' => t("Sending mail address to be used in all messages sent by NLP Services."),
    '#required' => TRUE,
  );
  
  // Column break.
   $form['column-break3'] = array(
    '#markup' => " \n ".'</td><td   style="width:350px;  vertical-align: top;">',
   );

  //
  // - - - VoteBuilder API configuration info. - - - - - - - - - - - - - -
  //

  $form['VoteBuilder'] = array(
    '#title' => 'VoteBuilder API Configuration',
    '#prefix' => " \n".'<div style="width:310px;">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
  );
  
  $stateCommittee = variable_get('voterdb_state_committee', '');
  
  $form['VoteBuilder']['voterdb_state_committee'] = array(
    '#type' => 'textfield',
    '#id' => 'state_committee',
    '#title' => t('VoterBuilder Committee for API'),
    '#default_value' => $stateCommittee,
    '#size' => 40,
    '#maxlength' => 64,
    '#description' => t("The state committee name where the API has access to most content."),
    //'#required' => TRUE,
  );
  
  if(!empty($stateCommittee)) {
    
    $apiAuthenticationObj = new ApiAuthentication();
    $committeeAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
    $url = $user = $apiKey = '';
    if(!empty($committeeAuthenticationObj)) {
      $url = $committeeAuthenticationObj->URL;
      $user = $committeeAuthenticationObj->User;
      $apiKey = $committeeAuthenticationObj->apiKey;
    }

    $form['VoteBuilder']['voterdb_state_url'] = array(
      '#type' => 'textfield',
      '#id' => 'state_url',
      '#title' => t('API URL'),
      '#default_value' => $url,
      '#size' => 40,
      '#maxlength' => 60,
      '#description' => t("The URL for accessing the API."),
      '#required' => TRUE,
    );

    $form['VoteBuilder']['voterdb_state_user'] = array(
      '#type' => 'textfield',
      '#id' => 'state_user',
      '#title' => t('Application Name (username)'),
      '#default_value' => $user,
      '#size' => 40,
      '#maxlength' => 60,
      '#description' => t("Application Name (simple authentication username)."),
      '#required' => TRUE,
    );

    $form['VoteBuilder']['voterdb_state_apikey'] = array(
      '#type' => 'textfield',
      '#id' => 'state_apikey',
      '#title' => t('API Key'),
      '#default_value' => $apiKey,
      '#size' => 40,
      '#maxlength' => 128,
      '#description' => t("The key for the API account."),
      '#required' => TRUE,
    );
    
    
    $form['VoteBuilder']['api_submit'] = array(
      '#type' => 'submit',
      '#name' => 'apisubmit',
      '#value' => 'Save >>'
    ); 
    
    
    $form['VoteBuilder']['vb-hdr'] = array (
      '#markup' => '<p>&nbsp;</p><hr><b>Use the link below to choose the survey '
        . 'question and to verify the VoterBuilder response codes.</b>',
    );
    // Submit button
    $form['VoteBuilder']['verifyvb'] = array(
      '#type' => 'submit',
      '#name' => 'verifyvb',
      '#value' => 'Verify VoterBuilder interface >>'
    );
  
  }
  
  

   
  // End of table body.
  $form['body-end'] = array(
    '#markup' => " \n ".'</td></tr></tbody>',
    );
  // End of the table.
  $form['table_end'] = array(
    '#markup' => " \n ".'</table>'." \n ",
    );
  // Submit button
  $form['submit'] = array(
    '#type' => 'submit',
    '#name' => 'configsubmit',
    '#value' => 'Submit Changes >>'
  );  
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_countypw
 * 
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_build_countypw(&$form, &$form_state) {
  $bc_countiesObj = new NlpCounties();
  $bc_county_array = $bc_countiesObj->getCountyNames();
  $bc_passwordObj = new NlpMagicWords();
  $bc_display = '';
  foreach ($bc_county_array as $bc_county) {
    $form = array();
    $bc_password_array = $bc_passwordObj->isSetMagicWords($bc_county);
    voterdb_debug_msg('password array '.$bc_county, $bc_password_array);
    if($bc_password_array['password'] != '' OR $bc_password_array['passwordAlt'] != '') {
      $bc_display .= 'County: '.$bc_county.' PW: <b>'.$bc_password_array['password']. '</b> Alt PW: <b>'.$bc_password_array['passwordAlt'].'</b><br>';
    }
  }
  if(!empty($bc_display)) {
    $form['countypw_list'] = array(
    '#markup' => "<b>Counties with custom passwords</b><br>".$bc_display,
    );
  }
  $form['countypw_selected'] = array(
       '#type' => 'select',
       '#title' => t('Selected'),
       '#options' => $bc_county_array,
       '#description' => t('Select a county to set a custom password.'),
   );
  $form['nlp']['county_password'] = array(
    '#type' => 'textfield',
    '#id' => 'password',
    '#title' => t('County NL password'),
    '#size' => 12,
    '#maxlength' => 12,
    '#description' => t("A custom password for a county."),
    //'#required' => TRUE,
  );
  $form['nlp']['county_altpassword'] = array(
    '#type' => 'textfield',
    '#id' => 'altpassword',
    '#title' => t('County NL password (alternate)'),
    '#size' => 12,
    '#maxlength' => 12,
    '#description' => t("An alternate custom password."),
    //'#required' => TRUE,
  );
   $form['countypw_back'] = array(
    '#type' => 'submit',
    '#name' => 'pwback',
    '#value' => '<< Back'
  );
  $form['countypw_submit'] = array(
    '#type' => 'submit',
    '#name' => 'pwsubmit',
    '#value' => 'Set Unique County Password >>'
  );    
  return $form;
}


