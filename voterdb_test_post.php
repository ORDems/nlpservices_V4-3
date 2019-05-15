<?php

require_once "voterdb_debug.php";
require_once "voterdb_class_api_authentication.php";


use Drupal\voterdb\ApiAuthentication;



function voterdb_test() {
  $output = "test started";
  $scheme =  'https';

  $apiURL = "api.securevan.com/v4";
  $apiKey = '35abc3d4-bf8d-d9bb-5877-efffe189c638';

  $user = 'demo.spacker.api';
  
  $database = 0;
  $vanid = '813027';

  
  $url = 'https://'.$apiURL.'/people/'.$vanid.'/canvassResponses';
  
  drupal_set_message("getURL: ".'<pre>'.print_r($url, true).'</pre>','status');

  $password = $apiKey.'|'.$database; 

  $ch = curl_init($url);

 // if(!curl_setopt($ch, CURLOPT_HEADER, array("Content-Type: application/json", "Accept: application/json"))) {
 //   voterdb_debug_msg('setopt HEADER error', curl_error($ch));
 // }
  if(!curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password)) {
    voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
  }

  if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
    voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
  }
  
  curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Expect:"));
  
  
  $canvassContextObj = new stdClass();
  $canvassContextObj->contactTypeId = 2;  // walk
  $canvassContextObj->dateCanvassed = NULL;
  
  $surveyResponse = new stdClass();
  $responseObj = new stdClass();
  $surveyResponse->canvassContext = $canvassContextObj;
  $responses[] = $responseObj;
  $surveyResponse->responses = $responses;
  $surveyResponse->responses[0]->type = 'ActivistCode';
  $surveyResponse->responses[0]->surveyQuestionId = NULL;
  $surveyResponse->responses[0]->surveyResponseId = NULL;
  $surveyResponse->responses[0]->activistCodeId = '4454860'; // nlp voter
  $surveyResponse->responses[0]->action = 'Apply';
  
  $data = json_encode($surveyResponse);
  voterdb_debug_msg('data', $data);
  //extract data from the post
  //set POST variables

  $fields = array(
    'data' => $data
  );
  $fields_string = '';
  //url-ify the data for the POST
  foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
  rtrim($fields_string, '&');
  voterdb_debug_msg('fields', $fields_string);
  // number of POST vars, POST data

  curl_setopt($ch,CURLOPT_POST, 1);
  curl_setopt($ch,CURLOPT_POSTFIELDS, $data);



  $result = curl_exec($ch);

  if($result === FALSE) {
    voterdb_debug_msg('setopt exec error', curl_error($ch));
  }
  $info = curl_getinfo($ch);
  voterdb_debug_msg('info', $info);
  voterdb_debug_msg('result', $result);
  voterdb_debug_msg('curl hdl', $ch);
  curl_close($ch);
  
  $output .= "<br>test complete";
  return array('#markup' => $output); 


}
