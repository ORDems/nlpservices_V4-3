<?php
/**
 * @file
 * Contains Drupal\voterdb\ApiAuthentication.
 */
/*
 * Name:  voterdb_class_api_authentication.php               V4.1 5/28/18
 */
namespace Drupal\voterdb;

class ApiAuthentication {
  
  const VANAPITBL = 'van_api';
   
  function __construct() {
    $this->Committee = NULL;
    $this->URL = NULL;
    $this->User = NULL;
    $this->apiKey = NULL;
  }
  
  public function getApiAuthentication($committee) {
    db_set_active('nlp_voterdb');
    try {
      $select = "SELECT * FROM {".self::VANAPITBL."} WHERE Committee = :cmty ";
      $args = array(':cmty' => $committee,);
      $result = db_query($select,$args);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return NULL;
    }
    $countyAuthentication = $result->fetchAssoc();
    db_set_active('default');
    if(empty($countyAuthentication)) {return NULL;}
    $this->Committee = $countyAuthentication['Committee'];
    $this->URL = $countyAuthentication['URL'];
    $this->User = $countyAuthentication['User'];
    $this->apiKey = $countyAuthentication['apiKey']; 
  return $this;
  }
  
  public function setApiAuthentication() {
    db_set_active('nlp_voterdb');
    try {
      db_delete(self::VANAPITBL)
      ->condition('Committee', $this->Committee)
      ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return;
    }
    try {
      db_insert(self::VANAPITBL)
        ->fields(array(
          'Committee' => $this->Committee,
          'URL' => $this->URL,
          'User' => $this->User,
          'apiKey' => $this->apiKey,))
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return;
    }
    db_set_active('default');
  }
  
}
