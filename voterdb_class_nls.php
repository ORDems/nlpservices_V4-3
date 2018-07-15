<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpNls.
 */
/*
 * Name: voterdb_class_nls.php   V4.2  6/17/18
 */

namespace Drupal\voterdb;

class NlpNls {
  
  const NLSTBL = 'nls';
  const NLSGRPTBL = 'nls_grp';
  const NLSSTATUSTBL = 'nls_status';
  const NLSSTATUSHISTORYTBL = 'nls_status_history';
  
  const CANVASS = 'canvass';
  const MINIVAN = 'minivan';
  const PHONE = 'phone';
  const MAIL = 'mail';
  
  const NOTESDBLENGTH = '81';  // Length of the notes field in database.
  const NOTESMAX = '75';   // Notes max length of the note.
  const NOTESWRAP = '25';  // Notes max length for single line.
   
  public $contactList = array(
      'canvass'=>self::CANVASS,
      'minivan'=>self::MINIVAN,
      'phone'=>self::PHONE,
      'mail'=>self::MAIL,
  );
  
  const DASH = '-';
  const ASKED = 'Asked';
  const YES = 'Yes';
  const NO = 'No';
  const QUIT = 'Quit';
  
  public $askList = array(
      '-'=>self::DASH,
      'asked'=>self::ASKED,
      'yes'=>self::YES,
      'no'=>self::NO,
      'quit'=>self::QUIT
  );
  
  public $askHistory = array(
      'asked' => self::HISTORYASKED,
      'yes' => self::HISTORYSIGNEDUP,
      'no' => self::HISTORYDECLINED,
      'quit' => self::HISTORYQUIT,
  );


  
  private $statusList = array(
    'mcid'=>'MCID',
    'county'=>'County',
    'loginDate'=>'Login_Date',
    'contact'=>'Contact', 
    'nlSignup'=>'NLSignup',
    'turfCut'=>'Turfcut',
    'turfDelivered'=>'TurfDelivered',
    'resultsReported'=>'ResultsReported',
    'asked'=>'Asked',
    'notes'=>'Notes'
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
  

  const HISTORYASKED = 'Asked';
  const HISTORYDECLINED = 'Declined';
  const HISTORYSIGNEDUP = 'Signed up';
  const HISTORYTURFCHECKEDIN = 'Checked in turf';
  const HISTORYDELIVEREDTURF = 'Delivered turf';
  const HISTORYREPORTEDRESULTS = 'Reported results';
  const HISTORYQUIT = 'Quit';
  
  private $historyList = array(
    'date' => 'Date',
    'mcid' => 'MCID',
    'county' => 'County',
    'cycle' => 'Cycle',
    'status' => 'Status',
    'nlFirstName' => 'NLfname',
    'nlLastName' => 'NLlname'
  );
  
  private $nlVanHdr = array(
      'mcid' => array('name'=>'VANID','err'=>'VANID'),
      'firstName' => array('name'=>'FirstName','err'=>'Name'),
      'lastName' => array('name'=>'LastName','err'=>'Name'),
      'nickname' => array('name'=>'Nickname','err'=>'Nickname'),
      'county' => array('name'=>'CountyName','err'=>'County'),
      'hd' => array('name'=>'HD','err'=>'Legislative Districts'),
      'pct' => array('name'=>'PrecinctName','err'=>'PrecinctName'),
      'address' => array('name'=>'Address','err'=>'Primary Address (MyCampaign)'),
      'city' => array('name'=>'City','err'=>'City'),
      'email' => array('name'=>'PreferredEmail','err'=>'Email'),
      'phone' => array('name'=>'Preferred Phone','err'=>'Preferred Phone'),
      'homePhone' => array('name'=>'Home Phone','err'=>'Home Phone'),
      'cellPhone' => array('name'=>'Cell Phone','err'=>'Cell Phone'),  
  );
  
  

  public function decodeNlHdr($fileHdr) {
    //voterdb_debug_msg('header', $fileHdr);
    $hdrErr = array();
    $hdrPos = array();
    foreach ($this->nlVanHdr as $nlpKey => $vanField) {
      $found = FALSE;
      foreach ($fileHdr as $fileCol=>$fileColName) {
        if($fileColName == trim($vanField['name'])) {
          $hdrPos[$nlpKey] = $fileCol;
          $found = TRUE;
          break;
        }
      }
      if(!$found) {
        $hdrErr[] = 'The MyCampaign export option "'.$vanField['err'].'" is missing.';
      }
    }
    $fieldPos['pos'] = $hdrPos;
    $fieldPos['err'] = $hdrErr;
    $fieldPos['ok'] = empty($hdrErr);
    //voterdb_debug_msg('fieldpos', $fieldPos);
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
    //voterdb_debug_msg('nlrecord', $nlRecord);
    $fields = array();
    foreach ($nlRecord as $nlpKey => $dbField) {
      $dbKey = $this->nlList[$nlpKey];
      $fields[$dbKey] = $dbField;
    }
    $this->deleteNl($nlRecord['mcid']);
    //voterdb_debug_msg('fields', $fields);
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
      voterdb_debug_msg('error', $error );
      return FALSE;
    }
    return TRUE;
  }
  
