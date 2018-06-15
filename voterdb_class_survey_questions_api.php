<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiSurveyQuestions.
 */

namespace Drupal\voterdb;

//require_once "voterdb_constants_van_api_tbl.php";

class ApiSurveyResponse { 
  
  public function __construct() {
    //$this->surveyQuestionId = NULL;
    //$this->surveyResponseId = NULL;
    //$this->type = self::SURVEYRESPONSE;
    $this->type = NULL;
  }
}

class ApiSurveyContext {
  const INPUTTYPEAPI = 11;
  
  public function __construct() {
    $this->contactTypeId = NULL;
    $this->inputTypeId = self::INPUTTYPEAPI;
    $this->dateCanvassed = NULL;
  }
}


class ApiSurveyQuestions {
  
  const SURVEYRESPONSE = 'SurveyResponse';
  const ACTIVISTCODE = 'ActivistCode';
  
  const CONTACTTYPEPOSTCARD = 7;
  const CONTACTTYPEWALK = 2;
  const CONTACTTYPEPHONE = 1;

  //public function __construct($contextObj) {
  public function __construct($contextObj) { 
    $this->canvassContext = empty($contextObj)?NULL:$contextObj;
    $this->resultCodeId = NULL;
    //$responses[] = $responseObj;
    //$this->responses = $responses;
  }
  
 /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * getApiSurveyQuestions
 * 
 * 
 */
  public function getApiSurveyQuestions($countyAuthenticationObj,$database,$questionType) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $questionsURL = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/surveyQuestions';
    $ch = curl_init($questionsURL);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('setopt exec error', curl_error($ch),__FILE__, __LINE__);
      curl_close($ch);
      return FALSE;
    }
    curl_close($ch);
    $resultObj = json_decode($result);
    //voterdb_debug_msg('survey questions',$resultObj,__FILE__, __LINE__);
    $questionsArray = array();
    foreach ($resultObj->items as $questionsObj) {
      $type = $questionsObj->type;
      if($questionType=='All' OR $questionType==$type) {
        $questionId = $questionsObj->surveyQuestionId;
        $questionsArray[$questionId]['name'] = $questionsObj->name; 
        $questionsArray[$questionId]['qid'] = $questionsObj->surveyQuestionId;
        $questionsArray[$questionId]['cycle'] = $questionsObj->cycle;
        $questionsArray[$questionId]['type'] = $questionsObj->type;
        $questionsArray[$questionId]['scriptQuestion'] = $questionsObj->scriptQuestion;
        foreach ($questionsObj->responses as $responsesObj) {
          $questionsArray[$questionId]['responses'][$responsesObj->surveyResponseId] = $responsesObj->mediumName; 
        }    
      }
    }
    $this->result = $questionsArray;
  return $this;
  }
  
  //public function setApiSurveyResponse($countyAuthenticationObj,$database,ApiSurveyResponse $responseObj,$surveyResponse) {
  public function setApiSurveyResponse($countyAuthenticationObj,$database,$responseObj,$surveyResponse) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/people/'.$surveyResponse['vanid'].'/canvassResponses';
    //drupal_set_message("url: ".'<pre>'.print_r($url, true).'</pre>','status');
    //drupal_set_message("survey this: ".'<pre>'.print_r($this, true).'</pre>','status');
    $this->canvassContext->contactTypeId = $surveyResponse['contactType'];
    switch ($surveyResponse['type']) {
      
      case 'Survey':
      case 'ID':
        $responses[] = $responseObj;
        $this->responses = $responses;
        $this->responses[0]->type = self::SURVEYRESPONSE;
        $this->responses[0]->surveyQuestionId = $surveyResponse['qid'];
        $this->responses[0]->surveyResponseId = $surveyResponse['rid'];
        $this->responses[0]->action = NULL;
        $this->canvassContext->dateCanvassed = $surveyResponse['dateCanvassed'];
        $this->resultCodeId = NULL;
        break;
      
      case 'Activist':
        $responses[] = $responseObj;
        $this->responses = $responses;
        $this->responses[0]->type = self::ACTIVISTCODE;
        $this->responses[0]->surveyQuestionId = NULL;
        $this->responses[0]->surveyResponseId = NULL;
        $this->responses[0]->activistCodeId = $surveyResponse['rid'];
        $this->responses[0]->action = ($surveyResponse['action']==1)?'Apply':'Remove';
        $this->canvassContext->dateCanvassed = $surveyResponse['dateCanvassed'];
        //$this->responses = NULL;
        break;
      
      case 'Contact':
        //$this->responses[0]->type = self::SURVEYRESPONSE;
        $this->resultCodeId = $surveyResponse['rid'];
        $this->canvassContext->dateCanvassed = $surveyResponse['dateCanvassed'];
        $this->responses = NULL;
        break;
    }
    //drupal_set_message("this: ".'<pre>'.print_r($this, true).'</pre>','status');
    //$data = http_build_query($surveyResponse);
    $data = json_encode($this);
    $options = array(
      'method' => 'POST',
      'data' => $data,
      'headers' => array('Content-Type' => 'application/json'),
    );
    //drupal_set_message("options: ".'<pre>'.print_r($options, true).'</pre>','status');
    $result = drupal_http_request($url, $options);
    $code = $result->code;
    if($code != 204) {
      drupal_set_message("Response: ".'<pre>'.print_r($result, true).'</pre>','status');
    }
  }
}
