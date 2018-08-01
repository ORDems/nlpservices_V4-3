<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpBallotCounts.
 */
/*
 * Name: voterdb_class_ballot_counts.php   V4.3  7/27/18
 */

namespace Drupal\voterdb;

class NlpBallotCounts {
  
  const BALLOTCOUNTSTBL = 'ballotcount';
  

  
  private $fields = array('VANID','DateIndex','County');
  
  private $ballotCountList = array(
    'index'=>'Index',
    'county'=>'County',
    'party'=>'Party',
    'regVoters'=>'RegVoters',
    'regVoted'=>'RegVoted',
  );
  
  private $ballotCountVanHdr1 = array(
      'county' => array('name'=>'County','err'=>'County'),
      'party' => array('name'=>'Party','err'=>'Party'),
      'voteDate' => array('name'=>'Vote Return Date','err'=>'Vote Return Date'),
      'total' => array('name'=>'Total People','err'=>'Total People'), 
  );
  
  private $ballotCountVanHdr2 = array(
      'balRet' => array('name'=>'Bal Ret','err'=>'Bal Ret'),
  );
  
  

  public function decodeBallotCountHdr($fileHdr,$fileHdr2) {
    //voterdb_debug_msg('header', $fileHdr);

    $hdrErr = array();
    $hdrPos = $hdrPos2 = array();
    foreach ($this->ballotCountVanHdr1 as $nlpKey => $vanField) {
      $found = FALSE;
      foreach ($fileHdr as $fileCol=>$fileColName) {
        if(trim($fileColName) == $vanField['name']) {
          $hdrPos[$nlpKey] = $fileCol;
          $found = TRUE;
          break;
        }
      }
      if(!$found) {
        $hdrErr[] = 'The crosstab export header "'.$vanField['err'].'" is missing.';
      }
    }
    $name = $this->ballotCountVanHdr2['balRet']['name'];
    $found = FALSE;
    foreach ($fileHdr2 as $fileCol=>$fileColName) {
        if(trim($fileColName) == $name) {
          $hdrPos2[$balRet] = $fileCol;
          $found = TRUE;
          break;
        }
      }
      if(!$found) {
        $hdrErr[] = 'The crosstab export header "'.$vanField['err'].'" is missing.';
      }
    
    $fieldPos['pos'] = $hdrPos;
    $fieldPos['pos']['balRet'] = $hdrPos2['balRet'];
    $fieldPos['err'] = $hdrErr;
    $fieldPos['ok'] = empty($hdrErr);
    //voterdb_debug_msg('fieldpos', $fieldPos);
    return $fieldPos;
  }
  
  public function updateBallotCount($cnts) {
  db_set_active('nlp_voterdb');
  db_merge(self::BALLOTCOUNTSTBL)
    ->key(array(
      'County' => $cnts['county'],
      'Party' => $cnts['party']))
    ->fields(array(
      'RegVoters' => $cnts['regVoters'],
      'RegVoted' => $cnts['regVoted']))
    ->execute();
  db_set_active('default');
  return TRUE;
}
  
  
  public function insertMatchbacks($county,$vanid,$dateIndex) {
    $record = array(
      'VANID' => $vanid,
      'DateIndex' => $dateIndex,
      'County' => $county,
    );
    $batchSubmit = FALSE;
    $this->records[$this->sqlCnt++] = $record;
    // When we reach 100 records, insert all of them in one query.
    if ($this->sqlCnt == self::MULTIINSERT) {
      $this->sqlCnt = 0;
      $this->batchCnt++;
      db_set_active('nlp_voterdb');
      $query = db_insert(self::MATCHBACKTBL)
        ->fields($this->fields);
      foreach ($this->records as $record) {
        $query->values($record);
      }
      $query->execute();
      db_set_active('default');
      $this->records = array();
      if($this->batchCnt==self::BATCH) {
        $batchSubmit = TRUE;
      }
    }
    return $batchSubmit;
  }
  
  public function flushMatchbacks() {
    if(empty($this->records)) {return;}
    db_set_active('nlp_voterdb');
    $query = db_insert(self::MATCHBACKTBL)
      ->fields($this->fields);
    foreach ($this->records as $record) {
      $query->values($record);
    }
    $query->execute();
    db_set_active('default');
    $this->records = array();
  }
  
  
  
  public function getMatchbackExists($vanid) {
    db_set_active('nlp_voterdb');
    $exists = db_query('SELECT 1 FROM {'.self::MATCHBACKTBL.'} WHERE VANID = '.$vanid)
      ->fetchField();
    db_set_active('default');
    return $exists;
  }

}
