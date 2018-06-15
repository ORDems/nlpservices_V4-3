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
    //voterdb_debug_msg('folders URL', $foldersURL, __FILE__, __LINE__);

    $ch = curl_init($foldersURL);
      
    if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
      voterdb_debug_msg('setopt HEADER error', curl_error($ch),__FILE__, __LINE__);
    }
    //if(!curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$apiKey.'|'.$database)) {
    //  voterdb_debug_msg('setopt USERPWD error', curl_error($ch),__FILE__, __LINE__);
    //}

    if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
      voterdb_debug_msg('setopt USERPWD error', curl_error($ch),__FILE__, __LINE__);
    }


    $result = curl_exec($ch);

    if($result === FALSE) {
      voterdb_debug_msg('setopt exec error', curl_error($ch),__FILE__, __LINE__);
      return FALSE;
    }
    //$info = curl_getinfo($ch);
    //voterdb_debug_msg('info', $info, __FILE__, __LINE__);
    //voterdb_debug_msg('result', $result, __FILE__, __LINE__);
    //voterdb_debug_msg('curl hdl', $ch, __FILE__, __LINE__);
    curl_close($ch);
    
    $resultObj = json_decode($result);
    //voterdb_debug_msg('result array', $resultObj, __FILE__, __LINE__);
    
    
    foreach ($resultObj->items as $folderInfo) {
      $folderArray[$folderInfo->folderId] = $folderInfo->name; 
    }
    
    
    $this->result = $folderArray;
    

    
  return $this;
  }
}

