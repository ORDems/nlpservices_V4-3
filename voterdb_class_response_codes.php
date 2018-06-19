<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpResponseCodes.
 */

namespace Drupal\voterdb;

require_once "voterdb_constants_rr_tbl.php";
const DB_RESPONSE_CODES_TBL = "response_codes";
//const DB_QUESTIONS_TBL = "questions";

class NlpResponseCodes {
  
  
  private function deleteResponseCodes() {
    db_set_active('nlp_voterdb');
    db_truncate(DB_RESPONSE_CODES_TBL)->execute();
    db_set_active('default');
  }
  
  
  private function insertResponseCode($type,$name,$code) {
    db_set_active('nlp_voterdb');
    db_insert(DB_RESPONSE_CODES_TBL)
      ->fields(array(
        'ContactType' => $type,
        'Name' => $name,
        'Code' => $code,
      ))
      ->execute();
    db_set_active('default'); 
  }
  

 /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * getNlpResponseCodes
 * 
 * 
 */
  public function getNlpResponseCodes() {
    $responseCodes = array();
    db_set_active('nlp_voterdb');
    $selectContacts = "SELECT * FROM {".DB_RESPONSE_CODES_TBL."} WHERE Name IS NULL ";
      $resultContactTypes = db_query($selectContacts);
      
    do {
      $typeCodeRow = $resultContactTypes->fetchAssoc();
      if(!$typeCodeRow) {break;}
      $contactTypeCode[$typeCodeRow['ContactType']] = $typeCodeRow['Code'];
    } while (TRUE);
 
    $select = "SELECT * FROM {".DB_RESPONSE_CODES_TBL."} WHERE  Name IS NOT NULL ";
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

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * setNlpResponseCodes
 * 
 * 
 */
  public function setNlpResponseCodes($apiResponseCodes) {
    $this->deleteResponseCodes();
    foreach ($apiResponseCodes as $contactType=>$contactArray) {
      $this->insertResponseCode($contactType,NULL,$contactArray['code']);
      //voterdb_debug_msg('contact array', $contactArray);
      $responsesArray = $contactArray['responses'];
      foreach ($responsesArray as $responseName => $responseCode) {
        $this->insertResponseCode($contactType,$responseName,$responseCode);
      }
    }  
  }
  
  /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  * getNlpResponseCodesList
  * 
  * 
  */
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
