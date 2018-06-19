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
  
 /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * getApiFolders
 * 
 * 
 */
  public function getApiFolders($countyAuthenticationObj,$database) {
    
    $apiKey = $countyAuthenticationObj->apiKey;
    $apiURL = $countyAuthenticationObj->URL;
    $user = $countyAuthenticationObj->User;

    $foldersURL = 'https://'.$user.':'.$apiKey.'|'.$database.'@'.$apiURL.'/folders';
    //voterdb_debug_msg('folders URL', $foldersURL);

    $ch = curl_init($foldersURL);
      
    if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
      voterdb_debug_msg('setopt HEADER error', curl_error($ch));
    }
    //if(!curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database)) {
    //  voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
    //}

    if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
      voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
    }


    $result = curl_exec($ch);

    if($result === FALSE) {
      voterdb_debug_msg('setopt exec error', curl_error($ch));
      return FALSE;
    }
    //$info = curl_getinfo($ch);
    //voterdb_debug_msg('info', $info);
    //voterdb_debug_msg('result', $result);
    //voterdb_debug_msg('curl hdl', $ch);
    curl_close($ch);
    
    $resultObj = json_decode($result);
    //voterdb_debug_msg('result array', $resultObj);
    
    
    foreach ($resultObj->items as $folderInfo) {
      $folderArray[$folderInfo->folderId] = $folderInfo->name; 
    }
    
    
    $this->result = $folderArray;
    

    
  return $this;
  }
}

