<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpCrosstabCounts.
 */
/*
 * Name: voterdb_class_ballot_crosstabs_and_counts.php   V4.3  7/27/18
 */

namespace Drupal\voterdb;

class NlpCrosstabCounts {
  
  const CROSSTABCOUNTSTBL = 'ballotcount';
  

  
  private $fields = array('VANID','DateIndex','County');
  
  private $crosstabCountList = array(
    'index'=>'Index',
    'county'=>'County',
    'party'=>'Party',
    'regVoters'=>'RegVoters',
    'regVoted'=>'RegVoted',
  );
  
  private $crosstabCountVanHdr1 = array(
      'county' => array('name'=>'County','err'=>'County'),
      'party' => array('name'=>'Party','err'=>'Party'),
      'voteDate' => array('name'=>'Vote Return Date','err'=>'Vote Return Date'),
      'total' => array('name'=>'Total People','err'=>'Total People'), 
  );
  
  private $crosstabCountVanHdr2 = array(
      'balRet' => array('name'=>'Bal Ret','err'=>'Bal Ret'),
  );
  
  

  public function decodeCrosstabCountHdr($fileHdr,$fileHdr2) {
    //voterdb_debug_msg('header', $fileHdr);

    $hdrErr = array();
    $hdrPos = array();
    foreach ($this->crosstabCountVanHdr1 as $nlpKey => $vanField) {
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
    $name = $this->crosstabCountVanHdr2['balRet']['name'];
    $found = FALSE;
    foreach ($fileHdr2 as $fileCol=>$fileColName) {
        if(trim($fileColName) == $name) {
          $hdrPos['balRet'] = $fileCol;
          $found = TRUE;
          break;
        }
      }
      if(!$found) {
        $hdrErr[] = 'The crosstab export header "'.$vanField['err'].'" is missing.';
      }
    
    $fieldPos['pos'] = $hdrPos;
    $fieldPos['err'] = $hdrErr;
    $fieldPos['ok'] = empty($hdrErr);
    //voterdb_debug_msg('fieldpos', $fieldPos);
    return $fieldPos;
  }
  
  public function updateCrosstabCount($cnts) {
  db_set_active('nlp_voterdb');
  db_merge(self::CROSSTABCOUNTSTBL)
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
  
  public function fetchCrosstabCounts() {
    db_set_active('nlp_voterdb');
    $select = "SELECT * FROM {".self::CROSSTABCOUNTSTBL."} WHERE  1";
    $result = db_query($select);
    $counts = array();
    do {
      $count = $result->fetchAssoc();
      if(empty($count)) {break;}
      $counts[$count['County']][$count['Party']]['regVoted'] = $count['RegVoted'];
      $counts[$count['County']][$count['Party']]['regVoters'] = $count['RegVoters'];
    } while (TRUE);
    db_set_active('default');
    return $counts;
  }

}
