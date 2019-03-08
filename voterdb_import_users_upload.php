<?php
/*
 * Name: voterdb_import_users_upload.php   V5.0   1/20/19
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_drupal_users.php";

require_once "voterdb_class_magic_word.php";


use Drupal\voterdb\NlpDrupalUser;

use Drupal\voterdb\NlpMagicWord;


define('MAXQUEUELIMIT','100');


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_import_users_upload
 * 
 * Read the provided file and save the Dems.
 * 
 * @param type $arg
 * @param type $context
 * @return FALSE if error.
 */
function voterdb_import_users_upload($arg,&$context) {
  $uri = $arg['uri'];
  
  $drupalUserObj = new NlpDrupalUser();
  $magicWordObj = new NlpMagicWord();
  
  $drupalRoles = user_roles();
  //voterdb_debug_msg('roles', $drupalRoles);
  $roleIds = array_flip($drupalRoles);
  /*
  $historicalRoles = array(
    'neighborhood leader' => NLP_LEADER_ROLE,
    'nlp administrator' => NLP_ADMIN_ROLE,
  );
   * 
   */

  $fh = fopen($uri, "r");
  if ($fh == FALSE) {
    watchdog('voterdb_import_minivan_upload', 'Failed to open file');
    $context['finished'] = TRUE;
    return;
  }
  
  $filesize = filesize($uri);
  $context['finished'] = 0;
  // Position file at the start or where we left off for the previous batch.
  if(empty($context['sandbox']['seek'])) {
    // Read the header record.
    $recordCount = 0;
    //$context['sandbox']['upload-start'] = voterdb_timer('start',0);
    //$context['sandbox']['addTime'] = 0;
  } else {
    // Seek to where we will restart.
    $seek = $context['sandbox']['seek'];
    fseek($fh, $seek);
    $recordCount = $context['sandbox']['recordCount'];
  }
  
  //$addTime = 0;
  $loopCount = 0;
  do {
    $userJson = fgets($fh);
    if(empty($userJson)) {break;}
    $loopCount++;
    $userObj = json_decode($userJson);
    //voterdb_debug_msg('user', $userObj);
    
    $existingUser = $drupalUserObj->getUserByName($userObj->userName);
    if(!empty($existingUser)) {
      $drupalUserObj->deleteUser($existingUser['uid']);
    }
    
    $password = $userObj->password;
    if(empty($password) or $password == 'unknown') {
      $password = 'changeme';
    }
    $rolesExported = (array) $userObj->roles;
    $roles = array();
    foreach ($rolesExported as $exportedRoleName) {
      //if(!empty($historicalRoles[$exportedRoleName])) {
      //  $exportedRoleName = $historicalRoles[$exportedRoleName];
      //}
      $localRid = $roleIds[$exportedRoleName];
      $roles[$localRid] = $exportedRoleName;
    }
    
    //voterdb_debug_msg('roles', $roles);
    $account = array(
      'userName' => $userObj->userName,
      'email' => $userObj->email,
      'firstName' => $userObj->firstName,
      'lastName' => $userObj->lastName,
      'phone' => $userObj->phone,
      'county' => $userObj->county,
      'mcid' => $userObj->mcid,
      'magicWord' => $password,
      'sharedEmail' => $userObj->sharedEmail,
      'roles' => $roles,
    );
    //voterdb_debug_msg('account', $account);
    //$startAddTime = voterdb_timer('start',NULL);
    $newUser = $drupalUserObj->addUser($account);
    //$addTime += voterdb_timer('end',$startAddTime);
    //voterdb_debug_msg('newuser', $newUser);
    
    if($newUser['status'] == 'complete') {
      if(!empty($newUser['mcid'])) {
        $magicWordObj->setMagicWord($newUser['mcid'],$password); 
      }
    }
    
    if($newUser['status'] == 'error') {
      drupal_set_message("Account creation error: ".$newUser['firstName'].' '.$newUser['lastName'],'error');
    }
    
    if($newUser['status'] == 'exists') {
      $editUpdate = array(
        'uid' => $newUser['uid'],
        'roles' => $roles,
      );
      $updateUser = $drupalUserObj->updateUser($editUpdate);
    }
    
    if($loopCount == MAXQUEUELIMIT) {break;}
    
  } while (TRUE);  // Keep looping to read records until the break at EOF.
  
  
  //$done = FALSE;
  $seek = ftell($fh);
  $context['sandbox']['seek'] = $seek;
  $context['finished'] = $seek/$filesize;
  //voterdb_debug_msg('seek: '.$seek.' progress: '.$context['finished'], '');
  $context['sandbox']['recordCount'] += $loopCount;
  //$context['sandbox']['addTime'] += $addTime;
  
  if($loopCount != MAXQUEUELIMIT OR $context['finished'] == 1) {
    $context['finished'] = 1;
    $context['results']['recordCount'] = $context['sandbox']['recordCount'];
    //$upload_time = voterdb_timer('end',$context['sandbox']['upload-start']);
    //$context['results']['upload-time'] = $upload_time;
    $context['results']['uri'] = $uri;
    $context['results']['addTime'] = $context['sandbox']['addTime'];
  }
  
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_import_minivan_finished
 * 
 * The batch operation is finished.  Report the results.
 * 
 * @param type $success
 * @param type $results
 * @param type $operations
 */
function voterdb_import_users_finished($success, $results, $operations) {
  //$matchbackObj = new NlpMatchback();
  $uri = $results['uri'];
  drupal_unlink($uri);
  if ($success) {

    // Report results.
    $recordCount = $results['recordCount'];
    drupal_set_message(t('@count records processed.', 
      array('@count' => $recordCount)));
    //$addTime = round($results['addTime'], 1);
    //$upload_time = round($results['upload-time'], 1);
    //drupal_set_message(t('Upload time: @upload, Add time: @loop.', 
    //  array('@upload' => $upload_time,'@loop'=>$addTime)),'status');
    
    drupal_set_message('The user account file successfully updated.','status');

  }
  else {
    drupal_set_message(t('Opps, an error occurred.'),'error');
  }
  //watchdog('import matchbacks', 'Import of matchbacks has finished');
}
