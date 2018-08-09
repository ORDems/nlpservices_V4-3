<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpMatchback.
 */
/*
 * Name: voterdb_class_matchback.php   V4.3  8/9/18
 */

namespace Drupal\voterdb;

class NlpMatchback {
  
  const MATCHBACKTBL = 'matchback';
  const DATESTBL = 'date_br';
  
  const MULTIINSERT = 100;
  const BATCH = 100;
  
  const DATEINDEX = 'DateIndex';

  public $dates = array();
  public $lastIndex = 0;

  private $records = array();
  private $sqlCnt = 0;
  private $batchCnt = 0;
  
  private $fields = array('VANID','DateIndex','County');
  
  private $matchbackVanHdr = array(
      'vanid' => array('name'=>'Voter File VANID','err'=>'Voter File VANID'),
      'ballotReceived' => array('name'=>'BallotReceived','err'=>'BallotReceived'),
      'party' => array('name'=>'Party','err'=>'Party'),
      'county' => array('name'=>'CountyName','err'=>'CountyName'), 
  );
  
  

  public function decodeMatchbackHdr($fileHdr) {
    //voterdb_debug_msg('header', $fileHdr);
    $state = variable_get('voterdb_state', 'Select');
    $hdrErr = array();
    $hdrPos = array();
    foreach ($this->matchbackVanHdr as $nlpKey => $vanField) {
      $found = FALSE;
      foreach ($fileHdr as $fileCol=>$fileColName) {
        if(trim($fileColName) == $vanField['name']) {
          $hdrPos[$nlpKey] = $fileCol;
          $found = TRUE;
          break;
        }
      }
      if(!$found) {
        if ($state == "Oregon") {
          $hdrErr[] = 'The MyCampaign export option "'.$vanField['err'].'" is missing.';
        }
      }
    }
    $fieldPos['pos'] = $hdrPos;
    $fieldPos['err'] = $hdrErr;
    $fieldPos['ok'] = empty($hdrErr);
    //voterdb_debug_msg('fieldpos', $fieldPos);
    return $fieldPos;
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
  
  public function getBrDates() {
    //voterdb_debug_msg('brdates: ', ''); 
    db_set_active('nlp_voterdb');
    $select = "SELECT * FROM {".self::DATESTBL."} WHERE  BRIndex<>0";
    $result = db_query($select);
    $this->lastIndex = 1;
    $this->dates = array();
    do {
      $dateRec = $result->fetchAssoc();
      if(!$dateRec) {break;}
      if($dateRec['BRIndex']>$this->lastIndex) {
        $this->lastIndex = $dateRec['BRIndex'];
      }
      $this->dates[$dateRec['BRDate']] = $dateRec['BRIndex'];
    } while (TRUE);
    db_set_active('default');
    //voterdb_debug_msg('Date Indexes: ', $date_indexes);
    return;
  }

 
  public function getBrDateIndex($date) {
    if(isset($this->dates[$date])) {
      $dateIndex = $this->dates[$date];
    } else {
      $dateIndex = ++$this->lastIndex;
      $this->dates[$date] = $dateIndex;
      db_set_active('nlp_voterdb');
      db_insert(self::DATESTBL)
        ->fields(array(
          'BRDate' => $date,
          'BRIndex' => $dateIndex,
        ))
        ->execute();
      db_set_active('default');
    }
    return $dateIndex;
  }
  
  public function getLatestMatchbackDate() {
    db_set_active('nlp_voterdb');
    $select = "SELECT * FROM {".self::DATESTBL."} WHERE  BRIndex=0";
    $result = db_query($select);
    db_set_active('default');
    $lastDate = $result->fetchAssoc();
    if(!$lastDate) {return NULL;}
    return $lastDate['BRDate'];
  }
  
  public function setLatestMatchbackDate($date) {
    db_set_active('nlp_voterdb');
    db_insert(self::DATESTBL)
      ->fields(array(
        'BRDate' => $date,
        'BRIndex' => 0,
      ))
      ->execute();
    db_set_active('default');
  }

}
