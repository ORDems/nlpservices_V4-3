<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpNls.
 */
/*
 * Name: voterdb_class_nls.php   V4.2  6/5/18
 */

namespace Drupal\voterdb;


define('DH_MCID', 'My Campaign ID');
define('DH_FNAME', 'FirstName');
define('DH_LNAME', 'LastName');
define('DH_NICKNAME', 'Nickname');
define('DH_COUNTY', 'CountyName');
define('DH_HD', 'HD');
define('DH_PCT', 'PrecinctName');
define('DH_ADDR', 'Address');
define('DH_EMAIL', 'PreferredEmail');
define('DH_PHONE', 'Preferred Phone');
define('DH_HOMEPHONE', 'Home Phone');
define('DH_CELLPHONE', 'Cell Phone');
define('DH_CITY', 'City');
define('DH_SALUTATION', 'Salutation'); // Depreciated.

define('DH_MESSAGE_ARRAY', serialize(array(
    'MCID', 'Name', '', 'Nickname',
    'County',
    'Legislative Districts', 'PrecinctName', 'Primary Address (MyCampaign)', 'Email',
    'Preferred Phone', 'Home Phone', 'Cell Phone', 'City'
)));

class NlpNls {
  
  const NLSTBL = 'nls';
  const NLSGRPTBL = 'nls_grp';
  const NLSSTATUSTBL = 'nls_status';
  const NLSSTATUSHISTORYTBL = 'nls_status_history';
  
  const CANVASS = 'canvass';
  const MINIVAN = 'minivan';
  const PHONE = 'phone';
  const POSTCARD = 'mail';
  
  const NOTESDBLENGTH = '81';  // Length of the notes field in database.
  const NOTESMAX = '75';   // Notes max length of the note.
  const NOTESWRAP = '25';  // Notes max length for single line.
   
  public $contactList = array(
      'canvass'=>self::CANVASS,
      'minivan'=>self::MINIVAN,
      'phone'=>self::PHONE,
      'postcard'=>self::POSTCARD,
  );
  
  const DASH = '-';
  const ASKED = 'Asked';
  const YES = 'Yes';
  const NO = 'No';
  const QUIT = 'Quit';
  
  public $askList = array(
      'select'=>self::DASH,
      'asked'=>self::ASKED,
      'yes'=>self::YES,
      'no'=>self::NO,
      'quit'=>self::QUIT,
  );


  
  private $statusList = array(
    'mcid'=>'MCID',
    'county'=>'County',
    'loginDate'=>'Login_Date',
    'contact'=>'Contact', 
    'nlSignup'=>'NLSignup',
    'turfCut'=>'TurfCut',
    'turfDelivered'=>'TurfDelivered',
    'resultsReported'=>'ResultsReported',
    'asked'=>'Asked',
    'notes'=>''
  );
  public $nlList = array(
    'mcid'=>'MCID',
    'firstName'=>'FirstName',
    'lastName'=>'LastName',
    'nickname'=>'Nickname',
    'county'=>'County',
    'hd'=>'HD',
    'pct'=>'Pct',
    'address'=>'Address',
    'email'=>'Email',
    'phone'=>'Phone',
    'homePhone'=>'HomePhone',
    'cellPhone'=>'CellPhone'
  );
  private $historyList = array(
    'date' => 'Date',
    'mcid' => 'MCID',
    'county' => 'MCID',
    'cycle' => 'Cycle',
    'status' => 'Status',
    'nlFirstName' => 'NLfname',
    'nlLastName' => 'NLlname'
  );
  
  private $nlVanHdr = array(
      'mcid' => array('name'=>'My Campaign ID','err'=>'MCID'),
      'firstName' => array('name'=>'FirstName','err'=>'Name'),
      'lastName' => array('name'=>'LastName','err'=>'Name'),
      'nickname' => array('name'=>'Nickname','err'=>'Nickname'),
      'county' => array('name'=>'County','err'=>'County'),
      'hd' => array('name'=>'HD','err'=>'Legislative Districts'),
      'pct' => array('name'=>'PrecinctName','err'=>'PrecinctName'),
      'address' => array('name'=>'Address','err'=>'Primary Address (MyCampaign)'),
      'city' => array('name'=>'City','err'=>'City'),
      'email' => array('name'=>'PreferredEmail','err'=>'Email'),
      'phone' => array('name'=>'Preferred Phone','err'=>'Preferred Phone'),
      'homePhone' => array('name'=>'Home Phone','err'=>'Home Phone'),
      'cellPhone' => array('name'=>'Cell Phone','err'=>'Cell Phone'),  
  );
  
  

