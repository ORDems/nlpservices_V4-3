<?php

require_once "voterdb_debug.php";
require_once "voterdb_class_api_authentication.php";


use Drupal\voterdb\ApiAuthentication;



function voterdb_test() {
  $output = "test started";
  $scheme =  'https';

  $apiURL = "api.securevan.com/v4";
  $apiKey = '35abc3d4-bf8d-d9bb-5877-efffe189c638';

  $user = 'demo.spacker.api';
  
  $database = 0;
  

  $acResourceName = '/activistCodes';

  $resource = $acResourceName;
  
  $password = $apiKey.'|'.$database;
  //$getURL = $scheme .'://'.$user.':'.$password.'@'.$apiURL . $resource ;
  $getURL = $scheme .'://'.$apiURL . $resource ;
  drupal_set_message("getURL: ".'<pre>'.print_r($getURL, true).'</pre>','status');

  /*
  $aResponse = drupal_http_request($getURL);
  drupal_set_message("Response: ".'<pre>'.print_r($aResponse, true).'</pre>','status');
  $sweet = $aResponse->code;
  if ($sweet == 200) {
    $arr = json_decode($aResponse->data, true);
    //drupal_set_message("response array: ".'<pre>'.print_r($arr, true).'</pre>','status');
  }
   * 
   */

  $ch = curl_init($getURL);

  if(!curl_setopt($ch, CURLOPT_HEADER, "Content-type: application/json")) {
    voterdb_debug_msg('setopt HEADER error', curl_error($ch));
  }
  if(!curl_setopt($ch, CURLOPT_USERPWD, $user.':'.$password)) {
    voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
  }

  if(!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
    voterdb_debug_msg('setopt USERPWD error', curl_error($ch));
  }


  $result = curl_exec($ch);

  if($result === FALSE) {
    voterdb_debug_msg('setopt exec error', curl_error($ch));
  }
  $info = curl_getinfo($ch);
  voterdb_debug_msg('info', $info);
  voterdb_debug_msg('result', $result);
  voterdb_debug_msg('curl hdl', $ch);
  curl_close($ch);
  
  $output .= "<br>test complete";
  return array('#markup' => $output); 


}