  public function getNl($firstName, $lastName, $county) {
    // Replace the apostrophe with the HTML code for MySQL.
    // This lets us have names like O'Brian in the database.
    $lname = str_replace("'", "&#039;", trim ( $lastName , " \t\n\r\0\x0B" ));
    $fname = str_replace("'", "&#039;", trim ( $firstName , " \t\n\r\0\x0B" ));
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSTBL, 'n');
      $query->join(self::NLSGRPTBL, 'g', 'g.MCID = n.MCID');
      $query->addField('n', 'MCID');
      $query->addField('n', 'HD');
      $query->addField('n', 'Pct');
      $query->condition('Nickname',$fname);
      $query->condition('LastName',$lname);
      $query->condition('g.County',$county);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $nl = $result->fetchAssoc();
    if(empty($nl)) {return FALSE;}  // NL not known.
    $nlListFlip = array_flip($this->nlList);
    foreach ($nl as $dbKey => $nlValue) {
      $nlRecord[$nlListFlip[$dbKey]] = $nlValue;
    }
    return $nlRecord;  //return the MCID and HD.
  }
  
  public function getNlById($mcid) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSTBL, 'n');
      $query->fields('n');
      $query->condition('MCID',$mcid);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $nl = $result->fetchAssoc();
    if(empty($nl)) {return FALSE;}  // NL not known.
    $nlListFlip = array_flip($this->nlList);
    foreach ($nl as $dbKey => $nlValue) {
      $nlRecord[$nlListFlip[$dbKey]] = $nlValue;
    }
    return $nlRecord;  //return the MCID and HD.
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
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    // Fetch each NL record and build the array of information about each NL
    // needed to build the display table.
    do {
      $fields = $results->fetchAssoc();
      if(empty($fields)) {break;}
      $nlRecord = array();
      foreach ($this->nlList  as $nlpKey => $dbKey) {
        if(isset($fields[$dbKey])) {
          $nlRecord[$nlpKey] = $fields[$dbKey];
        } else {
          $nlRecord[$nlpKey] = NULL;
        }
      }
      $mcid = $nlRecord['mcid'];
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
      voterdb_debug_msg('e', $e->getMessage() );
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
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    $nlDbStaus = $result->fetchAssoc();
    if(empty($nlDbStaus)) {
      //voterdb_debug_msg('nldbstatus', $nlDbStaus);
      $nlStaus = array();
      $nlpKeys = array_keys($this->statusList);
      foreach ($nlpKeys as $nlpKey) {
        $nlStaus[$nlpKey] = NULL;
      }
      $nlStaus['mcid'] = $mcid;
      $nlStaus['county'] = $county;
      $nlStaus['contact'] = self::CANVASS;
      $nlStaus['asked'] = self::DASH;
      //voterdb_debug_msg('nlstatus', $nlStaus);
    } else {
      //voterdb_debug_msg('nldbstatus', $nlDbStaus);
      foreach ($this->statusList as $nlpKey => $dbFieldName) {
        $nlStaus[$nlpKey] = $nlDbStaus[$dbFieldName];
      }
      //voterdb_debug_msg('nlstatus', $nlStaus);
      $askListFlip = array_flip($this->askList);
      //voterdb_debug_msg('asklistflipped', $askListFlip);
      $nlStaus['asked'] = $askListFlip[$nlStaus['asked']];
    }
    db_set_active('default');
    return $nlStaus;
  }

  public function setNlsStatus($status) {
    //voterdb_debug_msg('status', $status);
    //$backTrace = debug_backtrace(); 
    //voterdb_debug_msg('caller, Line: '.$backTrace[0]['line'], $backTrace[0]['file']);
    foreach ($this->statusList as $nlpKey => $dbFieldName) {
      $nlDbStaus[$dbFieldName] = $status[$nlpKey];
    }
    //voterdb_debug_msg('fields', $nlDbStaus);
    $nlDbStaus['Asked'] = $this->askList[$nlDbStaus['Asked']];
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
      //voterdb_debug_msg('e', $e->getMessage() );
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
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    return;
  }

  public function createNlGrp($mcid,$county) {
    //voterdb_debug_msg('mcid', $mcid);
    db_set_active('nlp_voterdb');
    
    try {
      db_insert(self::NLSGRPTBL)
        ->fields(array(
          'MCID' => $mcid,
          'County' => $county,
        ))
        ->execute();
    }
    catch (Exception $e) {
      voterdb_debug_msg('error', $e->getMessage() );
      db_set_active('default');
    }
   db_set_active('default');
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
      voterdb_debug_msg('e', $e->getMessage() );
      return;
    }
    db_set_active('default');
  }
  
  public function getHdList($county) {
    // Get the list of distinct HD numbers for this group, order numerically.
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSGRPTBL, 'g');
      $query->join(self::NLSTBL, 'n', 'g.MCID = n.MCID');
      $query->addField('n', 'HD');
      $query->distinct();
      $query->condition('g.County',$county);
      $query->orderBy('HD');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $hdOptions  = array();
    do {
      $hd = $result->fetchAssoc();
      if(empty($hd)) {break;}
      $hdOptions[] = $hd['HD'];
    } while (TRUE);
    return $hdOptions;
  }
  
  public function getPctList($county,$hd) {
    // Get the list of precinct numbers with at least one prospective NL in 
    // this HD, order numberically by precinct number.
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSGRPTBL, 'g');
      $query->join(self::NLSTBL, 'n', 'g.MCID = n.MCID');
      $query->addField('n', 'Pct');
      $query->distinct();
      $query->condition('g.County',$county);
      $query->condition('HD',$hd);
      $query->orderBy('Pct');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage()  );
      return FALSE;
    }
    db_set_active('default');
    $pctOptions = array();
    do {
      $pct = $result->fetchAssoc();
      if(empty($pct)) {break;}
      $pctOptions[] = $pct['Pct'];
    } while (TRUE);
    return $pctOptions;
  }
  
  function getNlList($county,$pct) {
    // Get a list of the NLs in the selected precinct, order by name.
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLSGRPTBL, 'g');
      $query->join(self::NLSTBL, 'n', 'g.MCID = n.MCID');
      $query->addField('n', 'Nickname');
      $query->addField('n', 'LastName');
      $query->addField('n', 'Email');
      $query->addField('n', 'Phone');
      $query->addField('n', 'MCID');
      $query->condition('Pct',$pct);
      $query->condition('g.County',$county);
      $query->orderBy('LastName');
      $query->orderBy('Nickname');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage()  );
      return FALSE;
    }
    db_set_active('default');
    $dbList = array_flip($this->nlList);
    $nlOptions = array();
    do {
      $nl = $result->fetchAssoc();
      if(empty($nl)) {break;}
      $nlOptions[] = $nl['Nickname'].' '.$nl['LastName'].
          ': '.$nl['Email'].', MCID['.$nl['MCID'].']';
      
      foreach ($nl as $dbKey => $dbValue) {
        $nlNlp[$dbList[$dbKey]] = $dbValue;
      }
      $nlMcid[] = $nlNlp;
    } while (TRUE);
    return array('options'=>$nlOptions,'mcidArray'=>$nlMcid);
  } 
  
}
