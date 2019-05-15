<?php
/*
 * Name: voterdb_test.php     
 *
 */

require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";


use Drupal\voterdb\NlpDrupalUser;
use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpNls;


function voterdb_test() {
  
  
  $queryObj = new EntityFieldQuery();
  $userObj = new NlpDrupalUser();
  
  $countiesObj = new NlpCounties();
  $counties = $countiesObj->getCountyNames();
  voterdb_debug_msg('counties', $counties, __FILE__, __LINE__);
  
  
  $turfsObj = new NlpTurfs();
  $nlObj = new NlpNls();
  
  foreach ($counties as $county) {
    $turfReq = array(
      'county' => $county,
      'pct' => NULL,
    );
    $countyTurfs = $turfsObj->getTurfs($turfReq);
    voterdb_debug_msg('countyturfs', $countyTurfs);
    
    foreach ($countyTurfs as $turf) {
      $mcid = $turf['mcid'];
      $nl = $nlObj->getNlById($mcid);
      voterdb_debug_msg('nl', $nl, __FILE__, __LINE__);
      
      $drupalUser = $userObj->getUserByMcid($queryObj,$mcid);
      
      voterdb_debug_msg('drupal user', $drupalUser);
      
      
      if(empty($drupalUser)) {
        $account = array(
          'mail' => $nl['email'],
          'firstName' => $nl['nickname'],
          'lastName' => $nl['lastName'],
          'phone' => $nl['phone'],
          'county' => $county,
          'mcid' => $mcid,
        );

        voterdb_debug_msg('account', $account);

        $newUser = $userObj->createUser($account);
        voterdb_debug_msg('newuser', $newUser);
      }
      
    }
  }
  
  
  
  
/*
  
  $userName = 'sjpacker';
  $accountObj = $userObj->getUserByName($userName);
  voterdb_debug_msg('get by username accountObj', $accountObj, __FILE__, __LINE__);
  
  
  $userName2 = 'Steve.Packer';
  $user2 = $userObj->getUserByName($userName2);
  if(!empty($user2)) {
    $uid2 = $user2['uid'];
    $userObj->deleteUser($uid2);
  }
  
  $userName3 = 'Karen.Packer';
  $user3 = $userObj->getUserByName($userName3);
  if(!empty($user3)) {
    $uid3 = $user3['uid'];
    $userObj->deleteUser($uid3);
  }
  
  
  
  
  
  $account = array(
    'mail' => 'steve.j.packer@gmail.com',
    'firstName' => 'Steve',
    'lastName' => 'Packer',
    'phone' => '5038303666',
    'county' => 'Washington',
    'mcid' => '10001692',
  );
  
  
  $newUser = $userObj->createUser($account);
  //voterdb_debug_msg('newuser', $newUser, __FILE__, __LINE__);
  if(!empty($newUser)) {
    $uid = $newUser['uid'];
    $accountUpdateObj = $userObj->getUserObj($uid);
    //$accountUpdateObj = new stdClass();
    $editUpdate = array(
      'uid' => $uid,
      'mail' => 'karen.packer@gmail.com',
      'phone' => '5037062675',
    );
    $updatedUser = $userObj->updateUser($accountUpdateObj,$editUpdate);
    //voterdb_debug_msg('updateduser', $updatedUser, __FILE__, __LINE__);
  }
  
  
  
  $userName4 = 'Test.Packer';
  $user4 = $userObj->getUserByName($userName4);
  if(!empty($user4)) {
    $uid4 = $user4['uid'];
    $userObj->deleteUser($uid4);
  }
  
  $account5 = array(
    'mail' => 'karen.packer@gmail.com',
    'firstName' => 'Karen',
    'lastName' => 'Packer',
    'phone' => '5037062675',
    'county' => 'Washington',
    'mcid' => '10001693',
  );
  
  
  $newUser5 = $userObj->createUser($account5);
  voterdb_debug_msg('newuser', $newUser5, __FILE__, __LINE__);
  if(!empty($newUser5)) {
    $uid5 = $newUser['uid'];
    voterdb_debug_msg('newuser5', $newUser5, __FILE__, __LINE__);
  }
  
  
  
  $account['mail'] = NULL;
  $account['firstName'] = 'Test';
  $newUser2 = $userObj->createUser($account);
  voterdb_debug_msg('newuser2', $newUser2, __FILE__, __LINE__);
  if(!empty($newUser2)) {
    $uid2 = $newUser2['uid'];
    $userObj->deleteUser($uid2);
  }
  
  
  
  
  
  $user6 = $userObj->getCurrentUser();
  voterdb_debug_msg('currentuser', $user6, __FILE__, __LINE__);
  
  $users = $userObj->getUsers($county);
  voterdb_debug_msg('countyusers', $users, __FILE__, __LINE__);
  
  if(!empty($uid)) {
    $userObj->deleteUser($uid);
  }
  
  if(!empty($uid5)) {
    $userObj->deleteUser($uid5);
  }
 * 
 */
      
  $output = "test complete";
  return array('#markup' => $output);   

}