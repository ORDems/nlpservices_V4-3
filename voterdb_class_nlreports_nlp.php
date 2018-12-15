<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpReports.
 */
/*
 * Name: voterdb_class_nlreports_nlp.php   V4.3  12/14/18
 */

namespace Drupal\voterdb;

require_once "voterdb_debug.php";


class NlpReports {
  
  const NLPRESULTSTBL = 'results'; 
  const BATCHLIMIT = 1000;
  
  const CONTACT = 'Contact';
  const SURVEY = 'Survey';
  const ID = 'ID';
  const COMMENT = 'Comment';
  
  const F2F = 'Face-to-Face';
  const DECEASED = 'Deceased';
  const HOSTILE = 'Hostile';
  const MOVED = 'Moved';
  
  const MAXCOMMENT = '190';
  
  const MULTIINSERT = 100;
  const BATCH = 100;
  
  private $records = array();
  private $sqlCnt = 0;
  private $batchCnt = 0;
  
  public $resultsArray = array(
    'Select Result',self::F2F, 'Left Lit', 'Post Card',
    'Phone Contact', 'Voice Mail', 'Disconnected', 'Not at this Number',
    self::DECEASED, self::HOSTILE, 'Inaccessible', self::MOVED, 'Refused Contact'
  );
  
  
  private $resultsList = array(
    'rindex'=>'Rindex',
    'recorded'=>'RecordedVan',
    'miniVanRecorded' => 'MiniVanRecorded',
    'cycle'=>'Cycle',
    'county'=>'County', 
    'active'=>'Active',
    'vanid'=>'VANID',
    'mcid'=>'MCID',
    'cdate'=>'Cdate',
    'type'=>'Type',
    'value'=>'Value',
    'text'=>'Text',
    'qid'=>'Qid',
    'rid'=>'Rid'
  );
  
  private $reportHdr = array(
    'recorded'=>'Recorded',
    'cycle'=>'Cycle',
    'county'=>'County',
    'active'=>'Active',
    'vanid'=>'VANID',
    'mcid'=>'MCID',
    'cdate'=>'Cdate', 
    'type'=>'Type', 
    'value'=>'Value', 
    'text'=>'Text');
  private $reportFields = array('RecordedVan','Cycle','County','Active',
    'VANID','MCID','Cdate','Type','Value','Text');
  

  public function getNlpReports($vanid) {
    //voterdb_debug_msg('vanid', $vanid);
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('VANID',$vanid);
      $query->condition('Active',TRUE);
      $query->condition('Type','Activist','<>');
      $query->orderBy('Cdate', 'DESC');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    db_set_active('default');
    $voterReports = $voterReport = array();
    do {
      $report = $result->fetchAssoc();
      if(!$report) {break;}
      foreach ($this->resultsList as $nlpKey=>$dbKey) {
        $voterReport[$nlpKey] = $report[$dbKey];
      }
      $voterReports[$report['VANID']][] = $voterReport;
    } while (TRUE);
    return $voterReports;
  }
  
