<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpVoters.
 */
/*
 * Name: voterdb_class_voters.php   V4.3  8/8/18
 */

namespace Drupal\voterdb;

use Drupal\voterdb\NlpMatchback;
use Drupal\voterdb\NlpReports;


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
    'turfIndex' => 'NLTurfIndex',
    'voterStatus' => 'Status',
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
  
  
  public function getVoterCount($county) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERGRPTBL, 'g');
      $query->addField('g','VANID');
      if(!empty($county)) {
        $query->condition('County',$county);
      }
      $voterCount = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');
    return $voterCount;
  }
  
  public function getVoted($county,$matchbackObj) {

    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERGRPTBL, 'g');
      $query->join($matchbackObj::MATCHBACKTBL, 'm', 'g.VANID = m.VANID');
      $query->condition('g.County',$county);
      $query->isNotNull($matchbackObj::DATEINDEX);
      $votedCount = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');
    return $votedCount;
  }
  
  public function getVotedAndContacted($county,$matchbackObj,$reportsObj) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERGRPTBL, 'g');
      $query->join($matchbackObj::MATCHBACKTBL, 'm', 'g.VANID = m.VANID AND g.County = :county',
              array(':county' => $county));
      $query->addField('g','VANID');
      $query->isNotNull('m.'.$matchbackObj::DATEINDEX);
      $voted = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');
    $contactedCount = 0;
    do {
      $voter = $voted->fetchAssoc();
      if(empty($voter)) {break;}
      $voterContacted = $reportsObj->voterContacted($voter['VANID']);
      if($voterContacted) {$contactedCount++;}
    } while (TRUE);
    return $contactedCount;
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
  
  public function getParticipatingCounties() {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::VOTERGRPTBL, 'g');
      $query->addField('g', 'County');
      $query->distinct();
      $query->orderBy('County');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $countyNames = array();
    do {
      $record = $result->fetchAssoc();
      if(empty($record)) {break;}
      $countyNames[] = $record['County'];
    } while (TRUE);
    return $countyNames;
  }
  
  function getVotersInTurf($turfIndex) {
    db_set_active('nlp_voterdb');
    try {
      $tselect = "SELECT VANID FROM {".self::VOTERGRPTBL."} WHERE  ".
        "NLTurfIndex = :index AND Status <> 'M'";
      $targs = array(':index' => $turfIndex,);
      $result = db_query($tselect,$targs);   
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    $voters = $array();
    do {
      $voter = $result->fetchAssoc();
      if(empty($voter)) {break;}
      $voters[$voter['VANID']] = $voter['VANID'];
    } while (TRUE);
    return $voters;
  }
  
}
