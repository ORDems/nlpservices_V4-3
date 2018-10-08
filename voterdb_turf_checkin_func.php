<?php
/*
 * Name: voterdb_turf_checkin_func.php     V4.3  10/7/18
 * This include file contains the code to upload a turf exported from the
 * VAN and add it to the voter database.
 */
/*
 * voterdb_get_base, voterdb_insert_turf
 * voterdb_hd_selected_callback, voterdb_pct_selected_callback
 */

use Drupal\voterdb\NlpVoters;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpActivistCodes;
use Drupal\voterdb\ApiSurveyResponse;
use Drupal\voterdb\ApiSurveyContext;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\ApiAuthentication;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_base
 * 
 * Given a file name of a PDF file exported from the VoteBuilder, strip off
 * the suffix, ie the .pdf.  Then remove the two random strings at the end
 * which are separated by underscore.  I assume the underscore is not used
 * as part of the base file name.
 * 
 * @param type $gb_filename - name of a pdf file exported from VoteBuilder
 * return string - base file name
 */
function voterdb_get_base($gb_filename) {
  $gb_turfpdf_e = explode('.', $gb_filename);
  $gb_turfpdf_b = explode('_', $gb_turfpdf_e[0]);
  $gb_turf_base = $gb_turfpdf_b[0];
  return $gb_turf_base;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_insert_turf
 *
 * Enter the turf into the MySQL table for voters.  And, save the PDF if
 * submitted so the NL can get it on the website.

 * @param type $form_state
 * 
 * return - False if error.
 */
function voterdb_insert_turf(&$form_state) {
  $it_mcid = $form_state['voterdb']['mcid'];
  $it_county = $form_state['voterdb']['county'];
  $it_voters = $form_state['voterdb']['voters'];
  // replace voters.
  $stateCommittee = variable_get('voterdb_state_committee', '');
  $apiAuthenticationObj = new ApiAuthentication();
  $stateAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
  
  $responseObj = new ApiSurveyResponse();
  $contextObj = new ApiSurveyContext();
  $apiSurveyQuestionObj = new ApiSurveyQuestions($contextObj);
  
  $nlpActivistCodeObj = new NlpActivistCodes();
  $nlpVoterAC = $nlpActivistCodeObj->getActivistCode('NLPVoter');
  //voterdb_debug_msg('voterac', $nlpVoterAC);
  
  $voterObj = new NlpVoters();
  foreach ($it_voters as $it_vanid => $it_voter) {
    db_set_active('nlp_voterdb');
    try {
      db_merge(DB_NLPVOTER_TBL)
        ->key(array(VN_VANID => $it_vanid))
        ->fields($it_voter)
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $voterStatus = $voterObj->getVoterStatus($it_vanid);
    if(!empty($it_voter['DORCurrent'])) {
      $voterStatus['dorCurrent'] = $it_voter['DORCurrent'];
    } else {
      $voterStatus['dorCurrent'] = $it_voter['DateReg'];
    }
    if(empty($voterStatus['nlpVoter'])) {
      $voterStatus['nlpVoter'] = TRUE;
      
      $surveyResponse['type'] = 'Activist';
      $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
      $surveyResponse['dateCanvassed'] = NULL;
      $surveyResponse['vanid'] = $it_vanid;
      $surveyResponse['action'] = 1;
      $surveyResponse['rid'] = $nlpVoterAC['activistCodeId'];
      //voterdb_debug_msg('surveyresponse', $surveyResponse);
      $apiSurveyQuestionObj->setApiSurveyResponse($stateAuthenticationObj,0,$responseObj,$surveyResponse);
      
    }
    $voterObj->setVoterStatus($it_vanid, $voterStatus);
  } 
  

  
  // Create a turf table for this new turf. 
  $turf['county'] = $it_county;
  $turf['mcid'] = $it_mcid;
  $turf['firstName'] = $form_state['voterdb']['fname'];
  $turf['lastName'] = $form_state['voterdb']['lname'];
  $turf['turfName'] = $form_state['voterdb']['tname'];
  $turf['pdf'] = $form_state['voterdb']['pdf_file'];
  $turf['hd'] = $form_state['voterdb']['turf_hd'];
  $turf['pct'] = $form_state['voterdb']['turf_pct'];
  //$turfsObj = $form_state['voterdb']['turfsObj'];
  $turfsObj = new NlpTurfs();
  $it_turf_index = $turfsObj->createTurf($turf);
  $form_state['voterdb']['turf_index'] = $it_turf_index;
  // Now insert a grp entry.
  $it_vanids = array_keys($it_voters);
  db_set_active('nlp_voterdb');
  foreach ($it_vanids as $it_vanid) {
    try {
      db_insert(DB_NLPVOTER_GRP_TBL)
        ->fields(array(
          NV_COUNTY => $it_county,
          NV_MCID => $it_mcid,
          NV_VANID => $it_vanid,
          NV_NLTURFINDEX => $it_turf_index,
          NV_VOTERSTATUS => 'A',
        ))
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
  }
  db_set_active('default');
  return TRUE;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_hd_selected_callback
 * 
 * AJAX call back for the selection of the HD
 * 
 * @param type $form
 * @param type $form_state
 * @return type
 */
function voterdb_hd_selected_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['nl-select']['hd-change'];
}
/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_pct_selected_callback
 * 
 * AJAX callback for the selection of an NL to associate with a turf.
 *
 * @return array
 */
function voterdb_pct_selected_callback ($form,$form_state) {
  //Rebuild the form to list the NLs in the precinct after the precinct is selected.
  return $form['nl-select']['hd-change']['nls-select'];
}