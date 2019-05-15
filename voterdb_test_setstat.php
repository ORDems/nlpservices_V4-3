<?php
/*
 * Name: voterdb_test.php     
 *
 */
require_once "voterdb_debug.php";
require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_voters.php";


use Drupal\voterdb\NlpVoters;



function voterdb_test() {
  
  $output = "test started";
  
  $voterObj = new NlpVoters();
  //voterdb_debug_msg('voterobj', $voterObj);
  
  $voterIds = $voterObj->getAllNlpVoterIds();
  //voterdb_debug_msg('allids', $voterIds);
  
  foreach ($voterIds as $vanid) {
    $voterStatus = $voterObj->getVoterStatus($vanid);
    //voterdb_debug_msg('status', $voterStatus);
    if(empty($voterStatus['vanid'])) {
      $voterObj->setVoterStatus($vanid, $voterStatus);
      //break;
    } else {
      voterdb_debug_msg('status', $voterStatus);
    }
    
  }
      
  $output .= "<br>test complete";
  return array('#markup' => $output);   

}