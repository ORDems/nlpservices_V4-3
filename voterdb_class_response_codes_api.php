<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiResponseCodes.
 */
/*
 * Name: voterdb_class_resonse_codes_api.php   V4.3 8/25/18
 */

namespace Drupal\voterdb;

class ApiResponseCodes {
  
  private $expectedContactTypes = array(
    'Walk' => array(
      array('text'=>'Left Message/Lit','weight'=>1),
      array('text'=>'Not Home','weight'=>11),
      array('text'=>'Refused','weight'=>2),
      array('text'=>'Inaccessible','weight'=>3),
      array('text'=>'Deceased','weight'=>7),
      array('text'=>'No Such Address','weight'=>4),
      array('text'=>'Hostile','weight'=>6),
      array('text'=>'Moved','weight'=>5),
      //array('text'=>'Canvassed','weight'=>12),
    ),
    'Phone'=>array(
      array('text'=>'Left Message','weight'=>8), 
      array('text'=>'Disconnected','weight'=>9),
      array('text'=>'Wrong Number','weight'=>10),
    ),
    'Postcard'=>array(
      array('text'=>'Mailed','weight'=>5),
    ),
  );
  
  function __construct() {
    $this->result = NULL;
  }
  
  public function getApiContactTypes($countyAuthenticationObj,$database) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$apiURL.'/canvassResponses/contactTypes?inputTypeId=11';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('curl error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    $resultArray = json_decode($result);
    //voterdb_debug_msg('result array', $resultArray);
    $contactTypes = array();
    foreach ($resultArray as $contactTypeObj) {
      $contactTypes[$contactTypeObj->name] = $contactTypeObj->contactTypeId;
    }
    return $contactTypes;
  }
  
  public function getApiResultCodes($countyAuthenticationObj,$database,$contactTypeId) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$apiURL.'/canvassResponses/resultCodes?contactTypeId='.$contactTypeId;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('curl error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    $resultArray = json_decode($result);
    //voterdb_debug_msg('result array', $resultArray);

    $resultCodes = array();
    foreach ($resultArray as $resultCodeObj) {
      $resultCodes[$resultCodeObj->name] = $resultCodeObj->resultCodeId;
    }
    return $resultCodes;
  }
  
  public function getApiKnownResultCodes($countyAuthenticationObj,$database) {
    $contactTypes = $this->getApiContactTypes($countyAuthenticationObj,$database);
    //voterdb_debug_msg('contact types', $contactTypes);
    $knownResultCodes = array();
    foreach ($this->expectedContactTypes as $contactName=>$eResultArray) {
      if(!empty($contactTypes[$contactName])) {
        $contactTypeId = $contactTypes[$contactName];
        $knownResultCodes[$contactName]['code'] = $contactTypeId;
        $resultCodes = $this->getApiResultCodes($countyAuthenticationObj,$database,$contactTypeId);
        foreach ($eResultArray as $expectedResultArray) {
          $expectedResultText = $expectedResultArray['text'];
          if(!empty($resultCodes[$expectedResultText])) {
            $resultCodeId = $resultCodes[$expectedResultText];
            $knownResultCodes[$contactName]['responses'][$expectedResultText] = $resultCodeId;
          }
        }
      } else {
        $knownResultCodes[$contactName]['code'] = NULL;
      }
    }
    return $knownResultCodes;
  }
  
  public function getResultDisplayList() {
    $resultList = array();
    foreach ($this->expectedContactTypes as $eResultArray) {
      foreach ($eResultArray as $expectedResultArray) {
        $resultList[$expectedResultArray['weight']] = $expectedResultArray['text'];
      }
    }
    ksort($resultList);
    return $resultList;
  }
  
  public function getApiExpectedResultCodes() {
    $expectedResultCodes = array();
    foreach ($this->expectedContactTypes as $contactType=>$eResultArray) {
      foreach ($eResultArray as $expectedResultArray) {
        $text = $expectedResultArray['text'];
        $expectedResultCodes[$contactType]['responses'][$text] = $text;
      }
    }
    return $expectedResultCodes;
  }
  
  public function setApiResponseCode($countyAuthenticationObj,$database,$vanid,$response) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$apiURL.'/people/'.$vanid.'/canvassResponses';
    drupal_set_message("url: ".'<pre>'.print_r($url, true).'</pre>','status'); 
    $data = json_encode($response);
    
    $ch = curl_init($url);   
    curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json", "Expect:"));
    curl_setopt($ch,CURLOPT_POST, 1);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($ch);
    
    drupal_set_message("result: ".'<pre>'.print_r($result, true).'</pre>','status');
  }
}
