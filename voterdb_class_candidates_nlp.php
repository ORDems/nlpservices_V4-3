<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpCandidates.
 */
/*
 * Name: voterdb_class_candidates_nlp.php   V4.1 4/21/18
 *
 */
namespace Drupal\voterdb;

class NlpCandidates {
  
  const DB_CANDIDATE_TBL = "candidates";
  
  function __construct() {
    $this->result = NULL;
  }
  
  private function removeCandidate($qid) {
    if(empty($candidateIndex)) {return;}
    db_set_active('nlp_voterdb');
    db_delete('candidates')
    ->condition('Qid', $qid)
    ->execute();
    db_set_active('default');
  }

  private function insertCandidate($candidateArray) {
    voterdb_debug_msg('candidate array', $candidateArray );
    db_set_active('nlp_voterdb');
    try {
      db_insert('candidates')
        ->fields($candidateArray)
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
  
  
  private function fetchCandidates() {
    try{
      db_set_active('nlp_voterdb');
      $selectCandidates = "SELECT * FROM {".'candidates'."} WHERE 1";
      $resultCandidates = db_query($selectCandidates);    
    }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->getMessage();
      drupal_set_message('Opps: '.$error,'error');
      return NULL;
    }
    db_set_active('default');
    if(!$resultCandidates) {return NULL;}
    $candidatesArray = array();
    while ($row = $resultCandidates->fetchAssoc()) {
      $candidatesArray[] = $row;
    }
    return $candidatesArray;
  }
  
  private function fetchResponses($qid) {
    try{
      db_set_active('nlp_voterdb');
      $select = "SELECT * FROM {".'survey_responses'."} WHERE Qid=:qid";
      $args = array(':qid'=>$qid);
      $result = db_query($select,$args);    
    }
    catch (Exception $e) {
      db_set_active('default');
      //voterdb_debug_msg('e', $e->getMessage() );
      drupal_set_message('Opps','error');
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

  

 /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * getCandidates
 * 
 * 
 */
  public function getCandidates() {
    $candidatesArray = $this->fetchCandidates();
    if(empty($candidatesArray)) {return NULL;}
    foreach ($candidatesArray as $candidate) {
      $candidateIndexArray[$candidate['Qid']] = $candidate;
    }
    return $candidateIndexArray;
  }
  
  /** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
  * setCandidate
  * 
  * 
  */
  public function setCandidate($candidateArray) {
    if(isset($candidateArray['Qid'])){
      $candidateIndex = $candidateArray['Qid'];
      $this->removeCandidate($candidateIndex);
    }
    
    $this->insertCandidate($candidateArray);
    
  }
  
  public function deleteCandidate($qid) {
    $this->removeCandidate($qid);
  }
  
  public function getCandidateList($district) {
    $candidates = array();
    // Candidates will be listed in the order of scope, state, CD, County, etc.
    $cats = array('State','CD','County','HD','Pcts');
    $candidates[0] = 'Select Candidate';
    // For each of the scope catagories, find the candidates that are configured 
    // for this election and are relevant to this NL.
    $atLeastOne = FALSE;
    foreach ($cats as $cat) {
      db_set_active('nlp_voterdb');
      try {
        $query = db_select('candidates', 'c');
        $query->fields('c');
        $query->condition('Scope',$cat);
        switch ($cat) {
          case 'CD':
            $query->condition('CD',$district['cd']);
            break;
          case 'County':
            $query->condition('County',$district['county']);
            break;
          case 'HD':
            $query->condition('HD',$district['hd']);
            $query->condition('County',$district['county']);
            break;
          case 'Pcts':
            $query->condition('Pcts','%'.$district['pct'].'%','LIKE');
            $query->condition('County',$district['county']);
            break;
        }
        $query->orderBy('Weight');
        $result = $query->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return NULL;
      }
      db_set_active('default');

      while ($candidate_info = $result->fetchAssoc()) {
        $atLeastOne = TRUE;
        $candidates[$candidate_info['Qid']] = $candidate_info['Name'];
      }

    }
  if(!$atLeastOne) {return NULL;}
  return $candidates;
  }
  
  public function getResponsesList($qid) {
    $responses = array();
    $responses[0] = 'Select Response';
    
    $responsesArray = $this->fetchResponses($qid);
    foreach ($responsesArray as $responseArray) {
      $responses[$responseArray['Rid']] = $responseArray['ResponseName'];
    }
    return $responses;
  }
 
}