  private function decodeNlHdr($fileHdr) {
    $hdrErr = array();
    $hdrPos = array();
    foreach (self::nlVanHdr as $nlpKey => $vanField) {
      $found = FALSE;
      foreach ($fileHdr as $fileCol=>$fileColName) {
        if($fileColName == $vanField['name']) {
          $hdrPos[$nlpKey] = $fileCol;
          $found = TRUE;
        }
      }
      if(!$found) {
        $hdrErr[] = 'The MyCasmpaign export option "'.$vanField['err'].'" is missing.';
      }
    }
    $fieldPos['fields'] = $hdrPos;
    $fieldPos['err'] = $hdrErr;
    $fieldPos['ok'] = empty($hdrErr);
    return $fieldPos;
  }


  
  private function deleteNl($mcid) {
    db_set_active('nlp_voterdb');
    db_delete(self::NLSTBL)
    ->condition('MCID', $mcid)
    ->execute();
    db_set_active('default');
  }
  
  
  public function createNl($nlRecord) {
    $fields = array();
    foreach ($nlRecord as $nlpKey => $dbField) {
      $fields[$dbField] = $nlRecord[$nlpKey];
    }
    $this->deleteNl($nlRecord['MCID']);
    db_set_active('nlp_voterdb');
    try {
      db_insert(self::NLSTBL)
        ->fields($fields)
        ->execute();
      }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->errorInfo;
      $args = $e->args;
      $error['MCID'] = $args[':db_insert_placeholder_0'];
      $error['LName'] = $args[':db_insert_placeholder_1'];
      voterdb_debug_msg('error', $error , __FILE__, __LINE__);
      return FALSE;
    }
    return TRUE;
  }
  
  function getNls($county,$hd) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSTBL, 'n');
      $query->fields('n');
      $query->join(self::NLSGRPTBL, 'g', 'n.MCID = g.MCID');
      $query->condition('g.County',$county);
      $query->orderBy('HD');
      $query->orderBy('LastName');
      $query->orderBy('Nickname');
      if ($hd != 'All') {
        $query->condition('HD',$hd);
      }
      $results =  $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return NULL;
    }
    db_set_active('default');
    // Fetch each NL record and build the array of information about each NL
    // needed to build the display table.
    do {
      $fields = $results->fetchAssoc();
      if(empty($fields)) {break;}
      $nlRecord = array();
      foreach ($nlList  as $nlpKey => $dbKey) {
        if(isset($fields[$dbKey])) {
          $nlRecord[$nlpKey] = $fields[$dbKey];
        } else {
          $nlRecord[$nlpKey] = NULL;
        }
      }
      $mcid = $nlRecord['MCID'];
      $nlRecord['status'] = $this->getNlsStatus($mcid,$county);
      $nlRecords[$mcid] = $nlRecord;
    } while (TRUE);
    return $nlRecords;
  }

  
  public function countNls($county) {
    // Create the count query for the NL count in a county.
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSGRPTBL,'g');
      $query->fields('g');
      $query->condition('County',$county);
      $cnt = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    db_set_active('default');
    return $cnt;
  }
 
  public function getNlsStatus($mcid,$county) {
    db_set_active('nlp_voterdb');
    try {
      $select = "SELECT * FROM {".self::NLSSTATUSTBL."} WHERE  ".
        "County = :county AND MCID = :mcid";
      $args = array(
        ':county' => $county,
        ':mcid' => $mcid);
      $result = db_query($select,$args);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    $nlDbStaus = $result->fetchAssoc();
    if(empty($nlDbStaus)) {
      $nlStaus = array();
      $nlpKeys = key($this->statusList);
      foreach ($nlpKeys as $nlpKey) {
        $nlStaus[$nlpKey] = NULL;
      }
      $nlStaus['mcid'] = $mcid;
      $nlStaus['county'] = $county;
      $nlStaus['contact'] = self::CANVASS;
    } else {
      foreach ($this->statusList as $nlpKey => $dbFieldName) {
        $nlStaus[$nlpKey] = $nlDbStaus[$dbFieldName];
      }
    }
    db_set_active('default');
    return $nlStaus;
  }

  public function setNlsStatus($status) {
    foreach ($this->statusList as $nlpKey => $dbFieldName) {
      $nlDbStaus[$dbFieldName] = $status[$nlpKey];
    }
    db_set_active('nlp_voterdb');
    try {
      db_merge(self::NLSSTATUSTBL)
        ->fields($nlDbStaus)
        ->key(array(
          'MCID' => $status['mcid'],
          'County' => $status['county']))
        ->execute();
      db_set_active('default');
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    db_set_active('default');
  }
  
  function setStatusHistory($statusHistory) {
    db_set_active('nlp_voterdb');
    $date = date('Y-m-d G:i:s');
    foreach ($this->historyList as $nlpKey => $dbFieldName) {
      $nlDbStaus[$dbFieldName] = (isset($statusHistory[$nlpKey]))?$statusHistory[$nlpKey]:NULL;
    }
    $nlDbStaus['Date'] = $date;
    $nlDbStaus['Cycle'] = variable_get('voterdb_ecycle', 'yyyy-mm-t');
    try {
      db_insert(self::NLSSTATUSHISTORYTBL)
        ->fields($nlDbStaus)
        ->execute();
      db_set_active('default');
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    return;
  }

  public function createNlGrp($mcid,$county) {
    try {
      db_insert(self::NLSGRPTBL)
        ->fields(array(
          'MCID' => $mcid,
          'County' => $county,
        ))
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      $pn_error = $e->errorInfo;
      voterdb_debug_msg('error', $pn_error , __FILE__, __LINE__);
      db_set_active('nlp_voterdb');
    }
  }
  
  public function deleteNlGrp($county) {
    db_set_active('nlp_voterdb');
    try {
      db_delete(self::NLSGRPTBL)
        ->condition('County', $county)
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return;
    }
  }
}
