<?php
/*
 * Name: voterdb_test.php     
 *
 */

require_once "voterdb_class_drupal_users.php";
require_once "voterdb_class_counties.php";
require_once "voterdb_class_turfs.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_magic_word.php";


use Drupal\voterdb\NlpDrupalUser;
use Drupal\voterdb\NlpCounties;
use Drupal\voterdb\NlpTurfs;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpMagicWord;


function voterdb_test() {
  
  
  $queryObj = new EntityFieldQuery();
  $userObj = new NlpDrupalUser();
  
  $countiesObj = new NlpCounties();
  $counties = $countiesObj->getCountyNames();
  //voterdb_debug_msg('counties', $counties, __FILE__, __LINE__);
  
  
  $turfsObj = new NlpTurfs();
  $nlObj = new NlpNls();
  
  $newAccounts = 0;
  
  foreach ($counties as $county) {
    $turfReq = array(
      'county' => $county,
      'pct' => NULL,
    );
    $countyTurfs = $turfsObj->getTurfs($turfReq);
    
    
    
    //voterdb_debug_msg('countyturfs', $countyTurfs);
    
    foreach ($countyTurfs as $turf) {
      
      $msg = $turf['County']." ".$turf['NLfname']." ".$turf['NLlname']." ".$turf['Nickname']." ".$turf['MCID'];
      //voterdb_debug_msg($msg, '');
      
      $mcid = $turf['MCID'];
      $nl = $nlObj->getNlById($mcid);
      //voterdb_debug_msg('nl', $nl, __FILE__, __LINE__);
      
      if(empty($nl)) {
        $output = "empty NL";
        return array('#markup' => $output); 
      }
      $queryObj = new EntityFieldQuery();
      $drupalUser = $userObj->getUserByMcid($queryObj,$mcid);
      
      //voterdb_debug_msg('drupal user', $drupalUser);
      
      
      if(empty($drupalUser)) {
        $newAccounts++;
        $magicWordObj = new NlpMagicWord();
        $magicWord = $magicWordObj->createMagicWord();
        
        
        $account = array(
          'mail' => $nl['email'],
          'firstName' => $nl['nickname'],
          'lastName' => $nl['lastName'],
          'phone' => $nl['phone'],
          'county' => $county,
          'mcid' => $mcid,
          'magicWord' => $magicWord,
        );

        voterdb_debug_msg('account', $account);

        $newUser = $userObj->createUser($account);

        switch ($newUser['status']) {
          case 'error':
            drupal_set_message('Something went wrong with creating an account.  '
                    . 'Please contact NLP tech support','error');
            break;
          case 'exists':
            drupal_set_message("The NL's name is already in use.  "
                    . 'Please contact NLP tech support','error');
            break;
          case 'complete':
            drupal_set_message('An account was created for this NL.'
                    . '<br>Username: '.$newUser['userName']
                    . '<br>Password: '.$magicWord,'status');

            if(empty($nl['email'])) {
              drupal_set_message("The NL doesn't have an email so you will have to help with the login.",'warning'); 
            }
            
            $magicWordObj->setMagicWord($mcid,$magicWord);
            
            
            break;
        }
        
        
        
        
        
        //$msg2 = "status: ".$newUser['status']." ".$newUser['email']." ".$newUser['sharedEmail'];
        //voterdb_debug_msg($msg2, '');
        
        if($newAccounts>9) {
          $output = "test complete";
          return array('#markup' => $output); 
        }
  
      } else {
        voterdb_debug_msg('User exists', '');
      }
      
    }
  }
  
  
       
  $output = "test complete";
  return array('#markup' => $output);   

}