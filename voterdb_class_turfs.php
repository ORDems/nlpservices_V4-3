<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpTurfs.
 */
/*
 * Name: voterdb_class_turfs.php   V4.2  6/16/18
 */

namespace Drupal\voterdb;

class NlpTurfs {
  
  const TURFTBL = 'turf'; 
  const NLSTBL = 'nls';
  const NLSSTATUSTBL = 'nls_status';
  
  const MAILLIMIT = '1000';
  
  private $fileTypes = array('PDF'=>'TurfPDF','MAIL'=>'TurfMail','CALL'=>'TurfCall');
  
  private $turfList = array(
    'turfIndex' => 'TurfIndex',
    'county' => 'County',
    'mcid' => 'MCID',
    'firstName' => 'NLfname',
    'lastName' => 'NLlname' ,
    'turfName' => 'TurfName',
    'pdf' => 'TurfPDF' ,
    'hd' =>  'TurfHD',
    'pct' =>  'TurfPCT',
    'reminderNeeded' => 'ReminderNeeded',
    'delivered' => 'Delivered',
    'commitDate' => 'CommitDate',
    'electionName' => 'ElectionName',
    'lastAccess' => 'LastAccess',
    'turfMail' => 'TurfMail',
    'turfCall' => 'TurfCall',
  );
  
  private function unlinkFile($fileName,$path) {
    if($fileName != '') {
      $fullName = $path . $fileName;
      if(file_exists($fullName)) {
        drupal_unlink($fullName);}
    }
  }

