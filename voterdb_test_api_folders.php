<?php




require_once "voterdb_debug.php";
require_once "voterdb_class_api_authentication.php";
require_once "voterdb_class_folders_api.php";
require_once "voterdb_class_saved_lists_api.php";
require_once "voterdb_class_survey_questions_api.php";
require_once "voterdb_class_survey_question_nlp.php";
require_once "voterdb_class_voter_api.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_class_response_codes_api.php";
require_once "voterdb_class_response_codes.php";
require_once "voterdb_class_canvass_response_api.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_export_jobs_api.php";

use Drupal\voterdb\ApiAuthentication;
use Drupal\voterdb\ApiFolders;
use Drupal\voterdb\ApiSavedLists;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\ApiVoter;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\ApiResponseCodes;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\ApiCanvassResponse;
use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\ApiExportJobs;


/*
 *  
  VAN Application Name: spackerAPIUser
  VAN API Key: 2DE4BDF0-4B5E-455B-81A4-865FA962C206
 */
function voterdb_test() {

  $output = "test start";
  $stateCommittee = variable_get('voterdb_state_committee', '');
  $apiAuthenticationObj = new ApiAuthentication;
  
  //voterdb_debug_msg('authentication object', $apiAuthenticationObj);
  
  $authenticationObj = $apiAuthenticationObj->getApiAuthentication($stateCommittee);
  
  
  $database = 1;
  $foldersObj = new ApiFolders($authenticationObj);
  //voterdb_debug_msg('Folders object', $foldersObj);
  
  
  
  $savedListsObj = new ApiSavedLists($authenticationObj);
  
  
  $folderInfo = $foldersObj->getApiFolders($database,NULL);
  //voterdb_debug_msg('Folders info', $folderInfo);
  
  foreach ($folderInfo as $folderId => $folderName) {
    $output .= "<br>Folder Name: ".$folderName;
    $folderMore = $foldersObj->getApiFolders($database,$folderId);
    //voterdb_debug_msg('More folders info', $folderMore);
    
    $listsObj = $savedListsObj->getSavedLists($database,$folderId);
    //voterdb_debug_msg('savedlists', $savedListsObj);
    
    if(empty($listsObj)) {
      $output .= "<br>-No available saved lists";
    } else {
      foreach ($listsObj->items as $listObj) {
        $output .= "<br>-name: ".$listObj->name;
        $output .= "<br>--savedListId: ".$listObj->savedListId;
        $output .= "<br>--description: ".$listObj->description;
        $output .= "<br>--listCount: ".$listObj->listCount;
        
        $listDetail = $savedListsObj->getSavedList($database,$folderId,$listObj->savedListId);
        //voterdb_debug_msg('detail', $listDetail);
        
      }
    }
    
    
    
  }

  $exportJobsObj = new ApiExportJobs($authenticationObj);
  $exportJobsTypes = $exportJobsObj->getExportJobTypes($database);
  voterdb_debug_msg('jobtypes', $exportJobsTypes);

 
  $output .= "<br>test complete";
  return array('#markup' => $output); 
}



