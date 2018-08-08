<?php
/*
 * Name: voterdb_class_canvass_response_nlp.php   V4.0 2/22/18
 *
 */
namespace Drupal\voterdb;

class NlpCanvassResponse {
  
  function __construct() {
    $this->result = NULL;
  }
  
  public function getCanvassResponse() {
  
  }
  
  public function setCanvassResponse($canvassResult) {
    // Insert the reported information into the results table.
    db_set_active('nlp_voterdb');
    db_insert('results')
      ->fields(array(
        'Cycle' => $canvassResult['cycle'],
        'County' => $canvassResult['county'],
        'Active' => TRUE,
        'MCID' => $canvassResult['mcid'],
        'VANID' => $canvassResult['vanid'],
        'Cdate' => $canvassResult['date'],
        'Type' => $canvassResult['type'],
        'Value' => $canvassResult['value'],
        'Text' => $canvassResult['text'],
        'Qid' => $canvassResult['qid'],
        'Rid' => $canvassResult['rid'],
      ))
      ->execute();
    db_set_active('default');
  }
  
  public function deleteSurveyQuestion($qid) {
    $this->deleteQuestion($qid);
  }
  
}