  public function createTurf($turf) {
    $fields = array(
      'ReminderNeeded' => 'Y',
      'CommitDate' => date('Y-m-d',time()),
      'ElectionName' => variable_get('voterdb_cycle_name', 'November 6, 2018'),
    );
    foreach ($turf as $nlpKey => $nlpValue) {
      $fields[$this->turfList[$nlpKey]] = $nlpValue;
    }
    db_set_active('nlp_voterdb');
    try {
      $turfIndex = db_insert(self::TURFTBL)
        ->fields($fields)
        ->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage()  );
        return FALSE;
      }
    db_set_active('default');
    return $turfIndex;
  }
  
  public function getTurf($turfIndex) {
    db_set_active('nlp_voterdb');
    $gt_tselect = "SELECT * FROM {".self::TURFTBL."} WHERE TurfIndex = :index ";
    $gt_targs = array(':index' => $turfIndex);
    $gt_result = db_query($gt_tselect,$gt_targs);
    $gt_turf = $gt_result->fetchAssoc();
    db_set_active('default');
    return $gt_turf;
  }

  public function removeTurf($turf) {
    //voterdb_debug_msg('turf', $turf);
    $turfIndex = $turf['turfIndex'];
    $county = $turf['county'];
    // Get the filenames the PDF walksheet, mail list, and call list.
    db_set_active('nlp_voterdb');
    try {
      $select = "SELECT * FROM {".self::TURFTBL."} WHERE  ".
        "TurfIndex = :index ";
      $args = array(
        ':index' => $turfIndex);
      $result = db_query($select,$args);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    $fileNames = $result->fetchAssoc();
    //voterdb_debug_msg('file names', $fileNames);
    db_set_active('default');
    // Delete the PDF, mail list and call list files.
    $pathObj = $turf['pathObj'];
    foreach ($this->fileTypes as $fileType=>$fieldName) {
      $fileName = $fileNames[$fieldName];
      $path = $pathObj->getPath($fileType,$county);
      $this->unlinkFile($fileName,$path);
    }
    // Delete the turf info in the turf table.
    db_set_active('nlp_voterdb');
    try {
      db_delete(self::TURFTBL)
        ->condition('TurfIndex', $turfIndex)
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


  public function turfExists($mcid,$county) {
    // Get the list of turfs assigned to this NL.
    db_set_active('nlp_voterdb');
    $select = "SELECT * FROM {".self::TURFTBL."} WHERE ".
      "County = :county AND MCID = :mcid";
    $args = array(
      ':county' => $county,
      ':mcid' => $mcid);
    $result = db_query($select,$args); 
    db_set_active('default');
    $turfs = array();
    do {
      $turf = $result->fetchAssoc();
      if (empty($turf)) {break;}
        $turfIndex = $turf['TurfIndex'];
        $turfs[$turfIndex] = $turf;
    } while (TRUE);
    if(empty($turfs)) {return $turfs;}
    $turfArray['turfs'] = $turfs;
    $turfArray['turfCnt'] = count($turfs);
    $turfArray['turfIndex'] = key($turfs);
    return $turfArray;
  }
  
  public function getTurfs($turfReq) {
    //voterdb_debug_msg('turf list called', $turfReq );
    // Order the list of turfs.
    $county = $turfReq['county'];
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::TURFTBL,'t');
      $query->join(self::NLSTBL,'n','n.MCID = t.MCID');
      $query->fields('t');
      $query->addField('n', 'HD');
      $query->addField('n', 'Pct');
      $query->addField('n', 'Nickname');
      $query->addField('n', 'LastName');
      $query->condition('t.County',$county);
      if(!empty($turfReq['pct'])) {
        $query->condition('n.Pct',$turfReq['pct']);
      }
      $query->orderBy('LastName');
      $query->orderBy('Nickname');
      $query->orderBy('TurfName');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $turfArray = array();
    do {
      $turf = $result->fetchAssoc();
      if (empty($turf)) {break;}
        $turfIndex = $turf['TurfIndex'];
        $turfArray[$turfIndex] = $turf;
    } while (TRUE);
    return $turfArray;
  }
  
  public function getCountyTurfs($county) {
    db_set_active('nlp_voterdb');
    try {
      $select = "SELECT * FROM {".self::TURFTBL."} WHERE  ".
        "County = :cnty ";
      $args = array(
        ':cnty' => $county,);
      $result = db_query($select,$args);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    $turfListFlip = array_flip($this->turfList);
    $turfArray = array();
    do {
      $turf = $result->fetchAssoc();
      if (empty($turf)) {break;}
        $turfIndex = $turf['TurfIndex'];
        foreach ($turf as $dbKey => $turfValue) {
          $turfRecord[$turfListFlip[$dbKey]] = $turfValue;
        }
        $turfArray[$turfIndex] = $turfRecord;
    } while (TRUE);

    return $turfArray;
  }
  
  public function getCountyNlsWithTurfs($county) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::TURFTBL, 't'); 
      $query->addField('t', 'MCID');
      $query->distinct();
      $query->condition('County',$county);
      $result = $query->execute();
    }
    catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return NULL;
    }
    db_set_active('default');
    $nlList = array();
    do {
      $turf = $result->fetchAssoc();
      if (!$turf) {break;}
      $nlList[] = $turf['MCID'];
    } while (TRUE);
    return $nlList;
  }
  
  public function createTurfDisplay($turfArray){
    $turfDisplay = array();
    foreach ($turfArray as $turfIndex=> $turf) {
      $turfDisplay[$turfIndex] = $turf['NLfname'].' '.
          $turf['NLlname'].': '.$turf['TurfName'].', pct-'.$turf['Pct'];
    }
  return $turfDisplay;
  }
  
  public function createTurfNames($turfArray){
    $turfDisplay = array();
    foreach ($turfArray as $turfIndex=> $turf) {
      $turfDisplay[$turfIndex] = $turf['TurfName'];
    }
  return $turfDisplay;
  }
  
  
  public function setTurfDelivered($turfIndex) {
    $isoDate = explode('T', date('c'));  // date/time in ISO format.
    db_set_active('nlp_voterdb');
    try {
      db_merge(self::TURFTBL)
        ->key(array('TurfIndex' => $turfIndex,))
        ->fields(array('Delivered' => $isoDate[0],))
        ->execute();
    } catch (Exception $e) {
      db_set_active('default');
      watchdog('voterdb_class_turfs', 'set turf delivered failed');
      voterdb_debug_msg('e', $e->getMessage() );
      return;
    }
    db_set_active('default');
  }
  
  public function setAllTurfsDelivered($mcid,$county) {
    db_set_active('nlp_voterdb');
    try {
      $select = "SELECT * FROM {".self::TURFTBL."} WHERE  ".
        "County = :county AND MCID = :mcid";
      $args = array(
        ':county' => $county,
        ':mcid' => $mcid);
      $result = db_query($select,$args);
    } catch (Exception $e) {
      db_set_active('default');
      //watchdog('voterdb_class_turfs', 'set all turfs delivered failed');
      voterdb_debug_msg('e', $e->getMessage() );
      return;
    }
    db_set_active('default');
    do {
      $turf = $result->fetchAssoc();
      if(empty($turf)) {break;}
      $this->setTurfDelivered($turf['TurfIndex']);
    } while (TRUE);
  }
  
  
  public function updateTurfFiles($type,$fileName,$turfIndex) {
    $field = array();
    
    switch ($type) {
      case 'mail':
        $file = 'TurfMail';
        break;
      case 'call':
        $file = 'TurfCall';
        break;
      default:
        return;
    }

    db_set_active('nlp_voterdb');
    db_update(self::TURFTBL)
      ->fields(array(
          $file => $fileName,
      ))
      ->condition('TurfIndex',$turfIndex)
      ->execute();
    db_set_active('default');
    return TRUE;
  }
  
  public function setLastTurfAccess($turfIndex,$date) {
    if(empty($date)) {
      $date = date('Y-m-d');
    }
    db_set_active('nlp_voterdb');
    try {
      db_merge(self::TURFTBL)
        ->key(array('TurfIndex' => $turfIndex,))
        ->fields(array('LastAccess' => $date,))
        ->execute(); 
      }
    catch (Exception $e) {
      db_set_active('default');
      watchdog('voterdb_co_notify', 'coordinator table query failed');
      //voterdb_debug_msg('e', $e->getMessage()  );
      return;
    }
    db_set_active('default');
  }
  
  public function getTurfHD($county) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::TURFTBL, 't');
      $query->join(self::NLSTBL, 'n', 'n.MCID = t.MCID' );
      $query->addField('n', 'HD');
      $query->condition('t.County',$county);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $uhd = array();
    do {
      $hdRec = $result->fetchAssoc();
      if(empty($hdRec)) {break;}
      $hd = $hdRec['HD'];
      $uhd[$hd] = $hd;
    } while (TRUE);
    if(empty($uhd)) {return NULL;}
  
    ksort($uhd);
    $hdOptions = array_values($uhd);
    return $hdOptions;
  }
  
  function getTurfPct($county,$hd) {
    // Get the list of precinct numbers with at least one NL with a turf in 
    // this HD, order numberically by precinct number.
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::TURFTBL, 't');
      $query->join(self::NLSTBL, 'n', 'n.MCID = t.MCID' );
      $query->addField('n', 'Pct');
      $query->condition('t.County',$county);
      $query->condition('n.HD',$hd);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }

    db_set_active('default');
    // Return if there are no precincts with a turf.
    $uPct = array();
    do {
      $pctRec = $result->fetchAssoc();
      if(empty($pctRec)) {break;}
      $pct = $pctRec['Pct'];
      $uPct[$pct] = $pct;
    } while (TRUE);
    if(empty($uPct)) {return NULL;}
    ksort($uPct);
    $pctOptions = array_values($uPct);
    return $pctOptions;
  }
  
  public function getTardyLogins($date) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::TURFTBL, 't');
      $query->join(self::NLSSTATUSTBL, 's', 't.MCID = s.MCID' );
      $query->fields('t');
      $query->isNull('LastAccess');
      $query->isNotNull('Delivered');
      $query->condition('Delivered',$date,'<');
      $query->isNull('Login_Date');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      watchdog('voterdb_login-chk', 'turf query failed');
      return NULL;
    }
    
    $turfs = array();
    do {
      $turf = $result->fetchAssoc();
      if(empty($turf)) {break;}
      $turfs[$turf['TurfIndex']] = $turf;
    } while (TRUE);
    
    db_set_active('default');
    return $turfs;
  }
  
  public function getTurfReminders() {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::TURFTBL, 't');
      $query->join(self::NLSTBL, 'n', 't.MCID = n.MCID' );
      $query->addField('t', 'MCID');
      $query->addField('t', 'County');
      $query->addField('t', 'TurfIndex');
      $query->addField('n', 'Nickname');
      $query->addField('n', 'Email');
      $query->isNull('ReminderNeeded');
      $query->isNotNull('Email');
      $query->range(0, self::MAILLIMIT);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      watchdog('voterdb_co_notify', 'Select of unreported turfs failed.');
      return NULL;
    }
    db_set_active('default');
    $turfs = array();
    do {
      $turf = $result->fetchAssoc();
      if(!$turf) {break;}
      $turfs[$turf['TurfIndex']] = $turf;
    } while (TRUE);
    return $turfs;
  }
  
  public function setTurfReminder($turfIndex,$value) {
    db_set_active('nlp_voterdb');
    db_merge(DB_NLSTURF_TBL)
      ->key(array('TurfIndex'=> $turfIndex))
      ->fields(array('ReminderNeeded' => $value))
      ->execute();
    db_set_active('default');
  }
  
}
