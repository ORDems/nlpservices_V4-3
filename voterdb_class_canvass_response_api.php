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
    $url = 'https://'.$apiURL.'/people/'.$vanid.'/canvassResponses';
    $this->resultCodeId = $code;
    $canvassContext = $this->canvassContext;
    $canvassContext->dateCanvassed = $dateCanvassed;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    $data = json_encode($this);
    //voterdb_debug_msg('data', $data);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
    //voterdb_debug_msg('ch', $ch);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('curl error', curl_error($ch));
      curl_close($ch);
      return FALSE;
    }
    curl_close($ch);
    return $result;
    $resultArray = json_decode($result);
    //voterdb_debug_msg('result array', $resultArray);
    $contactTypes = array();
    foreach ($resultArray as $contactTypeObj) {
      $contactTypes[$contactTypeObj->name] = $contactTypeObj->contactTypeId;
    }
    return $contactTypes;
  }
  
}
