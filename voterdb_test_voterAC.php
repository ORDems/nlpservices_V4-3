<?php
/*
 * Name: voterdb_test.php     
 *
 */
require_once "voterdb_debug.php";
require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_voters.php";
require_once "voterdb_class_activist_codes_api.php";
require_once "voterdb_class_activist_codes_nlp.php";
require_once "voterdb_class_survey_response_nlp.php";
require_once "voterdb_class_survey_questions_api.php";
require_once "voterdb_class_api_authentication.php";

use Drupal\voterdb\NlpVoters;
use Drupal\voterdb\ApiActivistCodes;
use Drupal\voterdb\NlpActivistCodes;
use Drupal\voterdb\ApiSurveyResponse;
use Drupal\voterdb\ApiSurveyContext;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\ApiAuthentication;

function voterdb_test() {
  
  $output = "test started";
  
  $voterObj = new NlpVoters();
  //voterdb_debug_msg('voterobj', $voterObj);
  $limit = 10;
  $voterIds = $voterObj->getNewNlpVoterIds($limit);
  voterdb_debug_msg('newids', $voterIds);
  
  $nlpActivistCodeObj = new NlpActivistCodes();
  $nlpVoterAC = $nlpActivistCodeObj->getActivistCode('NLPVoter');
  voterdb_debug_msg('voterac', $nlpVoterAC);
  
  $stateCommittee = variable_get('voterdb_state_committee', '');
  //$database = 0;
  $apiAuthenticationObj = new ApiAuthentication();
  $stateAuthenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
  
  //$activistCodeObj = new ApiActivistCodes();

  $responseObj = new ApiSurveyResponse();
  $contextObj = new ApiSurveyContext();
  $apiSurveyQuestionObj = new ApiSurveyQuestions($contextObj);
  
  foreach ($voterIds as $vanid) {
    $voterStatus = $voterObj->getVoterStatus($vanid);
    //voterdb_debug_msg('status', $voterStatus);
    if(empty($voterStatus['nlpVoter'])) {
      $voterStatus['nlpVoter'] = TRUE;
      voterdb_debug_msg('status', $voterStatus);
      
      $surveyResponse['type'] = 'Activist';
      $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
      $surveyResponse['dateCanvassed'] = NULL;
      $surveyResponse['vanid'] = $vanid;
      $surveyResponse['action'] = 1;
      $surveyResponse['rid'] = $nlpVoterAC['activistCodeId'];
      voterdb_debug_msg('surveyresponse', $surveyResponse);
      $apiSurveyQuestionObj->setApiSurveyResponse($stateAuthenticationObj,0,$responseObj,$surveyResponse);
      
      
      
      $voterObj->setVoterStatus($vanid, $voterStatus);
      //break;
    } else {

      voterdb_debug_msg('status', $voterStatus);
      //$voterObj->setVoterStatus($vanid, $voterStatus);
      //break;
    }
    
  }
      
  $output .= "<br>test complete";
  return array('#markup' => $output);   

}