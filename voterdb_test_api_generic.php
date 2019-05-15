<?php




require_once "voterdb_debug.php";
require_once "voterdb_class_api_authentication.php";
require_once "voterdb_class_folders.php";
require_once "voterdb_class_survey_questions_api.php";
require_once "voterdb_class_survey_question.php";
require_once "voterdb_class_voter_api.php";
require_once "voterdb_class_nlpreports.php";
require_once "voterdb_class_response_codes_api.php";
require_once "voterdb_class_response_codes.php";
require_once "voterdb_class_canvass_response_api.php";

use Drupal\voterdb\ApiAuthentication;
use Drupal\voterdb\ApiFolders;
use Drupal\voterdb\ApiSurveyQuestions;
use Drupal\voterdb\ApiVoter;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\ApiResponseCodes;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\ApiCanvassResponse;
use Drupal\voterdb\NlpSurveyQuestion;


/*
 *  
  VAN Application Name: spackerAPIUser
  VAN API Key: 2DE4BDF0-4B5E-455B-81A4-865FA962C206
 */
function voterdb_api_requests($form, &$form_state) {
  if (isset($form_state['voterdb']['reenter'])) {
    $resourceType = $form_state['values']['resourcetype'];
  } else {
    $resourceType = 0;
    $form_state['voterdb']['database'] = 0;
  }
  $database = $form_state['voterdb']['database'];
  //$scheme =  'https';
  
  voterdb_debug_msg('voterdb', $form_state['voterdb'],__FILE__,__LINE__);
  
  
  $turl =  'https://www.howsmyssl.com/a/check';
  $ch = curl_init($turl);
  $result = curl_exec($ch);

  if($result === FALSE) {
    voterdb_debug_msg('setopt exec error', curl_error($ch),__FILE__,__LINE__);
  }
  $info = curl_getinfo($ch);
  voterdb_debug_msg('info', $info,__FILE__,__LINE__);
  voterdb_debug_msg('result', $result,__FILE__,__LINE__);
  voterdb_debug_msg('curl hdl', $ch,__FILE__,__LINE__);
  curl_close($ch);

  
  
  $apiAuthenticationObj = new ApiAuthentication;
  
  //voterdb_debug_msg('authentication object', $apiAuthenticationObj,__FILE__,__LINE__);
  
  $countyAuthenticationObj = $apiAuthenticationObj->getApiAuthentication('Washington');
  
  
  $vanids = array(116744,986862,453307);
  $vanid = $vanids[0];
  
/*
  $foldersObj = new ApiFolders($countyAuthenticationObj,$database);
  //voterdb_debug_msg('Folders object', $foldersObj,__FILE__,__LINE__);
  
  $folderInfo = $foldersObj->getApiFolders($countyAuthenticationObj,$database);
  voterdb_debug_msg('Folders info', $folderInfo,__FILE__,__LINE__);
 * 
 */
  
  

  $questionsObj = new ApiSurveyQuestions();
  //voterdb_debug_msg('Folders object', $questionsObj,__FILE__,__LINE__);
  
  $questionsInfoObj = $questionsObj->getApiSurveyQuestions($countyAuthenticationObj,$database);
  voterdb_debug_msg('Questions info', $questionsInfoObj,__FILE__,__LINE__);
  
  
  $questionObj = new NlpSurveyQuestion();
  voterdb_debug_msg('question object', $questionObj,__FILE__,__LINE__);
  
  $surveyQuestionId = 284452;
  $surveyQuestionS = $questionsInfoObj->result;
  $surveyQuestion = $surveyQuestionS[$surveyQuestionId];
  voterdb_debug_msg('question array', $surveyQuestion,__FILE__,__LINE__);
  
  $questionObj->setSurveyQuestion($surveyQuestion,$surveyQuestionId);
  voterdb_debug_msg('question array set', '',__FILE__,__LINE__);
  
  $surveyQuestionArray = $questionObj->getSurveyQuestion();
  voterdb_debug_msg('Question', $surveyQuestionArray,__FILE__,__LINE__);
  
  /*
  $voterObj = new ApiVoter();
  //voterdb_debug_msg('Folders object', $voterObj,__FILE__,__LINE__);
  
  $voterInfo = $voterObj->getApiVoter($countyAuthenticationObj,$database,$vanid);
  voterdb_debug_msg('Voter info', $voterInfo,__FILE__,__LINE__);
  
  
  $nlpreportsObj = new NlpReports();
  $reports = $nlpreportsObj->getNlpReports('Washington');
  voterdb_debug_msg('nlreports', $reports,__FILE__,__LINE__);
  
  
  $nlpresponseObj = new ApiResponseCodes();
  $contactTypes = $nlpresponseObj->getApiContactTypes($countyAuthenticationObj,$database);
  voterdb_debug_msg('contact types', $contactTypes,__FILE__,__LINE__);
  
  $expectedContactTypes = array(
      'Walk' => array('Canvassed','Left Message/Lit','Refused', 'Inaccessible','Deceased', 'No Such Address','Hostile','Moved'),
      'Phone'=>array('Canvassed','Left Message','Disconnected','Wrong Number'),
      'Postcard'=>array('Mailed')
      );
  
  foreach ($expectedContactTypes as $contactName=>$eResult) {
    if(!empty($contactTypes[$contactName])) {
      $contactTypeId = $contactTypes[$contactName];
      $knownContactTypes[$contactName]['code'] = $contactTypeId;
      $resultCodes = $nlpresponseObj-> getApiResultCodes($countyAuthenticationObj,$database,$contactTypeId);
      voterdb_debug_msg('response codes', $resultCodes,__FILE__,__LINE__);
      
      foreach ($eResult as $expectedResult) {
        if(!empty($resultCodes[$expectedResult])) {
          $resultCodeId = $resultCodes[$expectedResult];
          $knownContactTypes[$contactName]['responses'][$expectedResult] = $resultCodeId;
        }
      }
      

    } else {
      $knownContactTypes[$contactName]['code'] = NULL;
    }
  }
   voterdb_debug_msg('known contact types', $knownContactTypes,__FILE__,__LINE__);
  
   $responseCodesObj = new NlpResponseCodes();
   $responseCodesObj->setNlpResponseCodes($knownContactTypes);
   
   $knownResponseCodes = $responseCodesObj->getNlpResponseCodes();
   voterdb_debug_msg('known response codes', $knownResponseCodes,__FILE__,__LINE__);
   
  /**
  $canvassReport = '   
    {
      "canvassContext": {
        "contactTypeId": 2,
        "inputTypeId": 14,
        "dateCanvassed": "2012-04-09T00:00:00-04:00"
      },
      "resultCodeId": null,
      "responses": [
        {
          "activistCodeId": 18917,
          "action": "Apply",
          "type": "ActivistCode"
        },
        {
          "volunteerActivityId": 3425,
          "action": "Apply",
          "type": "VolunteerActivity"
        },
        {
          "surveyQuestionId": 109149,
          "surveyResponseId": 465468,
          "type": "SurveyResponse"
        }
      ]
    }     ';
  
  $canvassObj = json_decode($canvassReport);
  voterdb_debug_msg('canvass report', $canvassObj,__FILE__,__LINE__);
          
   * 
   */
  
  $canvassReport = '
  {"canvassContext":{"contactTypeId":2,"inputTypeId":11,"dateCanvassed":"2018-04-07T16:58:49-0700"},"resultCodeId":14}
  ';
  
  $testObj = json_decode($canvassReport);
  voterdb_debug_msg('canvass report', $testObj,__FILE__,__LINE__);
  
  $contextObj = new stdClass();
  $canvassObj = new ApiCanvassResponse($contextObj);
  voterdb_debug_msg('Folders object', $canvassObj,__FILE__,__LINE__);
  

  $dateCanvassed = date("Y-m-d\TH:i:sO" ,time());
  $code = 14;
  $canvassSet = $canvassObj->setApiResponseCode($countyAuthenticationObj,$database,$vanid,$dateCanvassed,$code);
  voterdb_debug_msg('canvass set', $canvassSet,__FILE__,__LINE__);
   
   
   
   
 
  
  $const = voterdb_resource_def();
  $resourceTypes = $const['resourcetypes'];
  $person_list = $const['personlist'];
  $peopleResourceName = $const['peopleResourceName'];
  $canvassResourceName = $const['canvassResourceName'];
  $acResourceName = $const['acResourceName'];
  $surveyResourceName = $const['surveyResourceName'];
  $codesResourceName = $const['codesResourceName'];
  $notesResourceName = $const['notesResourceName'];
  $foldersResourceName = $const['foldersResourceName'];
  $listsResourceName = $const['listsResourceName'];
  $exportJobsResourceName = $const['exportJobsResourceName'];
  
  
  $form['resource-selected'] = array(
    '#type' => 'fieldset',
    '#prefix' => '<div id="resource-wrapper">',
    '#suffix' => '</div>',
    );
  
  $databaseName = ($database==0)?'VANID':'MCID';
  $form['resource-selected']['database'] = array(
    '#type' => 'select',
    '#options' => array('VoterFile','MyCampaign'),
    '#default_value' => $database,
    '#title' => 'Select the database',
    '#id' => 'database',
    '#ajax' => array(
      'callback' => 'voterdb_resource_callback',
      'wrapper' => 'resource-wrapper',
      'effect' => 'fade',
    )
  );
  
  
  $form['resource-selected']['resourcetype'] = array(
    '#type' => 'select',
    '#options' => $resourceTypes,
    '#default_value' => $resourceType,
    '#title' => 'Select the resource type',
    '#ajax' => array(
      'callback' => 'voterdb_resource_callback',
      'wrapper' => 'resource-wrapper',
      'effect' => 'fade',
    )
  );
  
  $resourceName = $resourceTypes[$resourceType];
  switch ($resourceName) {
    case 'People':
      $form['resource-selected']['person'] = array(
        '#type' => 'select',
        '#options' => $person_list,
        '#title' => 'Select the person',
      );
      $form['resource-selected']['people-resource'] = array(
        '#type' => 'select',
        '#options' => $peopleResourceName,
        '#title' => 'Select the resource',
      );
      break;
    case 'Canvass Responses':
      $form['resource-selected']['canvass-resource'] = array(
        '#type' => 'select',
        '#options' => $canvassResourceName,
        '#title' => 'Select the resource',
      );
      break;
    case 'Activist Codes':
      $form['resource-selected']['ac-resource'] = array(
        '#type' => 'select',
        '#options' => $acResourceName,
        '#title' => 'Select the resource',
      );
      //voterdb_debug_msg('voterdb', $form_state['voterdb'],__FILE__,__LINE__);
      //voterdb_debug_msg('databasename: '.$databaseName, '',__FILE__,__LINE__);
      //$acCodeSet = isset($form_state['voterdb']['activistCodeIds']);
      //if($acCodeSet) {
      //  voterdb_debug_msg('activistCodeIds', $form_state['voterdb']['activistCodeIds'],__FILE__,__LINE__);
      //}
      //voterdb_debug_msg('acCodeSet: ', $acCodeSet,__FILE__,__LINE__);
      if (isset($form_state['voterdb']['activistCodeIds'][$databaseName])) {
        $activistCodeNames = $form_state['voterdb']['activistCodeIds'][$databaseName]['name'];
        $form['resource-selected']['acId'] = array(
          '#type' => 'select',
          '#options' => $activistCodeNames,
          '#title' => 'Select the activist code ID',
        );
      }
      break;
      
    case 'Survey Questions':
      $form['resource-selected']['survey-resource'] = array(
        '#type' => 'select',
        '#options' => $surveyResourceName,
        '#title' => 'Select the resource',
      );
      if (isset($form_state['voterdb']['surveyQuestionIds'][$databaseName])) {
        $surveyQuestionNames = $form_state['voterdb']['surveyQuestionIds'][$databaseName]['name'];
        $form['resource-selected']['surveyId'] = array(
          '#type' => 'select',
          '#options' => $surveyQuestionNames,
          '#title' => 'Select the survey question ID',
        );
      }
      break;
      
    case 'Codes':
      $form['resource-selected']['codes-resource'] = array(
        '#type' => 'select',
        '#options' => $codesResourceName,
        '#title' => 'Select the resource',
      );
      if (isset($form_state['voterdb']['codeIds'][$databaseName])) {
        $codesNames = $form_state['voterdb']['codeIds'][$databaseName]['name'];
        $form['resource-selected']['codesId'] = array(
          '#type' => 'select',
          '#options' => $codesNames,
          '#title' => 'Select the survey question ID',
        );
      }
      break;
      
    case 'Events':
      break;
    case 'Event Types':
      break;
    case 'Signups':
      break;
    case 'Locations':
      break;
    case 'Notes':
      $form['resource-selected']['notes'] = array(
        '#type' => 'select',
        '#options' => $notesResourceName,
        '#title' => 'Select the notes resource',
      );
      if (isset($form_state['voterdb']['noteCatagoryId'])) {
        $noteCatagoryIds = $form_state['voterdb']['noteCatagoryId'];
        $form['resource-selected']['notesid'] = array(
          '#type' => 'select',
          '#options' => $noteCatagoryIds,
          '#title' => 'Select the note catagory ID',
        );
      }
      break;
      
    case 'Folders':
      $form['resource-selected']['folders'] = array(
        '#type' => 'select',
        '#options' => $foldersResourceName,
        '#title' => 'Select the notes resource',
      );
      //if (isset($form_state['voterdb']['foldersId'])) {
        //$foldersIds = $form_state['voterdb']['foldersId'];
        $form['resource-selected']['foldersid'] = array(
          '#type' => 'select',
          //'#options' => $foldersIds,
          '#options' => array('Washington','Lincoln'),
          '#title' => 'Select the folder ID',
        );
      //}
      
      
      break;
    case 'Saved Lists':
      
      $form['resource-selected']['savedlists'] = array(
        '#type' => 'select',
        '#options' => $listsResourceName,
        '#title' => 'Select the notes resource',
      );
      //if (isset($form_state['voterdb']['foldersId'])) {
        //$foldersIds = $form_state['voterdb']['foldersId'];
        $form['resource-selected']['listsid'] = array(
          '#type' => 'select',
          //'#options' => $foldersIds,
          '#options' => array('list 1','list 2'),
          '#title' => 'Select the folder ID',
        );
      //}
      break;
    
    case 'Export Jobs':
      
      $form['resource-selected']['exportjobs'] = array(
        '#type' => 'select',
        '#options' => $exportJobsResourceName,
        '#title' => 'Select the export jobs resource',
      );
      //if (isset($form_state['voterdb']['foldersId'])) {
        //$foldersIds = $form_state['voterdb']['foldersId'];
        $form['resource-selected']['exportJobsID'] = array(
          '#type' => 'select',
          //'#options' => $foldersIds,
          '#options' => array('list 1','list 2'),
          '#title' => 'Select the list ID',
        );
      //}
      break;
      
      
    default:
      break;
  }
  
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Submit'),
    '#id' => 'Submit',
  );
 
  return $form;
}

