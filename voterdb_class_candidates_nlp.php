<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpCandidates.
 */
/*
 * Name: voterdb_class_candidates_nlp.php   V4.3 9/12/18
 *
 */
namespace Drupal\voterdb;

class NlpCandidates {
  
  const CANDIDATETBL = "candidates";
  const RESPONSETBL = "survey_responses";
  
  public $candidateList = array(
    'qid' => 'Qid',
    'name' => 'Name',
    'weight' => 'Weight',
    'scope' => 'Scope',
    'cd' => 'CD',
    'county' => 'County',
    'hd' => 'HD',
    'pcts' => 'Pcts',
  );
  
  function __construct($responsesObj) {
    $this->responsesObj = empty($responsesObj)?NULL:$responsesObj;
    $this->result = NULL;
  }
  
  private function removeCandidate($qid) {
    if(empty($qid)) {return;}
    db_set_active('nlp_voterdb');
    db_delete(self::CANDIDATETBL)
    ->condition('Qid', $qid)
    ->execute();
    db_set_active('default');
  }

  private function insertCandidate($candidate) {
    //voterdb_debug_msg('candidate array', $candidate);
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::CANDIDATETBL)
        ->fields($candidate)
        ->execute();
      }
    catch (Exception $e) {
      db_set_active('default');
      drupal_set_message('Opps: '.$e->getMessage(),'error');
      return;
    }
    db_set_active('default');
  }
  
  private function fetchCandidates() {
    try{
      db_set_active('nlp_voterdb');
      $selectCandidates = "SELECT * FROM {".self::CANDIDATETBL."} WHERE 1";
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
    $candidateFlip = array_flip($this->candidateList);
    $candidates = array();
    while ($dbCandidate = $resultCandidates->fetchAssoc()) {
      foreach ($dbCandidate as $dbKey => $value) {
        $candidate[$candidateFlip[$dbKey]] = $value;
      }
      $candidates[] = $candidate;
    }
    return $candidates;
  }
  
  private function fetchResponses($qid) {
    try{
      db_set_active('nlp_voterdb');
      $select = "SELECT * FROM {".self::RESPONSETBL."} WHERE Qid=:qid";
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

  public function getCandidates() {
    $candidatesArray = $this->fetchCandidates();
    if(empty($candidatesArray)) {return NULL;}
    foreach ($candidatesArray as $candidate) {
      $candidateIndexArray[$candidate['qid']] = $candidate;
    }
    return $candidateIndexArray;
  }

  public function setCandidate($candidate) {
    if(isset($candidate['Qid'])){
      $qid = $candidate['Qid'];
      $this->removeCandidate($qid);
    }
    $this->insertCandidate($candidate);
  }
  
  public function updateCandidate($candidate) {
    db_set_active('nlp_voterdb');
    db_merge(self::CANDIDATETBL)
      ->key(array('Qid'=> $candidate['qid']))
      ->fields(array(
        'Weight' => $candidate['weight'],
        'Name' => $candidate['name'],
      ))
      ->execute();
    db_set_active('default');
  }
  
  public function deleteCandidate($qid) {
    $this->removeCandidate($qid);
    $this->deleteResponses($qid);
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
        $query = db_select(self::CANDIDATETBL, 'c');
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
