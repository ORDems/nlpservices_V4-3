<?php
/*
 * Name:  voterdb_candidates.php               V4.3 8/8/18
 */

require_once "voterdb_group.php";
require_once "voterdb_debug.php";
require_once "voterdb_banner.php";
require_once "voterdb_class_button.php";
require_once "voterdb_class_survey_questions_api.php";
require_once "voterdb_class_survey_question_nlp.php";
require_once "voterdb_class_api_authentication.php";
require_once "voterdb_class_candidates_nlp.php";
require_once "voterdb_class_survey_response_nlp.php";
require_once "voterdb_class_counties.php";

require_once "voterdb_candidates_func.php";
require_once "voterdb_candidates_func2.php";

use Drupal\voterdb\NlpButton;
use Drupal\voterdb\NlpCandidates;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\ApiAuthentication;
use Drupal\voterdb\ApiSurveyContext;
use Drupal\voterdb\NlpSurveyResponse;


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_candidates_form
 *
 * Create the form for managing the list of candidates for an election cycle.
 *
 * @param type $form
 * @param type $form_state
 * @return string
 */
function voterdb_candidates_form($form, &$form_state) {
  $dv_button_obj = new NlpButton();
  $dv_button_obj->setStyle();
  if (!isset($form_state['voterdb']['reenter'])) {
    if(!voterdb_get_group($form_state)) {return;}
    $form_state['voterdb']['reenter'] = TRUE;
    $form_state['voterdb']['scope'] = "unknown";  // Scope is not yet known.
    $form_state['voterdb']['func'] = "add"; 
    $form_state['voterdb']['definedCandidates'] = 'unknown';
    //voterdb_debug_msg('voterdb, form', $form_state['voterdb']);
    if(empty($form_state['voterdb']['ALL'])) {
      $form_state['voterdb']['ALL'] = FALSE;
    }
  }
  $dv_tbl_style = '
    .noborder {border-collapse: collapse; border-style: hidden; table-layout:fixed;}
    .nowhite {margin:0px; padding:0px; line-height:100%;}
    .form-item {margin-top:0px; margin-top:0px;}
    .td-de {margin-left:2px; margin-bottom:2px; line-height:100%;}
    .form-type-textfield {margin: 2px 2px;}
    ';
  drupal_add_css($dv_tbl_style, array('type' => 'inline'));
  $fv_county = $form_state['voterdb']['county'];
  $fv_func = $form_state['voterdb']['func'];
  // Create the banner.
  $fv_banner = voterdb_build_banner ($fv_county);
  $form['note'] = array (
    '#type' => 'markup',
    '#markup' => $fv_banner
  );
  $stateCommittee = variable_get('voterdb_state_committee', 'DPO');
  //voterdb_debug_msg('voterdb', $form_state['voterdb']);
  if($form_state['voterdb']['definedCandidates'] == 'unknown') {
    $responsesObj = new NlpSurveyResponse();
    $candidatesObj = new NlpCandidates($responsesObj);
    $form_state['voterdb']['candidatesObj'] = $candidatesObj;
    $candidatesArray = $candidatesObj->getCandidates();
    if(empty($candidatesArray)) {
      $form_state['voterdb']['definedCandidates'] = 'no';
      $form_state['voterdb']['candidatesArray'] = array();
    } else {
      $form_state['voterdb']['definedCandidates'] = 'yes';
      $form_state['voterdb']['candidatesArray'] = $candidatesArray;
    }
    $apiAuthenticationObj = new ApiAuthentication();
    $countyAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
    $contextObj = new ApiSurveyContext();
    $surveyQuestionsObj = new ApiSurveyQuestions($contextObj);
    $questionsObj = $surveyQuestionsObj->getApiSurveyQuestions($countyAuthenticationObj,0,'Candidate');
    $questionsArray = $questionsObj->result;
    $availableCandidates = array();
    foreach ($questionsArray as $qid => $candidateQuestion) {
      if(!isset($candidatesArray[$qid])) {
        $availableCandidates[$qid] = $candidateQuestion;
      }
    }
    $form_state['voterdb']['availableCandidates'] = $availableCandidates;
    voterdb_debug_msg('voterdb', $form_state['voterdb']);
  }
  switch ($fv_func) {
    case 'add':
      $form['note1'] = array (
        '#type' => 'markup',
        '#markup' => '<span style="font-weight: bold;">Either you can select the scope of the candiate\'s district.</span>',
      );
      voterdb_build_cscope($form,$form_state);  // func.
      $form['note2'] = array (
        '#type' => 'markup',
        '#markup' => '<span style="font-weight: bold;">Or, you can edit or delete a candidate.</span>',
      );
      
      $candidatesArray = $form_state['voterdb']['candidatesArray'];
      $scopeArray = voterdb_build_candidate_list($candidatesArray);  // func.
      voterdb_debug_msg('scope array', $scopeArray );
      $all = $form_state['voterdb']['ALL'];
      foreach ($scopeArray as $cat => $candidateList) {
        $form['candidates'][$cat] = voterdb_display_candidates($cat,$candidateList,$all); // func.
      }
      break;
    case 'edit':
      voterdb_edit_candidate($form,$form_state);  // func2.
      break;
    case 'delete':
      voterdb_confirm_cdelete($form,$form_state);  // func2.
      break;
  }
  $form['done'] = array(
    '#markup' => '<p><a href="nlpadmin?County='.$fv_county.'" class="button ">Return to Admin page >></a></p>',
  );
  
  return $form;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_candidates_form_validate
 * 
 * Get the user entered scope.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_candidates_form_validate($form, &$form_state) {
  $fv_func = $form_state['voterdb']['func'];
  switch ($fv_func) {
    case 'add':
      $fv_type = $form_state['triggering_element']['#type'];
      // If the button type is select, then the scope of the role for the 
      // coordinator was selected.
      if ($fv_type == 'select') {
        $fv_name = $form_state['triggering_element']['#name'];
        // Check that the actual select was scope, should always be true.
        if ($fv_name == 'scope-select') {
          $fv_scope_select = $form_state['triggering_element']['#value'];
          $fv_options = $form_state['triggering_element']['#options'];
          $form_state['voterdb']['scope'] = $fv_options[$fv_scope_select];
        }
        return;
      }
      // Check if one of the submit buttons was clicked.
      if ($fv_type == 'submit') {
        $fv_value = $form_state['triggering_element']['#value'];
        if($fv_value == 'Add this Candidate >>') {
          // Check for numeric weight.
          return; 
        }
        if($fv_value == 'edit' OR $fv_value == 'delete') {return;}
        return;
      }
      break;
    case 'edit':
      break;
    case 'delete':
      break;
  }
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_candidates_form_submit
 *
 * Process the request to add, edit or delete a coordinator.
 * 
 * @param type $form
 * @param type $form_state
 */
function voterdb_candidates_form_submit($form, &$form_state) {
  $form_state['voterdb']['reenter'] = TRUE;
  $form_state['rebuild'] = TRUE;  // form_state will persist
  $fv_func = $form_state['voterdb']['func'];
  switch ($fv_func) {
    case 'add':
      // If one of the edit or delete buttons was clicked, get the candidate
      // index and change the function.  Else, add this candidate.
      $fv_type = $form_state['triggering_element']['#type'];
      if ($fv_type == 'submit') {
        $fv_value = $form_state['triggering_element']['#value'];
        if($fv_value == 'edit' OR $fv_value == 'delete') {
          // Change function.
          $form_state['voterdb']['func'] = $fv_value;
          // Get the CIndex for this candidate.
          $fv_id_array = explode('-', $form_state['triggering_element']['#id']);
          $fv_cindex = $fv_id_array[2];
          $form_state['voterdb']['cindex'] = $fv_cindex;
          break;
        }
      }
      voterdb_save_candidate($form_state);  // func2.
      break;
    case 'edit':
      voterdb_save_edited_candidate($form_state);  //func2.
      $form_state['voterdb']['func'] = 'add';
      break;
    case 'delete':
      voterdb_delete_candidate($form_state); //func2.
      $form_state['voterdb']['func'] = 'add';
      break;
  }
  $form_state['voterdb']['scope'] = "unknown";
  $form_state['voterdb']['HD'] = 0;
  $form_state['voterdb']['partial'] = FALSE;  // start over.
  
  unset($form_state['voterdb']['reenter']);
  return;
}