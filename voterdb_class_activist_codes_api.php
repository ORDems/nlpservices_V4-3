<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiActivistCodes.
 */
/*
 * Name:  voterdb_class_activist_codes_api.php               V4.1 5/28/18
 */
namespace Drupal\voterdb;

class ApiActivistCodes {

  public function getApiActivistCodes($countyAuthenticationObj,$database) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $url = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/activistCodes?$top=200';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('curl error', curl_error($ch),__FILE__, __LINE__);
      return FALSE;
    }
    curl_close($ch);
    $activistObj = json_decode($result);
    $activistArray = $activistObj->items;
    $activistCodes = array();
    foreach ($activistArray as $activistCodeObj) {
      $activistCode['activistCodeId'] = $activistCodeObj->activistCodeId;
      $activistCode['type'] = $activistCodeObj->type;
      $activistCode['description'] = $activistCodeObj->description;
      $activistCode['name'] = $activistCodeObj->name;
      $activistCodes[$activistCodeObj->activistCodeId] = $activistCode;
    }
    return $activistCodes;
  }
  
  public function getActivistCodeList($activistCodes) {
    $activistCodeList[1] = 'Select an Activist Code';
    foreach ($activistCodes as $activistCodeId => $activistCode) {
      $activistCodeList[$activistCodeId] = 'name:"'.$activistCode['name'].
              '", type="'.$activistCode['type'].'"';
    }
    return $activistCodeList;
  }
          
}
