<?php
/*
 * Name:  voterdb_candidates_func2.php               V4.2 6/21/18
 */

use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpCandidates;
use Drupal\voterdb\NlpSurveyResponse;

/** * * * * * functions supported * * * * * *
 * voterdb_edit_candidate, voterdb_confirm_cdelete,voterdb_save_candidate, 
 * voterdb_save_edited_candidate, voterdb_delete_candidate,
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_edit_candidate
 * 
 * Display the weight and name so the user can change either the spelling or
 * the weight for display.  The weight changes the display order for the NL.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_edit_candidate(&$form,$form_state) {
  $ec_cindex = $form_state['voterdb']['cindex'];
  $ec_candidate = $form_state['voterdb']['candidates'][$ec_cindex];
  $ec_cname = $ec_candidate[CD_CNAME];
  $ec_weight = $ec_candidate[CD_WEIGHT];  
  $form['eform'] = array(
    '#title' => '<span style="font-weight: bold;">Edit this candidates\'s name or weight</span>',
    '#prefix' => " \n".'<div id="eform-div">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    '#attributes' => array(
      'style' => array(
        'background-image: none; border: 0px; padding:0px; margin:0px; '
        . 'background-color: rgb(240,240,240);'), ),
  );
   // Weight title.
  $form['eform']['e-weight-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<table style="width:380px;">
      <tbody style="border-collapse: collapse; border-style: hidden;">
      <tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Weight:',
  );
  // Weight data entry field.
  $form['eform']['e-weight'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 2,
    '#maxlength' => 4,
    '#type' => 'textfield',
    '#default_value' => $ec_weight,
  );
  // Candidate name title.
  $form['eform']['e-cname-t'] = array (
    '#type' => 'markup',
    '#prefix' => '<tr><td style="text-align:right"><b>',
    '#suffix' => '</b></td>',
    '#markup' => 'Name:',
  );
  // Candidate name data entry field.
  $form['eform']['e-cname'] = array (
    '#prefix' => '<td>',
    '#suffix' => '</td></tr>',
    '#size' => 20,
    '#maxlength' => 30,
    '#type' => 'textfield',
    '#default_value' => $ec_cname,
  );
  // Submit button.
  $form['eform']['e-submit-edit'] = array(
    '#prefix' => '<tr><td></td><td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#value' => 'Update this Candidate >>'
  );
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_confirm_cdelete
 * 
 * Confirm that the user really wants to delete the candidate.
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_confirm_cdelete(&$form,$form_state) {
  $ec_cindex = $form_state['voterdb']['cindex'];
  $ec_candidate = $form_state['voterdb']['candidates'][$ec_cindex];
  $ec_cname = $ec_candidate[CD_CNAME];
  $ec_scope = $ec_candidate[CD_SCOPE];
  $ec_co_info = '<span style="font-weight: bold;">Scope: </span>'.$ec_scope.
    '<span style="font-weight: bold;"> Name: </span>'.$ec_cname;
  $form['dform'] = array(
    '#title' => '<span style="font-weight: bold;">Delete this Candidate</span>',
    '#prefix' => " \n".'<div id="dform-div">'." \n",
    '#suffix' => " \n".'</div>'." \n",
    '#type' => 'fieldset',
    '#attributes' => array(
      'style' => array(
        'background-image: none; border: 0px; padding:0px; margin:0px; '
        . 'background-color: rgb(240,240,240);'), ),
  );
  $form['dform']['d-note'] = array (
    '#type' => 'markup',
    '#markup' => 'Please confirm that you want to delete this candidate.',
  );
  $form['dform']['d-info'] = array (
    '#type' => 'markup',
    '#markup' => '<br>'.$ec_co_info,
  );
  // Yes, delete.
  $form['dform']['d-yes'] = array(
    '#prefix' => '<table style="width:200px;">
      <tbody style="border-collapse: collapse; border-style: hidden;"><tr><td>',
    '#suffix' => '</td>',
    '#type' => 'submit',
    '#name' => 'd-yes',
    '#value' => 'Delete'
  );
  // Nope, cancel.
  $form['dform']['d-cancel'] = array(
    '#prefix' => '<td>',
    '#suffix' => '</td></tr></tbody></table>',
    '#type' => 'submit',
    '#name' => 'd-cancel',
    '#value' => 'Cancel'
  );
  
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_candidate
 * 
 * Update the database with the new candidate's information.
 * 
 * @param type $form_state
 */
