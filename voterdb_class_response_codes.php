<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpResponseCodes.
 */
/*
 * Name: voterdb_class_response_codes.php   V4.2 6/20/18
 *
 */

namespace Drupal\voterdb;

class NlpResponseCodes {
  
  const RESPONSECODESTBL = "response_codes";
  
  private $typeCodeList = array(
    'walk'=>'Walk',
    'phone'=>'Phone',
    'postcard'=>'Postcard'
  );
  
  
  
  private $responseCodesList = array(
    'contactType'=>'ContactType',
    'name'=>'Name',
    'code'=>'Code'
  );
  
  private function deleteResponseCodes() {
    db_set_active('nlp_voterdb');
    db_truncate(self::RESPONSECODESTBL)->execute();
    db_set_active('default');
  }
  
  private function insertResponseCode($type,$name,$code) {
    db_set_active('nlp_voterdb');
    db_insert(self::RESPONSECODESTBL)
      ->fields(array(
        'ContactType' => $type,
        'Name' => $name,
        'Code' => $code,
      ))
      ->execute();
    db_set_active('default'); 
  }
  
  public function getNlpResponseCodes() {
    $responseCodes = array();
    db_set_active('nlp_voterdb');
    $selectContacts = "SELECT * FROM {".self::RESPONSECODESTBL."} WHERE Name IS NULL ";
      $resultContactTypes = db_query($selectContacts);
    do {
      $typeCodeRow = $resultContactTypes->fetchAssoc();
      if(!$typeCodeRow) {break;}
      $contactTypeCode[$typeCodeRow['ContactType']] = $typeCodeRow['Code'];
    } while (TRUE);
    $select = "SELECT * FROM {".self::RESPONSECODESTBL."} WHERE  Name IS NOT NULL ";
    $result = db_query($select);
    db_set_active('default');
    do {
      $responseRow = $result->fetchAssoc();
      if(!$responseRow) {break;}
      $responseCode = array(
          'Code' => $responseRow['Code'],
          'ContactType' => $responseRow['ContactType'],
          'ContactTypeCode' => $contactTypeCode[$responseRow['ContactType']]
      );
      $responseCodes[$responseRow['Name']][] = $responseCode;
    } while (TRUE);
    return $responseCodes;
  }

  public function setNlpResponseCodes($apiResponseCodes) {
    $this->deleteResponseCodes();
    foreach ($apiResponseCodes as $contactType=>$contactArray) {
      $this->insertResponseCode($contactType,NULL,$contactArray['code']);
      $responsesArray = $contactArray['responses'];
      foreach ($responsesArray as $responseName => $responseCode) {
        $this->insertResponseCode($contactType,$responseName,$responseCode);
      }
    }  
  }
  
  public function getNlpResponseCodesList() {
    $responseCodes = $this->getNlpResponseCodes();
    $responseList[0] = 'Select Response';
    foreach ($responseCodes as $responseCodeName => $responseCodeArray) {
      $responseList[$responseCodeArray[0]['Code']] = $responseCodeName;
    }
    return $responseList;
  }
  
  public function getNlpContactType() {
    $responseCodes = $this->getNlpResponseCodes();
    foreach ($responseCodes as $responseCodeArray) {
      $responseCodeType[$responseCodeArray[0]['Code']] = $responseCodeArray[0]['ContactType'];
    }
    return $responseCodeType;
  }

}