// ========================================================================

function voterdb_api_requests_validate($form, &$form_state) {
  $form_state['voterdb']['reenter'] = true;
  //voterdb_debug_msg('verify: values', $form_state['values'],__FILE__,__LINE__);
  //voterdb_debug_msg('verify: voters', $form_state['voterdb'],__FILE__,__LINE__);
  $triggeringElement = $form_state['triggering_element']['#id'];
  if($triggeringElement != 'database') {
    $form_state['voterdb']['database'] = $form_state['values']['database'];
    return;
  }
}

// ========================================================================

function voterdb_api_requests_submit($form, &$form_state) {
  
  //voterdb_debug_msg('verify: submit voters', $form_state['voterdb'],__FILE__,__LINE__);
  //voterdb_debug_msg('verify: submit values', $form_state['values'],__FILE__,__LINE__);

  $form_state['voterdb']['reenter'] = true;
  $form_state['rebuild'] = true;
  $triggeringElement = $form_state['triggering_element']['#id'];
  if($triggeringElement != 'Submit') {return;}
  
  voterdb_debug_msg('voterdb', $form_state['voterdb'],__FILE__,__LINE__);
  $const = voterdb_resource_def();
  $resourceTypes = $const['resourcetypes'];
  //$person_list = $const['personlist'];
  $peopleResourceName = $const['peopleResourceName'];
  $canvassResourceName = $const['canvassResourceName'];
  $acResourceName = $const['acResourceName'];
  $surveyResourceName = $const['surveyResourceName'];
  $codesResourceName = $const['codesResourceName'];
  $notesResourceName = $const['notesResourceName'];
  $foldersResourceName = $const['foldersResourceName'];
  $listsResourceName = $const['listsResourceName'];
  
  
  //$resourceTypes = $form_state['voterdb']['resourcetypes'];

  $scheme =  'https';
  $expand = '?$expand=phones,emails,addresses,codes,districts,electionRecords';

  $apiURL = "api.securevan.com/v4";
  $apiKey = 'f5cf3c1f-6c0c-dd4c-307a-15c922659464';

  $user = 'demo.spacker.api';

  //$triggeringElement = $form_state['triggering_element']['#id'];
  if($triggeringElement != 'Submit') {return;}

  $database = $form_state['values']['database'];
  $databaseName = ($database==0)?'VANID':'MCID';

  $persons = $const['persons'];
  
  $resourceType = $form_state['values']['resourcetype'];
  $resourceName = $resourceTypes[$resourceType];
  $resource = $resourceName;
  $expandOption = '';
  //voterdb_debug_msg('resourceName: '.$resourceName,'',__FILE__,__LINE__);
  switch ($resourceName) {
    case 'People':
      $peopleResourceNames = $const['peopleResourceName'];
      $peopleResource = $form_state['values']['people-resource'];
      $peopleResourceName = $peopleResourceNames[$peopleResource];
      $resource = $peopleResourceName;
      switch ($peopleResourceName) {
        case '/people/find':
          $httpType = 'POST';
          break;
        case '/people/findOrCreate':
          $httpType = 'POST';
          break;
        case '/people/{vanId}':
          $httpType = 'GET';
          
          $person = $form_state['values']['person'];
          $id = $persons[$person][$databaseName];
          voterdb_debug_msg('person: '.$person.' ID:'.$id, $persons,__FILE__,__LINE__);
          $resource = str_replace('{vanId}', $id, $resource);
          $expandOption = $expand;
          break;
        
        case '/people/{personIdType}:{personId})':
          $httpType = 'GET';
          break;
        case '/people/{vanId}/canvassResponses':
          $httpType = 'POST';
          break;
        case '/people/{personIdType}:{personId}/canvassResponses':
          $httpType = 'POST';
          break;
        default:
          break;
      }
      break;

    case 'Canvass Responses':
      $httpType = 'GET';
      $canvassResourceNames = $const['canvassResourceName'];
      $canvassResource = $form_state['values']['canvass-resource'];
      $canvassResourceName = $canvassResourceNames[$canvassResource];
      $resource = $canvassResourceName;
      break;
    
    case 'Activist Codes':
      $httpType = 'GET';
      $acResourceNames = $const['acResourceName'];
      $acResource = $form_state['values']['ac-resource'];
      $acResourceName = $acResourceNames[$acResource];
      $resource = $acResourceName;
      //voterdb_debug_msg('acResourceName: '.$acResourceName,'',__FILE__,__LINE__);
      //voterdb_debug_msg('voterdb ',$form_state['voterdb'],__FILE__,__LINE__);
      if($acResourceName=='/activistCodes/{activistCodeId}') {
        $acCodesSet = isset($form_state['voterdb']['activistCodeIds']);
        //voterdb_debug_msg('acCodesSet: '.$acCodesSet,'',__FILE__,__LINE__);
        if (!$acCodesSet) {
          return;
        }
        $acId = $form_state['values']['acId'];
        $acCode = $form_state['voterdb']['activistCodeIds'][$databaseName]['code'][$acId];
        voterdb_debug_msg('$acId: '.$acId.' $acCode: '.$acCode,'',__FILE__,__LINE__);
        $resource = str_replace('{activistCodeId}', $acCode, $acResourceName);  
      }
      break;
      
    case 'Survey Questions':
      $httpType = 'GET';
      $surveyResourceNames = $const['surveyResourceName'];
      $surveyResource = $form_state['values']['survey-resource'];
      $surveyResourceName = $surveyResourceNames[$surveyResource];
      $resource = $surveyResourceName;

      if($surveyResourceName=='/surveyQuestions/{surveyQuestionId}') {
        $surveyCodesSet = isset($form_state['voterdb']['surveyQuestionIds']);
        if (!$surveyCodesSet) {
          return;
        }
        $surveyId = $form_state['values']['surveyId'];
        $surveyCode = $form_state['voterdb']['surveyQuestionIds'][$databaseName]['code'][$surveyId];
        voterdb_debug_msg('$surveyId: '.$surveyId.' $surveyCode: '.$surveyCode,'',__FILE__,__LINE__);
        $resource = str_replace('{surveyQuestionId}', $surveyCode, $surveyResourceName);  
      }
      break;
      
    case 'Codes':
      $codesResourceNames = $const['codesResourceName'];
      $codesResource = $form_state['values']['codes-resource'];
      $codesResourceName = $codesResourceNames[$codesResource];
      $resource = $codesResourceName;
      switch ($codesResourceName) {
        case '/codes/supportedEntities':
          $httpType = 'GET';
          break;
        case '/codes':
          $httpType = 'GET';
          break;
        case '/codes/{codeId}':
          $httpType = 'GET';
          break;
        case '{POST}/codes/{codeId}':
          $httpType = 'POST';
          $resource = '/codes/';
          break;
        case '{PUT}/codes':
          $httpType = 'PUT';
          $resource = '/codes';
          break;
        case '{DELETE}/codes/{codeId}':
          $httpType = 'DELETE';
          $resource = '/codes/';
          break;

        default:
          break;
      }
      break;
    
    case 'Events':
      break;
    case 'Event Types':
      break;
    case 'Signups':
      break;
    case 'Locations':
      break;
    case 'Notes':
      $notesResourceName = $const['notesResourceName'];
      $httpType = 'GET';
      $notes = $form_state['values']['notes'];
      $noteName = $notesResourceName[$notes];
      $resource = $noteName;
      switch ($noteName) {
        case '/notes/categoryTypes':
          break;
        case '/notes/categories':
          break;
        case '/notes/categories/{noteCategoryId}':
          if (!isset($form_state['voterdb']['noteCatagoryId'])) {
            return;
          }
          break;
        default:
          break;
      }
      
      break;
      
    case 'Folders':
      
      $foldersResourceName = $const['foldersResourceName'];
      $httpType = 'GET';
      $folders = $form_state['values']['folders'];
      $rName = $foldersResourceName[$folders];
      $resource = $rName;
      voterdb_debug_msg('folders', $folders,__FILE__,__LINE__);
      voterdb_debug_msg('resource name', $foldersResourceName,__FILE__,__LINE__);
      voterdb_debug_msg('resource', $resource,__FILE__,__LINE__);
      switch ($rName) {
        case '/folders':
          break;
        case '/folders/{foldersId}':
          //if (!isset($form_state['voterdb']['folderId'])) {
          //  return;
          //}
          $resource = '/folders/357';
          break;
        default:
          break;
      }
      
      break; 
    
    case 'Saved Lists':
      
      $listsResourceName = $const['listsResourceName'];
      $httpType = 'GET';
      $lists = $form_state['values']['savedlists'];
      $lName = $listsResourceName[$lists];
      $resource = $lName;
      voterdb_debug_msg('lists', $lists,__FILE__,__LINE__);
      voterdb_debug_msg('resource name', $listsResourceName,__FILE__,__LINE__);
      voterdb_debug_msg('resource', $resource,__FILE__,__LINE__);
      switch ($lName) {
        case '/savedLists':
          $resource = '/savedLists?folderID=1446';
          break;
        case '/savedLists/{savedListsId}':
          //if (!isset($form_state['voterdb']['folderId'])) {
          //  return;
          //}
          $resource = '/folders/1446';
          break;
        default:
          break;
      }
      
      
      break;  
    
    case 'Export Jobs':
      
      $exportJobsResourceName = $const['exportJobsResourceName'];
      $httpType = 'GET';
      $exportjobs = $form_state['values']['exportjobs'];
      $jName = $exportJobsResourceName[$exportjobs];
      $resource = $jName;
      voterdb_debug_msg('export jobs', $exportjobs,__FILE__,__LINE__);
      voterdb_debug_msg('resource name', $exportJobsResourceName,__FILE__,__LINE__);
      voterdb_debug_msg('resource', $resource,__FILE__,__LINE__);
      switch ($jName) {
        case '/exportJobTypes':
          $httpType = 'GET';
          break;
        case '/exportJobs':
          $httpType = 'POST';
          $data_array = array(
            "savedListId" => 1234,
            "type" => 777,
            "webhookUrl" => "http://www.nlpdevelopment.org/nlptest"
          );
          break;
        case '/exportJobs/{exportJobId}':
          $httpType = 'GET';
          $resource = '/exportJobs/357';
          break;
        default:
          break;
      }
      
      
      break;
    
      
      
    default:
      break;
  }
  
  if($httpType == 'GET') {
    $password = $apiKey.'|'.$database;
    $getURL = $scheme .'://'.$user.':'.$password.'@'.$apiURL . $resource . $expandOption;
      drupal_set_message("getURL: ".'<pre>'.print_r($getURL, true).'</pre>','status');

      /*
      $aResponse = drupal_http_request($getURL);
      drupal_set_message("Response: ".'<pre>'.print_r($aResponse, true).'</pre>','status');
      $sweet = $aResponse->code;
      if ($sweet == 200) {
        $arr = json_decode($aResponse->data, true);
        //drupal_set_message("response array: ".'<pre>'.print_r($arr, true).'</pre>','status');
      }
       * 
       */
      
      $ch = curl_init($getURL);
      
      if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
        voterdb_debug_msg('setopt HEADER error', curl_error($ch),__FILE__,__LINE__);
      }
      if(!curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password)) {
        voterdb_debug_msg('setopt USERPWD error', curl_error($ch),__FILE__,__LINE__);
      }
 
      if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
        voterdb_debug_msg('setopt USERPWD error', curl_error($ch),__FILE__,__LINE__);
      }

      
      $result = curl_exec($ch);
      
      if($result === FALSE) {
        voterdb_debug_msg('setopt exec error', curl_error($ch),__FILE__,__LINE__);
      }
      $info = curl_getinfo($ch);
      voterdb_debug_msg('info', $info,__FILE__,__LINE__);
      voterdb_debug_msg('result', $result,__FILE__,__LINE__);
      voterdb_debug_msg('curl hdl', $ch,__FILE__,__LINE__);
      curl_close($ch);

      
      
      
      
      switch ($resource) {
        case '/notes/categories':
          $noteCatagory = json_decode($aResponse->data, true);
          voterdb_debug_msg('notes/categories', $noteCatagory,__FILE__,__LINE__);
          break;
        case '/activistCodes':
          $activistCodeIDs = json_decode($aResponse->data, true);
          $form_state['voterdb']['activistCodeIds'][$databaseName] = voterdb_ac($activistCodeIDs);
          //voterdb_debug_msg('activistCodeIDs', $form_state['voterdb']['activistCodeIDs'],__FILE__,__LINE__);
          //voterdb_debug_msg('voterdb', $form_state['voterdb'],__FILE__,__LINE__);
          break;
        case '/surveyQuestions':
          $surveyQuestionIds = json_decode($aResponse->data, true);
          $form_state['voterdb']['surveyQuestionIds'][$databaseName] = voterdb_survey($surveyQuestionIds);
          //voterdb_debug_msg('activistCodeIDs', $form_state['voterdb']['activistCodeIDs'],__FILE__,__LINE__);
          //voterdb_debug_msg('voterdb', $form_state['voterdb'],__FILE__,__LINE__);
          break;
        case '/codes':
          $codeIds = json_decode($aResponse->data, true);
          $form_state['voterdb']['codesIds'][$databaseName] = voterdb_codes($codeIds);
          break;

        default:
        break;
      }
      return "GET completed";
  } else {
     // POST selected
      $password = $apiKey.'|'.$database;
      $post_url = $scheme .'://'.$user.':'.$password.'@'.$apiURL . $resource;
      drupal_set_message("post_url: ".'<pre>'.print_r($post_url, true).'</pre>','status');
      
      

      $data = http_build_query($data_array);

      //$data = json_encode($data_array);
      //drupal_set_message("data: ".'<pre>'.print_r($data, true).'</pre>','status');
      $options = array(
        'method' => 'POST',
        'data' => $data,
        'headers' => array('Content-Type' => 'application/json'),
      );
      drupal_set_message("options: ".'<pre>'.print_r($options, true).'</pre>','status');
      $result = drupal_http_request($post_url, $options);
      drupal_set_message("Response: ".'<pre>'.print_r($result, true).'</pre>','status');
  }
  
