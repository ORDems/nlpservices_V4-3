<?php
/**
 * Name:  voteredb_cron_results.php     V4.1  5/26/18
 * @file
 */
require_once "voterdb_class_api_authentication.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_class_response_codes.php";
require_once "voterdb_class_survey_questions_api.php";

define('BATCHSIZE','500');

use Drupal\voterdb\NlpReports;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\ApiSurveyResponse;
use Drupal\voterdb\ApiSurveyContext;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\ApiAuthentication;

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_results_chk
 * 
 * Check if there are any new canvass results reported by NLs.  If found,
 * schedule batches of reports to be recorded in VoteBuilder.
 * 
 * @return an associative list of NL reports arranged in batches.
 */
function voterdb_results_chk() {
  watchdog('voterdb_cron_results', 'results chk called.');
  $nlReportsObj = new NlpReports();
  $nlReports = $nlReportsObj->getNlpUnrecorded();
  //watchdog('voterdb_results_chk', 'reports: @rec',array('@rec' =>  print_r($nlReports, true)),WATCHDOG_DEBUG);
  $voterReportBatch = array();
  $voterCnt = 0;
  $batchCnt = 0;
  foreach ($nlReports as $vanid => $voterReports) {
    if($voterCnt<BATCHSIZE) {
      $voterReportBatch[$batchCnt][$vanid] = $voterReports;
      $voterCnt++;
    } else {
      $batchCnt++;
      $voterCnt = 1;
      $voterReportBatch = array();
      $voterReportBatch[$batchCnt][$vanid] = $voterReports;
    }
  }
  return $voterReportBatch;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_cron_results_notify
 * 
 * Process the queue of notifications to the coordinator of NLs that have
 * not yet reported resultcanvass results reported by NLs.
 * 
 * @param type $item - A batch of canvass results to be recorded in VoteBuilder.
 */
function voterdb_cron_results_notify($item) {
  watchdog('voterdb_results_notify', 'processed item created at @time', array('@time' => date_iso8601($item->created),));
  $stateCommittee = variable_get('voterdb_state_committee', 'StateParty');
  $authenticationObj = new ApiAuthentication;
  $apiAuthenticationObj = $authenticationObj->getApiAuthentication($stateCommittee);
  $nlpResponseCodesObj = new NlpResponseCodes();
  $responseCodeTypes = $nlpResponseCodesObj->getNlpContactType();
  $responseObj = new ApiSurveyResponse();
  $contextObj = new ApiSurveyContext();
  $apiSurveyQuestionObj = new ApiSurveyQuestions($contextObj);
  $nlReportsObj = new NlpReports();
  $batch = $item->value;
  foreach ($batch as $vanid => $voter) {
    foreach ($voter as $voterReport) {
      //watchdog('voterdb_results_notify', 'report: @rec', array('@rec' =>  print_r($voterReport, true)),WATCHDOG_DEBUG);
      $voterReportType = $voterReport['Type'];
      $surveyResponse['type'] = $voterReportType;
      switch ($voterReportType) {
        case 'Contact':
          $surveyResponse['vanid'] = $vanid;
          $surveyResponse['rid'] = $voterReport['Rid'];
          switch ($responseCodeTypes[$voterReport['Rid']]) {
            case 'Walk':
              $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
              break;
            case 'Phone':
              $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEPHONE;
              break;
            case 'Postcard':
              $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEPOSTCARD;
              break;
            default:
              $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
              break;
          }
          $dateTimeObj = new DateTime($voterReport['Cdate']); 
          $canvassDate = $dateTimeObj->format(DateTime::ATOM);
          $surveyResponse['dateCanvassed'] = $canvassDate;
          $apiSurveyQuestionObj->setApiSurveyResponse($apiAuthenticationObj,0,NULL,$surveyResponse);
          $nlReportsObj->setNlpReportRecorded($voterReport['Rindex']);
          break;
        case 'Survey':
          $surveyResponse['vanid'] = $vanid;
          $surveyResponse['qid'] = $voterReport['Qid'];
          $surveyResponse['rid'] = $voterReport['Rid'];
          $dateTimeObj = new DateTime($voterReport['Cdate']); 
          $canvassDate = $dateTimeObj->format(DateTime::ATOM);
          $surveyResponse['dateCanvassed'] = $canvassDate;
          $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
          $apiSurveyQuestionObj->setApiSurveyResponse($apiAuthenticationObj,0,$responseObj,$surveyResponse);
          $nlReportsObj->setNlpReportRecorded($voterReport['Rindex']);
          break;
        case 'ID':
          $surveyResponse['vanid'] = $vanid;
          $surveyResponse['qid'] = $voterReport['Qid'];
          $surveyResponse['rid'] = $voterReport['Rid'];
          $dateTimeObj = new DateTime($voterReport['Cdate']); 
          $canvassDate = $dateTimeObj->format(DateTime::ATOM);
          $surveyResponse['dateCanvassed'] = $canvassDate;
          $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
          $apiSurveyQuestionObj->setApiSurveyResponse($apiAuthenticationObj,0,$responseObj,$surveyResponse);
          $nlReportsObj->setNlpReportRecorded($voterReport['Rindex']);
          break;
        case 'Activist':
          //watchdog('voterdb_results_notify', 'survey obj: @rec', array('@rec' =>  print_r($apiSurveyQuestionObj, true)),WATCHDOG_DEBUG);
          $surveyResponse['contactType'] = $apiSurveyQuestionObj::CONTACTTYPEWALK;
          $surveyResponse['dateCanvassed'] = NULL;
          $surveyResponse['vanid'] = $vanid;
          $surveyResponse['action'] = $voterReport['Value'];
          $surveyResponse['rid'] = $voterReport['Rid'];
          $apiSurveyQuestionObj->setApiSurveyResponse($apiAuthenticationObj,0,$responseObj,$surveyResponse);
          $nlReportsObj->setNlpReportRecorded($voterReport['Rindex']);
          break;
        case 'Comment':
          $nlReportsObj->setNlpReportRecorded($voterReport['Rindex']);
          break;
      }
    }
  }
}
