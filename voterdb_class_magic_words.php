<?php
/*
 * Name: voterdb_class_magic_words.php   V4.1 4/19/18
 *
 */
namespace Drupal\voterdb;
require_once "voterdb_debug.php";
class NlpMagicWords {
  
  function __construct() {
    $this->result = NULL;
  }
  

  /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  * getMagicWords
  * 
  * 
  * @return string - array of county names.
  */
  function getMagicWords($county) {
    $passwordArray = variable_get('voterdb_passwords',NULL);
    if(!empty($passwordArray[$county]['password'])) {
      $passwords['password'] = $passwordArray[$county]['password'];
      $passwords['passwordAlt'] = $passwordArray[$county]['passwordAlt'];
    } else {
      if(!empty($passwordArray['default'])) {
        $passwords['password'] = $passwordArray['default']['password'];
        $passwords['passwordAlt'] = $passwordArray['default']['passwordAlt'];
      }  else {
        $passwords['password'] = $passwords['passwordAlt'] = '';
      }
    }
    return $passwords;
  }
  
  function isSetMagicWords($county) {
    $passwordArray = variable_get('voterdb_passwords',NULL);
    if(!empty($passwordArray[$county])) {
      $passwords['password'] = $passwordArray[$county]['password'];
      $passwords['passwordAlt'] = $passwordArray[$county]['passwordAlt'];
    } else {
      $passwords['password'] = $passwords['passwordAlt'] = '';
    }
    return $passwords;
  }
  
  function setMagicWords($county,$passwords) {
    $passwordArray = variable_get('voterdb_passwords',NULL);
    if(empty($passwords['password'])) {
      unset($passwordArray[$county]['password']);
      unset($passwordArray[$county]['passwordAlt']);
    } else {
      $passwordArray[$county]['password'] = $passwords['password'];
      $passwordArray[$county]['passwordAlt'] = $passwords['passwordAlt'];
    }
    variable_set('voterdb_passwords',$passwordArray);
    return;
  }
  
  function resetMagicWords() {
    variable_set('voterdb_passwords',NULL);
  }
}