return "Hello World";
}


/**
 * This converts a string to hex for debugging
 * @param type $string
 * @return type
 */
function strToHex2($string) {
  $hex = '';
  for ($i = 0; $i < strlen($string); $i++) {
    $hex .= dechex(ord($string[$i]));
  }
  return $hex;
}

function build_phone_request($person) {
  $phone_req['phoneNumber'] = $person['phoneNumber'];
  $phone_req['phoneType'] = $person['phoneType'];
  if ($person['ext'] != '') {$phone_req['ext'] = $person['ext'];}
  if ($person['isPreferredPh'] != '') {$phone_req['isPreferredPh'] = $person['isPreferredPh'];}
  return $phone_req;
}

function build_em_request($person) {
  $em_req['email'] = $person['email'];
  if ($person['isPreferredEmail'] != '') {$em_req['isPreferredEmail'] = $person['isPreferredEmail'];}
  return $em_req;
}

function build_addr_request($person) {
  $addr_req['addressLine1'] = $person['addressLine1'];
  if ($person['addressLine2'] != '') {$addr_req['addressLine2'] = $person['addressLine2'];}
  if ($person['addressLine3'] != '') {$addr_req['addressLine3'] = $person['addressLine3'];}
  $addr_req['city'] = $person['city'];
  $addr_req['stateOrProvidence'] = $person['stateOrProvidence'];
  $addr_req['zipOrPostalCode'] = $person['zipOrPostalCode'];
  $addr_req['countryCode'] = $person['countryCode'];
  $addr_req['type'] = $person['type'];
  if ($person['isPreferredAddr'] != '') {$addr_req['isPreferredAddr'] = $person['isPreferredAddr'];}
  return $addr_req;
}

