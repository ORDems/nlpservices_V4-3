<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpSurveyQuestion.
 */
/*
 * Name: voterdb_class_survey_question_nlp.php   V4.2 7/11/18
 */
namespace Drupal\voterdb;

require_once "voterdb_debug.php";

class NlpSurveyQuestion {
  
  const QUESTIONSTBL = 'survey_questions';
  
  private $questionList = array(
    'qid'=>'Qid',
    'questionName'=>'QuestionName',
    'questionType'=>'QuestionType',
    'cycle'=>'Cycle',
    'scriptQuestion'=>'ScriptQuestion'
  );

  
  function __construct($responsesObj) {
    $this->responsesObj = empty($responsesObj)?NULL:$responsesObj;
    $this->result = NULL;
  }
  
  private function deleteQuestion($qid) {
    db_set_active('nlp_voterdb');
    db_delete(self::QUESTIONSTBL)
    ->condition('Qid', $qid)
    ->execute();
    db_set_active('default');
  }

  private function insertQuestion($surveyFields) {
    $fields = array();
    foreach ($surveyFields as $nlpKey => $value) {
      $fields[$this->questionsList[$nlpKey]] = $value;
    }
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::QUESTIONSTBL)
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
  
  public function getSurveyQuestion() {
    $question = $this->fetchQuestion();
    if(empty($question)) {return NULL;} 
    $questionArray['questionName'] = $question['QuestionName'];
    $questionArray['qid'] = $question['Qid'];
    $questionArray['cycle'] = $question['Cycle'];
    $questionArray['questionType'] = $question['QuestionType'];
    $questionArray['scriptQuestion'] = $question['ScriptQuestion'];
    $responsesArray = $this->responsesObj->fetchResponses($question['Qid']);
    foreach ($responsesArray as $response) {
      $questionArray['responses'][$response['rid']] = $response['responseName'];
    }
    return $questionArray;
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
          'qid'=>$surveyQuestionId,
          'rid'=>$surveyResponseId,
          'responseName'=>$surveyResponseName,
          'questionName'=>$surveyQuestion['name'],
          );
      $this->responsesObj->insertResponse($responseFields);
    }
  }
  
  public function deleteSurveyQuestion($qid) {
    $this->deleteQuestion($qid);
    $this->deleteResponses($qid);
  }
  
}
