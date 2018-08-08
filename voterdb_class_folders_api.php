<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiFolders.
 */

namespace Drupal\voterdb;

class ApiFolders {
  
  function __construct($AuthenticationObj) {
    $this->apiKey = $AuthenticationObj->apiKey;
    $this->apiURL = $AuthenticationObj->URL;
    $this->user = $AuthenticationObj->User;
  }
  
 /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * getApiFolders
 * 
 * 
 */
  public function getApiFolders($database,$folderId) {

    $foldersURL = 'https://'.$this->user.':'.$this->apiKey.'|'.$database.'@'.$this->apiURL.'/folders';
    if(!empty($folderId)) {
      $foldersURL .= '/'.$folderId;
    }
    //voterdb_debug_msg('url', $foldersURL);
    $ch = curl_init($foldersURL);  
    if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
      voterdb_debug_msg('setopt HEADER error', curl_error($ch));
    }
    if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
      voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
    }
    $result = curl_exec($ch);
    if($result === FALSE) {
      //voterdb_debug_msg('setopt exec error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    //voterdb_debug_msg('result', $result);

    $resultObj = json_decode($result);
    //voterdb_debug_msg('resultobj', $resultObj);
    if(empty($folderId)) {
      foreach ($resultObj->items as $folderInfo) {
        $folderArray[$folderInfo->folderId] = $folderInfo->name; 
      }
    } else {
      $folderArray[$resultObj->folderId] = $resultObj->name;
    }

  return $folderArray;
  }
  
}
