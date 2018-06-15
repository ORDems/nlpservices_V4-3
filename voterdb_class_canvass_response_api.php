<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiCanvassRespose.
 */

namespace Drupal\voterdb;

//require_once "voterdb_class_response_codes_api.php";

class ApiCanvassResponse {
  
  const API = '11';

  const PHONE = '1';
  const WALK = '2';
  const POSTCARD = '7';
  
  
  function __construct($canvassContext) {

    $canvassContext->contactTypeId = self::WALK;
    $canvassContext->inputTypeId = self::API;
    $canvassContext->dateCanvassed = NULL;
    $this->canvassContext = $canvassContext;
    //$this->resultCodeId = NULL;
    //$this->responses = NULL;
  }
  
  /**
   * 
   * @param type $countyAuthenticationObj
   * @param type $database
   * @return boolean
   */
  public function setApiResponseCode($countyAuthenticationObj,$database,$vanid,$dateCanvassed,$code) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/people/'.$vanid.'/canvassResponses';
    
    $this->resultCodeId = $code;
    $canvassContext = $this->canvassContext;
    $canvassContext->dateCanvassed = $dateCanvassed;
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    
    //curl_setopt($ch, CURLOPT_URL,"http://www.example.com/tester.phtml");
    curl_setopt($ch, CURLOPT_POST, 1);
    
    $data = json_encode($this);
    voterdb_debug_msg('data', $data, __FILE__, __LINE__);
    //curl_setopt($ch, CURLOPT_POSTFIELDS,"postvar1=value1&postvar2=value2&postvar3=value3");
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    // in real life you should use something like:
    // curl_setopt($ch, CURLOPT_POSTFIELDS, 
    //          http_build_query(array('postvar1' => 'value1')));

    voterdb_debug_msg('ch', $ch, __FILE__, __LINE__);
    $result = curl_exec($ch);
    
    if($result === FALSE) {
      voterdb_debug_msg('curl error', curl_error($ch),__FILE__, __LINE__);
      curl_close($ch);
      return FALSE;
    }
    curl_close($ch);
    return $result;
    $resultArray = json_decode($result);
    //voterdb_debug_msg('result array', $resultArray, __FILE__, __LINE__);
    $contactTypes = array();
    foreach ($resultArray as $contactTypeObj) {
      $contactTypes[$contactTypeObj->name] = $contactTypeObj->contactTypeId;
    }
    return $contactTypes;
  }
  
}
