<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpReports.
 */

namespace Drupal\voterdb;

//require_once "voterdb_constants_rr_tbl.php";
require_once "voterdb_debug.php";


class NlpReports {
  
  const NLPRESULTSTBL = 'results'; 
  
  const CONTACT = 'Contact';
  const SURVEY = 'Survey';
  const ID = 'ID';
  const COMMENT = 'Comment';
  
  const F2F = 'Face-to-Face';
  const DECEASED = 'Deceased';
  const HOSTILE = 'Hostile';
  const MOVED = 'Moved';
  
  public $resultsArray = array(
    'Select Result',self::F2F, 'Left Lit', 'Post Card',
    'Phone Contact', 'Voice Mail', 'Disconnected', 'Not at this Number',
    self::DECEASED, self::HOSTILE, 'Inaccessible', self::MOVED, 'Refused Contact'
  );
  
  
  
  private $resultsList = array(
    'rindex'=>'Rindex',
    'recorded'=>'Recorded',
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

  public function getNlpUnrecorded() {
    $cycle = variable_get('voterdb_ecycle', 'yyyy-mm-t');
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('Active',TRUE);
      $query->condition('Cycle',$cycle);
      $query->isNull('Recorded');
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
    db_set_active('nlp_voterdb');
    db_insert(self::NLPRESULTSTBL)
      ->fields(array(
        'Cycle' => $canvassResult['cycle'],
        'County' => $canvassResult['county'],
        'Active' => TRUE,
        'MCID' => $canvassResult['mcid'],
        'VANID' => $canvassResult['vanid'],
        'Cdate' => $canvassResult['date'],
        'Type' => $canvassResult['type'],
        'Value' => $canvassResult['value'],
        'Text' => $canvassResult['text'],
        'Qid' => $canvassResult['qid'],
        'Rid' => $canvassResult['rid'],
      ))
      ->execute();
    db_set_active('default');
  }
  
  public function getNlpAcReport($vanId) {
    //$cycle = variable_get('voterdb_ecycle', 'yyyy-mm-t');

    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::NLPRESULTSTBL, 'v');
      $query->fields('v');
      $query->condition('VANID',$vanId);
      $query->condition('Active',TRUE);
      $query->condition('Type','Activist');
      //$query->condition('Cycle',$cycle);
      $query->orderBy(NC_CDATE, 'DESC');
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

}