  public function getNlpVoterReports($mcid,$cycle) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('MCID',$mcid);
      $query->condition('Active',TRUE);
      $query->condition('Type','Activist','<>');
      if(!empty($cycle)) {
        $query->condition('Cycle',$cycle);
      }
      $query->orderBy('Cdate', 'DESC');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    db_set_active('default');
    $voterReports = $voterReport = array();
    do {
      $report = $result->fetchAssoc();
      if(!$report) {break;}
      foreach ($this->resultsList as $nlpKey=>$dbKey) {
        $voterReport[$nlpKey] = $report[$dbKey];
      }
      $voterReports[$report['MCID']][] = $voterReport;
    } while (TRUE);
    return $voterReports;
  }
  
  public function getNlReportsForVoters($voterArray,$cycle){
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('VANID', $voterArray, 'IN');
      $query->condition('Active',TRUE);
      $query->condition('Cycle',$cycle);
      $query->condition('Type','Activist','<>');
      $query->orderBy('Cdate', 'DESC');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    db_set_active('default');

    $voterReports = $voterReport = array();
    do {
      $report = $result->fetchAssoc();
      if(!$report) {break;}
      foreach ($this->resultsList as $nlpKey=>$dbKey) {
        $voterReport[$nlpKey] = $report[$dbKey];
      }
      $voterReports[$report['MCID']][] = $voterReport;
    } while (TRUE);

    return $voterReports;
  }
  
  public function getNlpUnrecorded() {
    $cycle = variable_get('voterdb_ecycle', 'yyyy-mm-t');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('Active',TRUE);
      $query->condition('Cycle',$cycle);
      $query->isNull('Recorded');
      $query->range(0, 1000);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    db_set_active('default');
    $voterReports = array();
    do {
      $report = $result->fetchAssoc();
      if(!$report) {break;}
      $voterReports[$report['VANID']][] = $report;
    } while (TRUE);
    return $voterReports;
  }
  
  public function displayNlReports($voterReports) {
    $reportTypes = array('Contact','Comment', 'ID','Survey'); // Names of fields.
    $voterReportsDisplay = array();
    foreach ($reportTypes as $reportType) {
      $voterReportsDisplay['current'][$reportType] = ''; // Current cycle reports.
      $voterReportsDisplay['historic'][$reportType] = ''; // Historical cycle reports.
    }   
    $voterReportsDisplay['current']['Activist'] = '';
    if(empty($voterReports)) {return $voterReportsDisplay;}
    $currentCycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    // Build the strings for all the reports by type.
    $reportColor['current'] = 'blue';  //  Current cycle is blue.
    $reportColor['historic'] = 'grey';  // History is grey.
    foreach ($voterReports as $reports) {
      foreach ($reports as $report) {
        $reportType = $report['type'];
        $cycleType = ($report['cycle'] == $currentCycle) ? 'current':'historic';   
        switch ($reportType) {
          case 'ID':
            // For the ID, add the candidate name.
            $reportDisplay = $report['text'].' ['.$report['value'].']';
            break;
          case 'Contact':
            $reportDisplay = $report['value'];
            break;
          case 'Comment':
            $reportDisplay = $report['text'];
            break;
          case 'Survey':
            $reportDisplay = $report['text'].': '.$report['value'];
            break;
          case 'Activist':
            $activistCodeName = $report['text'];
            $activistCodeValue = $report['value'];
            $voterReportsDisplay['current']['Activist'][$activistCodeName]['value'] = $activistCodeValue;
            $reportDisplay = NULL;
            break;
        }
        
        if(!empty($reportDisplay)) {  
          if (!empty($voterReportsDisplay[$cycleType][$reportType])) {
            $voterReportsDisplay[$cycleType][$reportType] .= '<br/>';
          }
          $newReport = $report['cdate'].': '.$reportDisplay;
          $voterReportsDisplay[$cycleType][$reportType] .= '<span style="color:'.$reportColor[$cycleType].';">'.$newReport.'</span>';
        }
      } 
    }
    foreach ($reportTypes as $reportType) {
      if (!empty($voterReportsDisplay['historic'][$reportType])) {
        $voterReportsDisplay['historic'][$reportType] = '<br/>'.$voterReportsDisplay['historic'][$reportType];
      }
    }  
    return $voterReportsDisplay;
  }
  
   public function displayNewNlReport($nlReportDisplay,$value,$date) {    
     if (!empty($nlReportDisplay)) {
       $nlReportDisplay .= '<br/>';
     }
     $newReport = $date.': '.$value;
     $nlReportDisplay .= '<span style="color:red;">'.$newReport.'</span>';
     return $nlReportDisplay;
   }
  
  
  public function setNlpReportRecorded($rIndex) {
    $date = date('Y-m-d',time()); 
    db_set_active('nlp_voterdb');
    try {
      db_update(self::NLPRESULTSTBL)
        ->fields(array(
          'Recorded' => $date,))
        ->condition('Rindex',$rIndex)
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
  }
  
  public function setNlReport($canvassResult) {
    // Insert the reported information into the results table.
    $recorded = NULL;
    if(!empty($canvassResult['recorded'])) {
      $recorded = $canvassResult['recorded'];
    }
    $minivanRecorded = NULL;
    if(!empty($canvassResult['miniVanRecorded'])) {
      $minivanRecorded = $canvassResult['miniVanRecorded'];
    }
    if(!empty($canvassResult['source'])) {
      $source = $canvassResult['source'];
    } else {
      $source = 'nlp';
    }
    db_set_active('nlp_voterdb');
    db_insert(self::NLPRESULTSTBL)
      ->fields(array(
        'Cycle' => $canvassResult['cycle'],
        'County' => $canvassResult['county'],
        'RecordedVan' => $recorded,
        'MiniVanRecorded' => $minivanRecorded,
        'Active' => TRUE,
        'MCID' => $canvassResult['mcid'],
        'VANID' => $canvassResult['vanid'],
        'Cdate' => $canvassResult['date'],
        'Type' => $canvassResult['type'],
        'Value' => $canvassResult['value'],
        'Text' => $canvassResult['text'],
        'Qid' => $canvassResult['qid'],
        'Rid' => $canvassResult['rid'],
        'Source' => $source,
      ))
      ->execute();
    db_set_active('default');
  }
  
  public function getNlpAcReport($vanId) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('VANID',$vanId);
      $query->condition('Active',TRUE);
      $query->condition('Type','Activist');
      $query->orderBy('Cdate', 'DESC');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    db_set_active('default');
    $report = $result->fetchAssoc();
    if(!$report) {return NULL;}
    $acName = $report['Text'];
    $voterReport[$acName] = $report;
    return $voterReport;
  }
  
  public function getAcRindex($mcid,$activistCodeName) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->addField('v','Rindex');
      $query->condition('MCID',$mcid);
      $query->condition('Active',TRUE);
      $query->condition('Type','Activist');
      $query->condition('Text',$activistCodeName);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return '';
    }
    db_set_active('default');
    $report = $result->fetchAssoc();
    if(!$report) {return NULL;}
    $rindex= $report['Rindex'];
    return $rindex;
  }
  
  public function setNlAcReport($canvassResult) {
    //voterdb_debug_msg('canvass report', $canvassResult);
    $rindex = $canvassResult['rindex'];
    if($rindex != 0) {
      try {
        db_set_active('nlp_voterdb');
        db_merge(self::NLPRESULTSTBL)
          ->key(array('Rindex' => $rindex))
          ->fields(array('Active' => 0,))
          ->execute();
        db_set_active('default');
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage());
        return FALSE;
      }
    }
    $this->setNlReport($canvassResult);
  }
  
  function surveyResponse($vanid) {
    $cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->fields('r');
      $query->condition('Cycle',$cycle);
      $query->condition('VANID',$vanid);
      $query->condition('Type',self::SURVEY);
      $query->range(0,1);
      $result = $query->execute();
      //$contactCount = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $report = $result->fetchAssoc();
    if(empty($report)) {return FALSE;}
    //$voterContacted = $contactCount>0;
    //return $voterContacted;
    return TRUE;
  }
  
  function voterContactAttempted($vanid) {
    $cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->fields('r');
      $query->condition('Cycle',$cycle);
      $query->condition('VANID',$vanid);
      $query->condition(db_or()
        ->condition ('r.Type', self::SURVEY )
        ->condition ('r.Type', self::CONTACT ) 
      );
      $query->range(0,1);
      //$contactCount = $query->countQuery()->execute()->fetchField();
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $report = $result->fetchAssoc();
    if(empty($report)) {return FALSE;}
    //$voterContactAttempt = $contactCount>0;
    //return $voterContactAttempt;
    return TRUE;
  }
  
  function contactAttempt($vanid) {
    $cycle = variable_get('nlp_ecycle', 'xxxx-mm-G');
    $canvassResult['attempt'] = $canvassResult['survey'] = FALSE;
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->fields('r');
      $query->condition('Cycle',$cycle);
      $query->condition('VANID',$vanid);
      $query->condition(db_or()
        ->condition ('r.Type', self::SURVEY )
        ->condition ('r.Type', self::CONTACT ) 
      );
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      nlp_debug_msg('e', $e->getMessage() );
      return $canvassResult;
    }
    db_set_active('default');
    
    do {
      $report = $result->fetchAssoc();
      if(empty($report)) {break;}
      $canvassResult['attempt'] = TRUE;
      if($report['Type'] == 'Survey') {
        $canvassResult['survey'] = TRUE;
      }
    } while (TRUE);
  }
  
  function countyContacted($county) {
    $cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->addField('r','VANID');
      $query->distinct();
      $query->condition('Cycle',$cycle);
      $query->condition('County',$county);
      $query->condition('Type',self::SURVEY);
      $contactedCount = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');
    return $contactedCount;
  }
  
  public function getCountyReportCounts($county) {
    $cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    db_set_active('nlp_voterdb');
    try {
      $rquery = db_select(self::NLPRESULTSTBL, 'r');
      $rquery->fields('r');
      $rquery->condition('Active',TRUE);
      $rquery->condition('County',$county);
      $rquery->condition('Cycle',$cycle);
      $result = $rquery->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    $vstatus = array();
    do {
      $report = $result->fetchAssoc();
      if(!$report) {break;}
      $vanid = $report['VANID'];
      $vstatus[$vanid]['mcid'] = $report['MCID'];
      $vstatus[$vanid]['attempt'] = TRUE;
      if($report['Type']==self::SURVEY) {
        $vstatus[$vanid]['survey'] = TRUE;
      }
    } while (TRUE);
    $counts = array();
    foreach ($vstatus as $status) {
      $mcid = $status['mcid'];
      if(empty($counts[$mcid]['attempts'])) {
        $counts[$mcid]['attempts'] = 1;
        $counts[$mcid]['contacts'] = 0;
      } else {
        $counts[$mcid]['attempts']++;
      }
      if(!empty($status['survey'])) {
        $counts[$mcid]['contacts']++;
      }
    }
    return $counts;
  }
  
  public function getTotalContactAttempts() { 
    $cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->addField('r','VANID');
      $query->distinct();
      $query->condition('Cycle',$cycle);
    $br = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');
    return $br;
  }
  
  public function getNlVoterContactAttempts($mcid) {
    $cycle = variable_get('voterdb_ecycle', 'xxxx-mm-G');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->addField('r','VANID');
      $query->condition('MCID',$mcid);
      $query->condition('Cycle',$cycle);
      $query->distinct();
    $br = $query->countQuery()->execute()->fetchField();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return 0;
    }
    db_set_active('default');
    return $br;
  }
  
  public function getSurveyResponseCounts($qid) {
    $counts = array();
    $cycle = variable_get('voterdb_ecycle', 'yyyy-mm-t');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->addField('r','VANID');
      $query->condition('Active',TRUE);
      $query->condition('Cycle',$cycle);
      $query->condition('Type',self::SURVEY);
      $query->condition('Qid',$qid);
      $query->distinct();
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    do {
    $voter = $result->fetchAssoc();
    if(empty($voter)) {break;}
      $vanid = $voter['VANID'];
      
      db_set_active('nlp_voterdb');
      try {
        $vtrQuery = db_select(self::NLPRESULTSTBL, 'r');
        $vtrQuery->addField('r','Rid');
        $vtrQuery->addField('r','Cdate');
        $vtrQuery->condition('Active',TRUE);
        $vtrQuery->condition('VANID',$vanid);
        $vtrQuery->condition('Cycle',$cycle);
        $vtrQuery->condition('Type',self::SURVEY);
        $vtrQuery->condition('Qid',$qid);
        $vtrResult = $vtrQuery->execute();
      }
      catch (Exception $e) {
        db_set_active('default');
        voterdb_debug_msg('e', $e->getMessage() );
        return 0;
      }
      db_set_active('default');
      $newest = '';
      $newestRid = 0;
      do {
      $responses = $vtrResult->fetchAssoc();
      if(empty($responses)) {break;}
        $rid = $responses['Rid'];
        $cdate = $responses['Cdate'];
        if($cdate>$newest) {
          $newestRid = $rid;
        }
      } while (TRUE);
      if($newestRid!=0 AND !isset($counts[$newestRid])) {
        $counts[$newestRid] = 1;
      } else {
        $counts[$newestRid]++;
      }
    } while (TRUE);
    return $counts;
  }
  
  public function getSurveyResponsesCountsById ($mcid,$qid) {
    $counts = array();
    $cycle = variable_get('voterdb_ecycle', 'yyyy-mm-t');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      //$query->addField('r','VANID');
      //$query->addField('r','Rid');
      //$query->addField('r','Cdate');
      $query->fields('r');
      //$query->condition('Active',TRUE);
      $query->condition('Cycle',$cycle);
      //$query->condition('Type',self::SURVEY);
      $query->condition('Qid',$qid);
      $query->condition('MCID',$mcid);
      //$query->distinct();
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    
    $vanIds = array();
    do {
      $voter = $result->fetchAssoc();
      //voterdb_debug_msg('voter', $voter);
      if(empty($voter)) {break;}
      $vanid = $voter['VANID'];
      if(empty($vanIds[$vanid])) {
        $vanIds[$vanid] = array('vanid'=>$vanid,'cdate'=>$voter['Cdate'],'rid'=>$voter['Rid']);
      } else {
        if($voter['Cdate']>$vanIds[$vanid]['cdate']) {
          $vanIds[$vanid] = array('vanid'=>$vanid,'cdate'=>$voter['Cdate'],'rid'=>$voter['Rid']);
        }
      }
    } while (TRUE);
    //voterdb_debug_msg('vanIds', $vanIds);
    foreach ($vanIds as $response) {
      $rid = $response['rid'];
      if(!isset($counts[$rid])) {
        $counts[$rid] = 1;
      } else {
        $counts[$rid]++;
      }
    }
    //voterdb_debug_msg('counts', $counts);
    return $counts;
  }
  
  public function getColumnNames() {
    db_set_active('nlp_voterdb');
    try {
      $tselect = "SHOW COLUMNS FROM  {".self::NLPRESULTSTBL.'}';
      $result = db_query($tselect);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return NULL;
    }
    db_set_active('default');
    $colnames = array();
    do {
      $name = $result->fetchAssoc();
      if(empty($name)) {break;}
      $colnames[] = $name['Field'];
    } while (TRUE);
    return $colnames;
  }
  
  public function getReportCount() {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $numRows = $query->countQuery()->execute()->fetchField();
      }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return;
      }
    db_set_active('default');
    return $numRows;
  }
  
  public function selectAllReports ($nextRecord) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->fields('r');
      $query->range($nextRecord, self::BATCHLIMIT);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      watchdog('voterdb_export_restore_batch', 'select error record: @rec', 
            array('@rec' =>  print_r($e->getMessage(), true)),WATCHDOG_DEBUG);
      return NULL;
    }
    db_set_active('default');
    return $result;
    } 
    
    public function selectNlReports ($nlsObj,$county) {
    $cycle = variable_get('nlp_ecycle', 'xxxx-mm-G');
    $nlsTbl = $nlsObj::NLSTBL;
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'r');
      $query->join($nlsTbl, 'n', 'r.MCID = n.MCID' );
      $query->fields('r');
      $query->addField('n', $nlsObj->nlList['nickname']);
      $query->addField('n', $nlsObj->nlList['lastName']);
      if(!empty($county)) {
        $query->condition('r.'.'County',$county);
        $query->condition('r.'.'Cycle',$cycle);
      }
      $query->orderBy('r.'.'County');
      $query->orderBy('r.'.'Cycle');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      nlp_debug_msg('e', $e->getMessage() );
      return FALSE;
    }
    db_set_active('default');
    return $result;
  }
  
  public function decodeReportsHdr($fileHdr) {
    //voterdb_debug_msg('header', $fileHdr);
    //$state = variable_get('voterdb_state', 'Select');
    $hdrErr = array();
    $hdrPos = array();
    foreach ($this->reportHdr as $nlpKey => $importField) {
      $found = FALSE;
      foreach ($fileHdr as $fileCol=>$fileColName) {
        if(trim($fileColName) == $importField) {
          $hdrPos[$nlpKey] = $fileCol;
          $found = TRUE;
          break;
        }
      }
      if(!$found) {
        $hdrErr[] = 'The import column "'.$vanField['err'].'" is missing.';
      }
    }
    $fieldPos['pos'] = $hdrPos;
    $fieldPos['err'] = $hdrErr;
    $fieldPos['ok'] = empty($hdrErr);
    //voterdb_debug_msg('fieldpos', $fieldPos);
    return $fieldPos;
  }
  
  public function insertNlReports($report) {
    $record = array();
    foreach ($this->reportHdr as $nlpKey => $dbKey) {
      $record[$dbKey] = $report[$nlpKey];   
    }
    $batchSubmit = FALSE;
    $this->records[$this->sqlCnt++] = $record;
    //voterdb_debug_msg('records', $this->records);
    // When we reach 100 records, insert all of them in one query.
    if ($this->sqlCnt == self::MULTIINSERT) {
      $this->sqlCnt = 0;
      $this->batchCnt++;
      db_set_active('nlp_voterdb');
      $query = db_insert(self::NLPRESULTSTBL)
        ->fields($this->reportFields);
      foreach ($this->records as $record) {
        $query->values($record);
      }
      $query->execute();
      db_set_active('default');
      $this->records = array();
      if($this->batchCnt==self::BATCH) {
        $this->batchCnt=0;
        $batchSubmit = TRUE;
      }
    }
    return $batchSubmit;
  }
  
  public function flushNlReports() {
    if(empty($this->records)) {return;}
    db_set_active('nlp_voterdb');
    $query = db_insert(self::NLPRESULTSTBL)
      ->fields($this->reportFields);
    foreach ($this->records as $record) {
      $query->values($record);
    }
    $query->execute();
    db_set_active('default');
    $this->records = array();
  }
  
  public function emptyNlTable() {
    db_set_active('nlp_voterdb');
    db_truncate(self::NLPRESULTSTBL)->execute();
    db_set_active('default');
  }

}
