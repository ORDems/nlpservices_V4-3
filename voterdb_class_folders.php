<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiFolders.
 */

namespace Drupal\voterdb;

//require_once "voterdb_constants_van_api_tbl.php";

class ApiFolders {
  
  function __construct() {
    $this->result = NULL;
  }
  
  public function getApiFolders($countyAuthenticationObj,$database) {
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;
    $foldersURL = 'https://'.$apiURL.'/folders';
    $ch = curl_init($foldersURL); 
    curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json");
    curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('setopt exec error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    $resultObj = json_decode($result);
    foreach ($resultObj->items as $folderInfo) {
      $folderArray[$folderInfo->folderId] = $folderInfo->name; 
    }
    $this->result = $folderArray;
  return $this;
  }
}
