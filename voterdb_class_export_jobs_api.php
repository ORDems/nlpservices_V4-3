<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiExportJobs.
 */
/*
 * Name:  voterdb_class_export_jobs_api.php               V4.3 7/22/18
 */
namespace Drupal\voterdb;

class ApiExportJobs {
  
  function __construct($AuthenticationObj) {
    $this->apiKey = $AuthenticationObj->apiKey;
    $this->apiURL = $AuthenticationObj->URL;
    $this->user = $AuthenticationObj->User;
  }
  
  public function getExportJobTypes($database) {

    $listsURL = 'https://'.$this->user.':'.$this->apiKey.'|'.$database.
            '@'.$this->apiURL.'/exportJobTypes';

    voterdb_debug_msg('url', $listsURL);
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



  return $resultObj;
  }
  

  
}
