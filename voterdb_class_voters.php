<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpVoters.
 */
/*
 * Name: voterdb_class_voters.php   V4.2  6/17/18
 */

namespace Drupal\voterdb;

class NlpVoters {
  
  const VOTERTBL = 'voter';
  const VOTERGRPTBL = 'voter_grp';
  const VOTERSTATUSTBL = 'voter_status';
  

  private $grpList = array(
    'indx' => 'indx',
    'county' => 'County',
    'grpType' => 'Grp_Type',
    'mcid' => 'MCID',
    'vanid' => 'VANID',
    'turfIdnex' => 'NLTurfIndex',
  );
  private $statusList = array(  
    'vanid' => 'VANID',
    'dorCurrent' => 'DORCurrent',
    'moved' => 'Moved',
    'deceased' => 'Deceased',
    'hostile' => 'Hostile',
    'nlpVoter' => 'NLPVoter',
  );
  public $nlList = array(
    'vanid' => 'VANID',
    'lastName' => 'LastName',
    'firstName' => 'FirstName',
    'nickname' => 'Nickname',
    'age' => 'Age',
    'sex' => 'Sex',
    'streetNo' => 'StreetNo',
    'streetPrefix' => 'StreetPrefix',
    'streetName' => 'StreetName',
    'streetType' => 'StreetType',
    'city' => 'City',
    'county' => 'County',
    'cd' => 'CD',
    'hd' => 'HD',
    'pct' => 'Pct',
    'homePhone' => 'HomePhone',
    'cellPhone' => 'CellPhone',
    'aptType' => 'AptType',
    'aptNo' => 'AptNo',
    'mAddress' => 'mAddress',
    'mCity' => 'mCity',
    'mState' => 'mState',
    'mZip' => 'mZip',
    'voting' => 'Voting',
    'votingDisplay' => 'VotingDisplay',
    'dateReg' => 'DateReg',
    'dorCurrent' => 'DORCurrent',
    'party' => 'Party',
  );
  
  
  
  public function getNewNlpVoterIds($limit) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERTBL, 'v');
      $query->join(self::VOTERSTATUSTBL, 's', 's.VANID = v.VANID' );
      $query->addField('v','VANID');
      $query->range(0, $limit);
      $query->isNull('NLPVoter');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    $voterIds = array();
    do {
      $voter = $result->fetchAssoc();
      if(empty($voter)) {break;}
      $voterIds[] = $voter['VANID'];
    } while (TRUE);
  return $voterIds;
  }
  
  
  public function getAllNlpVoterIds() {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERTBL, 'v');
      //$query->join(self::VOTERSTATUSTBL, 's', 's.VANID = v.VANID' );
      $query->addField('v','VANID');
      //$query->condition('NLPVoter',FALSE);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    $voterIds = array();
    do {
      $voter = $result->fetchAssoc();
      if(empty($voter)) {break;}
      $voterIds[] = $voter['VANID'];
    } while (TRUE);
  return $voterIds;
  }
  
  
  
  public function getVoterStatus($vanid) {
    $keys = array_keys($this->statusList);
    foreach ($keys as $key) {
      $null[$key] = NULL;
    }
    //voterdb_debug_msg('null', $null);
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERSTATUSTBL, 's');
      $query->fields('s');
      $query->condition('VANID',$vanid);
      //$query->condition(VM_DORCURRENT,$dorc);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return $null;
    }
    db_set_active('default');
    //voterdb_debug_msg('result', $result);
    $statusFlip = array_flip($this->statusList);
    $dbStatus = $result->fetchAssoc();
    //voterdb_debug_msg('status', $dbStatus);
    if(empty($dbStatus)) {return $null;}
    foreach ($dbStatus as $dbKey => $value) {
      $status[$statusFlip[$dbKey]] = $value;
    }
    return $status;
  }
  
  function setVoterStatus($vanid, $fields) {
    //voterdb_debug_msg('fields', $fields);
    foreach ($fields as $nlpKey => $value) {
      $dbFields[$this->statusList[$nlpKey]] = $value;
    }
    $dbFields['VANID'] = $vanid;
    //voterdb_debug_msg('fdbields', $dbFields);
    db_set_active('nlp_voterdb');
    try {
      db_merge(self::VOTERSTATUSTBL)
        ->key(array(
          'VANID' => $vanid))
        ->fields($dbFields)
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    return TRUE;
  }
  
  
}