function build_data_request($person,$em,$ph,$ad) {
  $data_req['firstName'] = $person['firstName'];
  if ($person['middleName'] != '') {$data_req['middleName'] = $person['middleName'];}
  $data_req['lastName'] = $person['lastName'];
  if ($em != '') {$data_req['email'] = $em;}
  if ($ph != '') {$data_req['phone'] = $ph;}
  if ($ad != '') {$data_req['address'] = $ad;}
  return $data_req;
}

function voterdb_apitest() {
  $form = drupal_get_form('voterdb_api_requests');
  return $form;
}

function voterdb_resource_callback($form,$form_state) {
  return $form['resource-selected'];
}

function voterdb_database_callback($form,$form_state) {
  return $form['database'];
}

function voterdb_ac($json) {
  $i=0;
  $items = $json['items'];
  foreach ($items as $ac) {
    $ids['name'][$i] = $ac['type'].':'.$ac['name'].'['. $ac['activistCodeId'].']';
    $ids['code'][$i++] = $ac['activistCodeId'];
  }
  return $ids;
}

function voterdb_survey($json) {
  $i=0;
  $items = $json['items'];
  foreach ($items as $ac) {
    $ids['name'][$i] = $ac['type'].':'.$ac['name'].'['. $ac['surveyQuestionId'].']';
    $ids['code'][$i++] = $ac['surveyQuestionId'];
  }
  return $ids;
}

