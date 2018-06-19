<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiResponseCodes.
 */

namespace Drupal\voterdb;

//require_once "voterdb_constants_van_api_tbl.php";
//require_once "voterdb_class_response_codes_api.php";

class ApiResponseCodes {
  

  private $expectedContactTypes = array(
    'Walk' => array(
      array('text'=>'Not a Dem','weight'=>12),
      array('text'=>'Left Message/Lit','weight'=>1),
      array('text'=>'Refused','weight'=>2),
      array('text'=>'Inaccessible','weight'=>3),
      array('text'=>'Deceased','weight'=>7),
      array('text'=>'No Such Address','weight'=>4),
      array('text'=>'Hostile','weight'=>6),
      array('text'=>'Moved','weight'=>5),
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
  
  /**
   * 
   * @param type $countyAuthenticationObj
   * @param type $database
   * @return boolean
   */
  public function getApiContactTypes($countyAuthenticationObj,$database) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/canvassResponses/contactTypes?inputTypeId=11';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
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
  
    /**
   * 
   * @param type $countyAuthenticationObj
   * @param type $database
   * @return boolean
   */
  public function getApiResultCodes($countyAuthenticationObj,$database,$contactTypeId) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/canvassResponses/resultCodes?contactTypeId='.$contactTypeId;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
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
        //voterdb_debug_msg('response codes', $resultCodes);

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
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/people/'.$vanid.'/canvassResponses';
    drupal_set_message("url: ".'<pre>'.print_r($url, true).'</pre>','status');

    $data = http_build_query($response);


    $options = array(
      'method' => 'POST',
      'data' => $data,
      'headers' => array('Content-Type' => 'application/json'),
    );
    drupal_set_message("options: ".'<pre>'.print_r($options, true).'</pre>','status');
    //$result = drupal_http_request($url, $options);
    //drupal_set_message("Response: ".'<pre>'.print_r($result, true).'</pre>','status');
    
}
  
  
}
