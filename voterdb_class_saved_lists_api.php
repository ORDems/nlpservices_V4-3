<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiSavedLists.
 */

namespace Drupal\voterdb;

class ApiSavedLists {
  
  function __construct($AuthenticationObj) {
    $this->apiKey = $AuthenticationObj->apiKey;
    $this->apiURL = $AuthenticationObj->URL;
    $this->user = $AuthenticationObj->User;
  }
  
  public function getSavedLists($database,$folderId) {

    $listsURL = 'https://'.$this->user.':'.$this->apiKey.'|'.$database.
            '@'.$this->apiURL.'/savedLists?folderId='.$folderId;

    //voterdb_debug_msg('url', $listsURL);
    $ch = curl_init($listsURL);  
    if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
      voterdb_debug_msg('setopt HEADER error', curl_error($ch));
    }
    if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
      voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
    }
    $result = curl_exec($ch);
    if($result === FALSE) {
      voterdb_debug_msg('setopt exec error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    //voterdb_debug_msg('result', $result);

    $resultObj = json_decode($result);
    //voterdb_debug_msg('resultobj', $resultObj);
    if($resultObj->count == 0) {
      return NULL;
    }


  return $resultObj;
  }
  
  public function getSavedList($database,$folderId,$savedListId) {

    $foldersURL = 'https://'.$this->user.':'.$this->apiKey.'|'.$database.
            '@'.$this->apiURL.'/savedLists/'.$savedListId;

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
      voterdb_debug_msg('setopt exec error', curl_error($ch));
      return FALSE;
    }
    curl_close($ch);
    //voterdb_debug_msg('result', $result);

    $resultObj = json_decode($result);
    //voterdb_debug_msg('resultobj', $resultObj);

  return $resultObj;
  }
  
}