function voterdb_codes($json) {
  $i=0;
  $items = $json['items'];
  foreach ($items as $ac) {
    $ids['name'][$i] = $ac['name'].'['. $ac['codeId'].']';
    $ids['code'][$i++] = $ac['codeId'];
  }
  return $ids;
}


function voterdb_resource_def() {
  $resourceTypes = array('People','Canvass Responses','Activist Codes',
      'Survey Questions','Codes','Events','Event Types','Signups','Locations',
      'Notes','Folders','Saved Lists','Export Jobs');
  $const['resourcetypes'] = $resourceTypes;
  
// People
  $peopleResourceName = array('/people/find','/people/findOrCreate',
    '/people/{vanId}','/people/{personIdType}:{personId})',
    '/people/{vanId}/canvassResponses',
    '/people/{personIdType}:{personId}/canvassResponses',
    );
  $const['peopleResourceName'] = $peopleResourceName;
  
// Canvass Response
  $canvassResourceName = array('/canvassResponses/contactTypes',
    '/canvassResponses/inputTypes','/canvassResponses/resultCodes?contactTypeId=7',
    );
  $const['canvassResourceName'] = $canvassResourceName;
  
  // Activist Codes
  $acResourceName = array('/activistCodes','/activistCodes/{activistCodeId}',
    );
  $const['acResourceName'] = $acResourceName;
  
  // Survey Questions
  $surveyResourceName = array('/surveyQuestions', '/surveyQuestions/{surveyQuestionId}',
    );
  $const['surveyResourceName'] = $surveyResourceName;
  
  // Codes
  $codesResourceName = array('/codes/supportedEntities', '/codes',
    '/codes/{codeId}', '/codes','{POST}/codes/{codeId}', '{PUT}/codes',
    '{DELETE}/codes/{codeId}'
    );
  $const['codesResourceName'] = $codesResourceName;

  // Notes
  $notesResourceName = array ('/notes/categoryTypes','/notes/categories',
    '/notes/categories/{noteCategoryId}',
  );
  $const['notesResourceName'] = $notesResourceName;
  
  // Folders
  $foldersResourceName = array ('/folders','/folders/{foldersId}',
  );
  $const['foldersResourceName'] = $foldersResourceName;
  
  // Saved Lists
  $listsResourceName = array ('/savedLists','/savedLists/{savedListId}',
  );
  $const['listsResourceName'] = $listsResourceName;
  
  
  // Export jobs
  $exportJobsResourceName = array ('/exportJobTypes','/exportJobs','/exportJobs/{exportJobId}',
  );
  $const['exportJobsResourceName'] = $exportJobsResourceName;
  

  
  $person1 = array(
    'VANID' => '', 'MCID'=>'100476367',
    'firstName'=>'Steve', 'middleName'=>'J','lastName'=>'Packer',
    'addressLine1'=>'21355 SW Hillsboro Hwy', 'addressLine2'=>'','addressLine3'=>'',
    'city'=>'Newberg', 'stateOrProvidence'=>'OR','zipOrPostalCode'=>'97132',
    'countryCode'=>'US', 'type'=>'H','isPreferredAddr'=>'',
    'email'=>'steve.packer@yahoo.com', 'isPreferredEmail'=>true,
    'phoneNumber'=>'5036281222', 'phoneType'=>'C','ext'=>'','isPreferredPh'=>'',
  );

  $person2 = array(
    'VANID' => '', 'MCID'=>'100476374',
    'firstName'=>'John', 'middleName'=>'F','lastName'=>'Packer',
    'addressLine1'=>'21355 SW Hillsboro Hwy', 'addressLine2'=>'','addressLine3'=>'',
    'city'=>'Newberg', 'stateOrProvidence'=>'OR','zipOrPostalCode'=>'97132',
    'countryCode'=>'US', 'type'=>'H', 'isPreferredAddr'=>'',
    'email'=>'john.packer@yahoo.com', 'isPreferredEmail'=>'true',
    'phoneNumber'=>'5036281222', 'phoneType'=>'C','ext'=>'','isPreferredPh'=>'',
  );

  $person3 = array(
    'VANID' => '215500', 'MCID'=>'',
    'firstName'=>'Barbara', 'middleName'=>'M','lastName'=>'Hager',
    'addressLine1'=>'1915 Grand Isle Cir', 'addressLine2'=>'Apt 611B','addressLine3'=>'',
    'city'=>'Orlando', 'stateOrProvidence'=>'FL','zipOrPostalCode'=>'32810',
    'countryCode'=>'US', 'type'=>'H', 'isPreferredAddr'=>'',
    'email'=>'one@two.com', 'isPreferredEmail'=>'',
    'phoneNumber'=>'5554213818', 'phoneType'=>'H','ext'=>'','isPreferredPh'=>'true',
  );

  $person4 = array(
    'VANID' => '215501', 'MCID'=>'',
    'firstName'=>'Teresa', 'middleName'=>'L','lastName'=>'Steenhoek',
    'addressLine1'=>'3001 Tradewinds Trl', 'addressLine2'=>'','addressLine3'=>'',
    'city'=>'Orlando', 'stateOrProvidence'=>'FL','zipOrPostalCode'=>'32805',
    'countryCode'=>'US', 'type'=>'H', 'isPreferredAddr'=>'',
    'email'=>'', 'isPreferredEmail'=>'',
    'phoneNumber'=>'7815720256', 'phoneType'=>'C','ext'=>'','isPreferredPh'=>'true',
  );
  
  $person5 = array(
    'VANID' => '338396', 'MCID'=>'101077091',
    'firstName'=>'Kristen', 'middleName'=>'','lastName'=>'Anderson',
    'addressLine1'=>'3001 Tradewinds Trl', 'addressLine2'=>'','addressLine3'=>'',
    'city'=>'Orlando', 'stateOrProvidence'=>'FL','zipOrPostalCode'=>'32805',
    'countryCode'=>'US', 'type'=>'H', 'isPreferredAddr'=>'',
    'email'=>'', 'isPreferredEmail'=>'',
    'phoneNumber'=>'7815720256', 'phoneType'=>'C','ext'=>'','isPreferredPh'=>'true',
  );
  
  $persons = array($person1,$person2,$person3,$person4,$person5);
  $i = 0;
  foreach ($persons as $person) {
    $firstName = $person['firstName'];
    $lastName = $person['lastName'];
    $vanId = $person['VANID'];
    $mcId = $person['MCID'];
    $nameDisplay = $lastName.','.$firstName.' VANID['.$vanId.'], MCID['. $mcId.']';
    $person_list[$i++] = $nameDisplay;
  }
  $const['personlist'] = $person_list;
  $const['persons'] = $persons;
  return $const;
}