function voterdb_save_candidate($form_state) {
  $fv_county = $form_state['voterdb']['county'];
  $fv_scope = $form_state['voterdb']['scope'];
  $fv_all = $form_state['voterdb']['ALL'];
  $fv_hd = $fv_pct_list = $fv_cd = NULL;
  //$fv_pct_array = array(NULL);
  switch ($fv_scope) {
    case 'Group of Precincts':
      $form_state['voterdb']['partial'] = TRUE;
      $fv_pct_list = strip_tags(filter_var($form_state['values']['pct'], FILTER_SANITIZE_STRING));
      //$fv_pct_array = explode(',', $fv_pct_list);
      if($fv_all) {
        $fv_countyi = $form_state['values']['county'];
        $fv_county = $form_state['voterdb']['county-names'][$fv_countyi];
      }
      $fv_scope_value = 'Pcts';
      break;
    case 'House District':
      $fv_hdi = $form_state['values']['hd'];
      $fv_hd_array = $form_state['voterdb']['hd_array'];
      $fv_hd = $fv_hd_array[$fv_hdi];
      $form_state['voterdb']['HD'] = $fv_hd;
      $fv_scope_value = 'HD';
      if($fv_all) {
        $fv_countyi = $form_state['values']['county'];
        $fv_county = $form_state['voterdb']['county-names'][$fv_countyi];
      }
      break;
    case 'County':
      $fv_scope_value = 'County';
      if($fv_all) {
        $fv_countyi = $form_state['values']['county'];
        $fv_county = $form_state['voterdb']['county-names'][$fv_countyi];
      }
      break;
    case 'Congressional District':
      $fv_scope_value = 'CD';
      $fv_cd = $form_state['values']['cd'];
      $fv_county = NULL;
      break;
    case 'State':
      $fv_scope_value = 'State';
      $fv_county = NULL;
      break;
  }
  
  //voterdb_debug_msg('form state ', $form_state);
  
  $fv_qid = $form_state['values']['qid'];
  $fv_weight = $form_state['values']['weight'];
  $availableCandidates = $form_state['voterdb']['availableCandidates'];
  

  $candidateArray = array(
      'qid' => $fv_qid,
      'name' => $availableCandidates[$fv_qid]['name'],
      'weight' => $fv_weight,
      'scope' => $fv_scope_value,
      'county' => $fv_county,
      'cd' => $fv_cd,
      'hd' => $fv_hd,
      'pcts' => $fv_pct_list,
      );
  $candidatesObj = $form_state['voterdb']['candidatesObj'];
  $candidatesObj->setCandidate($candidateArray);
  
  //$stateCommittee = variable_get('voterdb_state_committee', 'DPO');
  
  //$apiAuthenticationObj = new ApiAuthentication();
  //$countyAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
  
  $responseObj = new NlpSurveyResponse();
  $questionObj = new NlpSurveyQuestion($responseObj);
  
  $questionObj->setSurveyQuestion($availableCandidates[$fv_qid],$fv_qid);
  
  

}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_save_edited_candidate
 * 
 * Update the database with the changes to the name and weight.
 * 
 * @param type $form_state
 * @return 
 */
function voterdb_save_edited_candidate($form_state) {
  $qid = $form_state['voterdb']['qid'];
  $candidateObj = new NlpCandidates();
  $candidate['qid'] = $qid;
  $candidate['weight'] = $form_state['values']['e-weight'];
  $candidate['name'] = $form_state['values']['e-cname'];
  $candidateObj->updateCandidate($candidate);
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_delete_candidate
 * 
 * Delete the candidate record if the user confirmed that they really wanted
 * to delete the record.
 * 
 * @param type $form_state
 * @return type
 */
function voterdb_delete_candidate($form_state) {
  $dc_type = $form_state['triggering_element']['#type'];
  if ($dc_type != 'submit') {return;}  // Should not happen.
  $dc_name = $form_state['triggering_element']['#name'];
  if($dc_name == 'd-yes') {
    $qid = $form_state['voterdb']['cindex'];
    // Delete the candidate.
  $responsesObj = new NlpSurveyResponse();
  $candiateObj = new NlpCandidates($responsesObj);
  $candiateObj->deleteCandidate($qid);
  }
  return;
}
