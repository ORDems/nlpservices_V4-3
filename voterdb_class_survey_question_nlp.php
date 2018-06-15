<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpSurveyQuestion.
 */
/*
 * Name: voterdb_class_survey_question_nlp.php   V4.0 5/28/18
 *
 */
namespace Drupal\voterdb;

require_once "voterdb_debug.php";

class NlpSurveyQuestion {
  
  const QUESTIONSTBL = 'survey_questions';
  const RESPONSESTBL = 'survey_responses';
  
  function __construct() {
    $this->result = NULL;
  }
  
  private function deleteQuestion($qid) {
    db_set_active('nlp_voterdb');
    db_delete(self::QUESTIONSTBL)
    ->condition('Qid', $qid)
    ->execute();
    db_set_active('default');
  }
  
  private function deleteResponses($qid) {
    db_set_active('nlp_voterdb');
    db_delete(self::RESPONSESTBL)
    ->condition('Qid', $qid)
    ->execute();
    db_set_active('default');
  }

  private function insertQuestion($surveyFields) {
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::QUESTIONSTBL)
        ->fields($surveyFields)
        ->execute();
      }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->getMessage();
      drupal_set_message('Opps: '.$error,'error');
      return;
    }
    db_set_active('default');
  }
  
  private function insertResponse($responseFields) {
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::RESPONSESTBL)
        ->fields($responseFields)
        ->execute();
      }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->getMessage();
      drupal_set_message('Opps: '.$error,'error');
      return;
    }
    db_set_active('default');
  }
  
  private function fetchQuestion() {
    try{
      db_set_active('nlp_voterdb');
      $selectContacts = "SELECT * FROM {".self::QUESTIONSTBL."} WHERE QuestionType<>:qtype";
      $args = array(':qtype'=>'Candidate');
      $result = db_query($selectContacts,$args);    
    }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->getMessage();
      drupal_set_message('Opps: '.$error,'error');
      return NULL;
    }
    db_set_active('default');
    if(!$result) {return NULL;}
    $question = $result->fetchAssoc();
    return $question;
  }
  
  private function fetchResponses($qid) {
    try{
      db_set_active('nlp_voterdb');
      $select = "SELECT * FROM {".self::RESPONSESTBL."} WHERE Qid=:qid";
      $args = array(':qid'=>$qid);
      $result = db_query($select,$args);    
    }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->getMessage();
      drupal_set_message('Opps: '.$error,'error');
      return NULL;
    }
    db_set_active('default');
    if(!$result) {return NULL;}
    do {
      $response = $result->fetchAssoc();
      if(!$response) {break;}
      $responses[] = $response;
    } while (TRUE);
    return $responses;
  }

  public function getSurveyQuestion() {
    $question = $this->fetchQuestion();
    if(empty($question)) {return NULL;} 
    $questionArray['questionName'] = $question['QuestionName'];
    $questionArray['qid'] = $question['Qid'];
    $questionArray['cycle'] = $question['Cycle'];
    $questionArray['questionType'] = $question['QuestionType'];
    $questionArray['scriptQuestion'] = $question['ScriptQuestion'];
    $responsesArray = $this->fetchResponses($question['Qid']);
    foreach ($responsesArray as $response) {
      $questionArray['responses'][$response['Rid']] = $response['ResponseName'];
    }
    return $questionArray;
  }
  
  public function getSurveyResponseList($qid) {
    $responseList[0] = 'Select Response';
    $responsesArray = $this->fetchResponses($qid);
    foreach ($responsesArray as $response) {
      $responseList[$response['Rid']] = $response['ResponseName'];
    }
    return $responseList;
  }
  
  public function setSurveyQuestion($surveyQuestion,$surveyQuestionId) {
    $this->deleteQuestion($surveyQuestionId);
    $this->deleteResponses($surveyQuestionId);
    $surveyFields = array(  
        'Qid'=>$surveyQuestionId,
        'QuestionName'=>$surveyQuestion['name'],
        'QuestionType'=>$surveyQuestion['type'],
        'Cycle'=>$surveyQuestion['cycle'],
        'ScriptQuestion'=>$surveyQuestion['scriptQuestion'],
        );
    $this->insertQuestion($surveyFields);
    $responses = $surveyQuestion['responses'];
    foreach ($responses as $surveyResponseId=>$surveyResponseName) {
      $responseFields = array(
          'Qid'=>$surveyQuestionId,
          'Rid'=>$surveyResponseId,
          'ResponseName'=>$surveyResponseName,
          'QuestionName'=>$surveyQuestion['name'],
          );
      $this->insertResponse($responseFields);
    }
  }
  
  public function deleteSurveyQuestion($qid) {
    $this->deleteQuestion($qid);
    $this->deleteResponses($qid);
  }
  
}
