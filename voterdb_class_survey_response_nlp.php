<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpSurveyResponse.
 */
/*
 * Name: voterdb_class_survey_response_nlp.php   V4.2 6/20/18
 */
namespace Drupal\voterdb;

class NlpSurveyResponse {
  
  const RESPONSESTBL = 'survey_responses';
  
  private $responseList = array(
    'rid'=>'Rid',
    'responseName'=>'ResponseName',
    'qid'=>'Qid',
    'questionName'=>'QuestionName'
  );
  
  public function deleteResponses($qid) {
    db_set_active('nlp_voterdb');
    db_delete(self::RESPONSESTBL)
    ->condition('Qid', $qid)
    ->execute();
    db_set_active('default');
  }
  
  public function insertResponse($responseFields) {
    $fields = array();
    foreach ($responseFields as $nlpKey => $value) {
      $fields[$this->responseList[$nlpKey]] = $value;
    }
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::RESPONSESTBL)
        ->fields($fields)
        ->execute();
      }
    catch (Exception $e) {
      db_set_active('default');
      drupal_set_message('Opps: '.$e->getMessage(),'error');
      return;
    }
    db_set_active('default');
  }
  
  public function fetchResponses($qid) {
    try{
      db_set_active('nlp_voterdb');
      $select = "SELECT * FROM {".self::RESPONSESTBL."} WHERE Qid=:qid";
      $args = array(':qid'=>$qid);
      $result = db_query($select,$args);    
    }
    catch (Exception $e) {
      db_set_active('default');
      drupal_set_message('Opps: '.$e->getMessage(),'error');
      return NULL;
    }
    db_set_active('default');
    if(empty($result)) {return NULL;}
    $responseFlip = array_flip($this->responseList);
    $responses = array();
    do {
      $response = $result->fetchAssoc();
      //voterdb_debug_msg('response', $response);
      
      if(empty($response)) {break;}
      foreach ($response as $dbKey => $value) {
        $nlpResponse[$responseFlip[$dbKey]] = $value;
      }
      $responses[] = $nlpResponse;
    } while (TRUE);
    
    return $responses;
  }

  public function getSurveyResponseList($qid) {
    $responseList[0] = 'Select Response';
    $responsesArray = $this->fetchResponses($qid);
    foreach ($responsesArray as $response) {
      $responseList[$response['rid']] = $response['responseName'];
    }
    return $responseList;
  }
  
}
