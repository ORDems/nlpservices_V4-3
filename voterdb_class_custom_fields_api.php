<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiCustomFields.
 */

namespace Drupal\voterdb;

class ApiCustomFields {
  
  function __construct($AuthenticationObj) {
    $this->apiKey = $AuthenticationObj->apiKey;
    $this->apiURL = $AuthenticationObj->URL;
    $this->user = $AuthenticationObj->User;
  }

  public function getCustomFields($database) {
    $url = 'https://'.$this->apiURL.'/customFields?customFieldsGroupType=Contacts';
    voterdb_debug_msg('url', $url);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_USERPWD, $this->user.':'.$this->apiKey.'|'.$database);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('curl error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    $resultArray = json_decode($result);
    voterdb_debug_msg('result array', $resultArray);
  }
  
}
