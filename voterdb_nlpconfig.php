<?php
/*
 * Name: voterdb_nlpconfig.php   V4.2 5/29/18
 * Sets the global variables for an election cycle.
 */
require_once "voterdb_get_county_names.php";
require_once "voterdb_debug.php";
require_once "voterdb_path.php";
require_once "voterdb_nlpconfig_func.php";
require_once "voterdb_nlpconfig_func2.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_api_authentication.php";
require_once "voterdb_class_response_codes_api.php";
require_once "voterdb_class_response_codes.php";
require_once "voterdb_class_survey_questions_api.php";
require_once "voterdb_class_survey_question_nlp.php";
require_once "voterdb_class_activist_codes_api.php";
require_once "voterdb_class_activist_codes_nlp.php";


//use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\ApiAuthentication;

define('SQ_TITLE_LEN','16');
define('SQ_RESPONSE_LEN','16');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_config_form
 *
 * Allows the NLP admin to set the global variables for an election cycle and
 * sets the login information for the voterdb database.
 *
 * @param type $form
 * @param type $form_state
 * @return $form.
 */
function voterdb_config_form($form, &$form_state) {
  if (!isset($form_state['voterdb']['reenter'])) {
    $form_state['voterdb']['pass'] = 'config';
  } 
  
  //$form_state['voterdb']['county'] = 'Washington';
  //voterdb_debug_msg('pass', $form_state['voterdb']['pass']);
  switch ($form_state['voterdb']['pass']) {
    case 'config':
      voterdb_build_form($form,$form_state);  // func.
      break;
    case 'countypw':
      voterdb_build_countypw($form, $form_state);
      break;
    case 'verifyvb':
      voterdb_build_verify_votebuilder($form, $form_state);  // func2.
      break;
  }
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cycle_validate
 * 
 * @param type $cv_cycle
 * @return boolean
 */
function voterdb_cycle_validate($cv_cycle) {
  $cv_cycle_fields = explode('-', $cv_cycle);
  $cv_eyear = $cv_cycle_fields[0]; 
  if (!is_numeric($cv_eyear) ) {
    form_set_error('voterdb_ecycle', t('The year field must be numeric.'));
    return FALSE;
  }
  if(strlen($cv_eyear) != 4 AND $cv_eyear > 2017) {
    form_set_error('voterdb_ecycle', t('The year field must be 4 digits.'));
    return FALSE;
  }
  $cv_month = $cv_cycle_fields[1];
  if(!is_numeric($cv_month)) {
    form_set_error('voterdb_ecycle', t('The month field of the election cycle '
      . 'must be a numeric value.'));
    return FALSE;
  }
  if($cv_month < 0 OR $cv_month >12) {
    form_set_error('voterdb_ecycle', t('The month field of the election cycle '
      . 'must be a number between 1 and 12.'));
    return FALSE;
  }
  $cv_cycle_type = $cv_cycle_fields[2];
  if($cv_cycle_type == 'G' OR $cv_cycle_type == 'P' OR $cv_cycle_type == 'S' OR $cv_cycle_type == 'U') {
    return TRUE;
  }
  form_set_error('voterdb_ecycle', t('The cycle type must be G, P, S or U.'));
    return FALSE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_config_form_validate
 *
 * Verify that the election cycle is in the proper form.
 *
 * @param type $form
 * @param type $form_state
 * @return boolean
 */
function voterdb_config_form_validate($form, &$form_state) {
  //voterdb_debug_msg('validate triggering', $form_state['triggering_element']);
  switch ($form_state['voterdb']['pass']) {
    
    case 'config':
      $fv_trigger = $form_state['triggering_element']['#name'];
      //voterdb_debug_msg(' trigger', $fv_trigger);
      if ($fv_trigger != 'configsubmit') {
        return;
      }

      $ad_cycle = $form_state['values']['voterdb_ecycle'];
      if(!voterdb_cycle_validate($ad_cycle)) {return;} 
      $ad_rvr = $form_state['values']['voterdb_vrecord'];  // Required voting record.
      $ad_ryear = substr($ad_rvr,-2);  // Last rwo characters are the year.
      $ad_rtype = substr($ad_rvr,0,-2);  // The first part is the cycle type.
      if($ad_rtype != 'General' AND $ad_rtype != 'Primary') {
        form_set_error('voterdb_vrecord', t('The required voting record must be either Generalxx or Primaryxx.'));
        return;
      }
      if(!is_numeric($ad_ryear) OR $ad_ryear%2!=0) {
        form_set_error('voterdb_vrecord', t('The year has to be an even number.'));
        return;
      }
      $ad_ovr = $form_state['values']['voterdb_orecord'];  // Optional voting record.
      $ad_otype = $ad_oyear = NULL;
      if(!empty($ad_ovr)) {
        $ad_oyear = substr($ad_ovr,-2);  // Last rwo characters are the year.
        $ad_otype = substr($ad_ovr,0,-2);  // The first part is the cycle type.
        if($ad_otype != 'General' AND $ad_otype != 'Primary') {
          form_set_error('voterdb_orecord', t('The optional voting record must be either Generalxx or Primaryxx.'));
          return;
        }
        if($ad_rtype == $ad_otype) {
          if($ad_otype == 'Primary') {
            form_set_error('voterdb_orecord', t('The optional voting record must be Generalxx.'));
          } else {
            form_set_error('voterdb_orecord', t('The optional voting record must be Primaryxx.'));
          }
        }
        if($ad_otype == 'Primary') {
          if($ad_oyear != $ad_ryear+2) {
            $ad_gyear = $ad_ryear+2;
            form_set_error('voterdb_orecord', t('The optional voting year must be '.$ad_gyear));
            return;
          }
        } else {
          if($ad_oyear != $ad_ryear) {
            form_set_error('voterdb_orecord', t('The optional voting year must be '.$ad_ryear));
            return;
          }
        }
      }
      $form_state['req_voting']['name'] = $ad_rvr;
      $form_state['req_voting']['type'] = $ad_rtype;
      $form_state['req_voting']['year'] = $ad_ryear;
      $form_state['opt_voting']['name'] = $ad_ovr;
      if(!empty($ad_otype)) {
        $form_state['opt_voting']['type'] = $ad_otype;
        $form_state['opt_voting']['year'] = $ad_oyear;
      }
      // Check the dates for ISO format.
      $ad_rtn = FALSE;
      $form_state['e-date'] = $form_state['values']['voterdb_edate'];
      if (!voterdb_check_date($form_state['e-date'])) {
        form_set_error('voterdb_edate', t('The date must be in the form:  yyyy-mm-dd '));
        $ad_rtn = TRUE;
      }
      $form_state['b-date'] = $form_state['values']['voterdb_bdate'];
      if (!empty($form_state['b-date']) AND !voterdb_check_date($form_state['b-date'])) {
        form_set_error('voterdb_bdate', t('The date must be in the form:  yyyy-mm-dd '));
        $ad_rtn = TRUE;
      }
      $form_state['n-date'] = $form_state['values']['voterdb_ndate'];
      if (!voterdb_check_date($form_state['n-date'])) {
        form_set_error('voterdb_ndate', t('The date must be in the form:  yyyy-mm-dd '));
        $ad_rtn = TRUE;
      }
      if($ad_rtn) {return;}
      $form_state['email'] = $form_state['values']['voterdb_email'];
      if(!valid_email_address($form_state['email'])) {
        form_set_error('voterdb_email', t('The email is not in correct format.'));
        return;
      }
      
      break;
      
      
      
      case 'verfifyvb':
      $fv_trigger = $form_state['triggering_element']['#name'];

      //voterdb_debug_msg(' trigger', $fv_trigger);
      if ($fv_trigger != 'vbback') { 
        //voterdb_debug_msg(' not back', '');
        
        
        // verify stuff
        
        //break;
      }
      break;
  }
  
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_config_form_submit
 *
 * Delete any existing tables and build new ones.   All the field entries
 * will be built with appropriate attributes.  Then build the table with the
 * list of house districts for each supported county.
 *
 * @param type $form
 * @param type $form_state
 */
function voterdb_config_form_submit($form, &$form_state) {
  //voterdb_debug_msg('submit triggering', $form_state['triggering_element']);
  $fv_trigger = $form_state['triggering_element']['#name'];
  //voterdb_debug_msg('submit trigger', $fv_trigger);
  //voterdb_debug_msg('submit pass', $form_state['voterdb']['pass']);
  switch ($form_state['voterdb']['pass']) {
    
    case 'config':
      if ($fv_trigger == 'countypw') {
        $form_state['voterdb']['reenter'] = TRUE;
        $form_state['rebuild'] = TRUE;  // form_state will persist.
        $form_state['voterdb']['pass'] = 'verifyvb';
        break;
      }
      
      unset($form_state['voterdb']['reenter']);
      $form_state['rebuild'] = FALSE;  
      
      
      if ($fv_trigger == 'configsubmit') {
        // Make sure all the folders are available.
        voterdb_create_folders();

        // Set (or reset) all the application variables.
        variable_set('voterdb_ecycle',$form_state['values']['voterdb_ecycle']);
        variable_set('voterdb_cycle_name',$form_state['values']['voterdb_cycle_name']);
        variable_set('voterdb_required_voting_record',$form_state['req_voting']);
        variable_set('voterdb_optional_voting_record',$form_state['opt_voting']);
        variable_set('voterdb_edate',$form_state['e-date']);
        variable_set('voterdb_bdate',$form_state['b-date']);
        variable_set('voterdb_ndate',$form_state['n-date']);
        variable_set('voterdb_email',$form_state['email']);
        variable_set('voterdb_state',$form_state['values']['voterdb_state']);
        }
      
      $stateCommittee = $form_state['values']['voterdb_state_committee'];
      if(!empty($stateCommittee)) {
        variable_set('voterdb_state_committee',$form_state['values']['voterdb_state_committee']);
        $apiAuthenticationObj = new ApiAuthentication();
        $apiAuthenticationObj->Committee = $form_state['values']['voterdb_state_committee'];
        $apiAuthenticationObj->URL = $form_state['values']['voterdb_state_url'];
        $apiAuthenticationObj->User = $form_state['values']['voterdb_state_user'];
        $apiAuthenticationObj->apiKey = $form_state['values']['voterdb_state_apikey'];
        $apiAuthenticationObj->setApiAuthentication();
      }
      
      $cs_reset = $form_state['values']['reset-confirm'];
      if($cs_reset) {
        variable_set('voterdb_br_date',NULL);
        drupal_set_message('The database was reset for a new election cycle.','status');
      }
      drupal_set_message('The election Cycle parameters are updated.','status');
      break;
      
    case 'verifyvb':
      unset($form_state['voterdb']['reenter']);
      $form_state['rebuild'] = FALSE; 
      $form_state['voterdb']['pass'] = 'config';
      if ($fv_trigger == 'vbback') {    
        return;
      }

      voterdb_process_vbverify($form_state);  // func2.
      break;   
  }
  return;
}
