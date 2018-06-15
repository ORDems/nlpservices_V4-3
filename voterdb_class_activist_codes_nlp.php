<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpActivistCodes.
 */
/*
 * Name:  voterdb_class_activist_codes_nlp.php               V4.1 5/28/18
 */
namespace Drupal\voterdb;

class NlpActivistCodes {
  
  const ACTIVISTCODETBL = 'activist_codes';
  
  private $activistCodeMap = array (
    'FunctionName' => 'functionName',
      'Name' => 'name',
      'Type' => 'type',
      'Description' => 'description',
      'ActivistCodeId' => 'activistCodeId',
  );
  
  private function deleteCode($functionName) {
    db_set_active('nlp_voterdb');
    try {
      db_delete(self::ACTIVISTCODETBL)
      ->condition('FunctionName', $functionName)
      ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return;
    }
    db_set_active('default');
  }
  
  public function setActivistCode($activistCode) { 
    //voterdb_debug_msg('$activistCode', $activistCode , __FILE__, __LINE__);
    $this->deleteCode($activistCode['functionName']);
    foreach ($this->activistCodeMap as $dbKey => $nlpKey) {
      $fieldArray[$dbKey] = $activistCode[$nlpKey];
    }
    //voterdb_debug_msg('fields', $fieldArray , __FILE__, __LINE__);
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::ACTIVISTCODETBL)
        ->fields($fieldArray)
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return;
    }
    db_set_active('default');
  }
  
  public function getActivistCode($functionName) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::ACTIVISTCODETBL, 'a');
      $query->fields('a');
      $query->condition('FunctionName',$functionName);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return NULL;
    }
    $activistCodeRecord = $result->fetchAssoc();
    db_set_active('default');
    if(empty($activistCodeRecord)) {
      return NULL;
    }
    $activistCode = array();
    foreach ($this->activistCodeMap as $dbKey => $nlpKey) {
      $activistCode[$nlpKey] = $activistCodeRecord[$dbKey];
    }
    return $activistCode;
  }     
  
  public function getNlpActivistCodeDisplay($activistCode) {
    $activistCodeDisplay = 'name:"'.$activistCode['name'].'", type="'.
            $activistCode['type'].'", Description:"'.$activistCode['description'].'"';
    return $activistCodeDisplay;
  }
  
  public function deleteActivistCode($functionName) {
    $this->deleteCode($functionName);
  }

}
