<?php
/*
 * Name: nlp_import_minivan_upload.php   V5.0   12/10/18
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_minivan.php";
require_once "voterdb_class_nlreports_nlp.php";
require_once "voterdb_class_nls.php";
require_once "voterdb_class_survey_question_nlp.php";
require_once "voterdb_class_survey_response_nlp.php";
require_once "voterdb_class_response_codes.php";
require_once "voterdb_class_activist_codes_nlp.php";
require_once "voterdb_class_voters.php";


use Drupal\voterdb\NlpMinivan;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpSurveyResponse;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\NlpActivistCodes;
use Drupal\voterdb\NlpVoters;

define('MAXQUEUELIMIT','200');


function nlp_record_minivan_reports($reports) {
  $nlpReportsObj = new NlpReports();
  $nlObj = new NlpNls();
  $result['county'] = NULL;
  $result['firstName'] = NULL;
  $result['lastName'] = NULL;
  $result['cycle'] = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  $result['source'] = 'MiniVAN';
  
  $nlpVotersObj = new NlpVoters();
  $activistCodesObj = new NlpActivistCodes();
  $stickyStatus = array('Moved'=>'moved','Deceased'=>'deceased','Hostile'=>'hostile');
  
  $altSurveyQuestion = array(
    'qid' => 305923,
    'rid' => array(
      '1266087' => 1218788,
      '1266088' => 1218789,
      '1266089' => 1218790,
    ),
  );
  
  $reportTime = 0;
  $transaction = db_transaction();
  
  //voterdb_debug_msg('reports', $reports);
  foreach ($reports as $report) {  
    //watchdog('nlp_import_minivan_upload', 'report @rec', array('@rec' =>  print_r($report, true)),WATCHDOG_DEBUG);
    //voterdb_debug_msg('report', $report);
    $vanid = $report['vanid'];
    $mcid = $nlpVotersObj->getNlId($vanid);
    if (!empty($mcid)) {
      $nl = $nlObj->getNlById($mcid);
      if(!empty($nl)) {
        $result['firstName'] = $nl['firstName'];
        $result['lastName'] = $nl['lastName'];
        $result['county'] = $nl['county'];
      }
    }
    $result['mcid'] = $mcid;

    $cdate = $report['dateCreated'];
    $cudate = strtotime($cdate);
    $result['recorded'] = date('Y-m-d',$cudate);
    $result['miniVanRecorded'] = date('Y-m-d',time());
    
    if(!empty($report['dateCanvassed'])) {
      $idate = $report['dateCanvassed'];
      $udate = strtotime($idate);
      $result['date'] = date('Y-m-d',$udate);
    } else {
      $result['date'] = $result['recorded'];
    }
    
    $result['vanid'] = $report['vanid'];
    
    $reportType = $report['fileType'];
    //voterdb_debug_msg('reporttype', $reportType);
    $processReport = TRUE;
    $setStatus = FALSE;
    switch ($reportType) {
      case 'survey':
        $qid = $report['surveyQuestionId'];
        $rid = $report['surveyResponseId'];
        $surveyResponseObj = new NlpSurveyResponse();
        $surveyQuestionObj = new NlpSurveyQuestion($surveyResponseObj);
        $questionArray = $surveyQuestionObj->getSurveyQuestion();

        if(empty($questionArray)) {
          $processReport = FALSE;
          break;
        }
        
        if($qid == $questionArray['qid']) {
          $title = $questionArray['questionName'];
          $surveyResponseList = $questionArray['responses'];
        } else {
          $processReport = FALSE;
          break;
        }
        
        $response = '';
        if(!empty($surveyResponseList)) {
          if(!empty($surveyResponseList[$rid])) {
            $response = $surveyResponseList[$rid];
          }
        }
        $result['type'] = 'Survey';
        $result['value'] = $response;
        $result['text'] = $title;
        $result['qid'] = $qid;
        $result['rid'] = $rid;
        break;
        
      case 'canvass':
        $rid = $report['resultid'];
        $canvassResponsesObj = new NlpResponseCodes();
        $canvassResponseList = $canvassResponsesObj->getNlpResponseCodesList();
        //voterdb_debug_msg('canvassresponselist', $canvassResponseList);
        
        if(empty($canvassResponseList) OR empty($canvassResponseList[$rid])) {
          $processReport = FALSE;
          break;
        }
        $response = $canvassResponseList[$rid];
        
        if(isset($stickyStatus[$response])) {
          $setStatus = TRUE;
        }
        
        
        $result['type'] = 'Contact';
        $result['value'] = $response;
        $result['text'] = '';
        $result['qid'] = NULL;
        $result['rid'] = $rid;
        break;
        
      case 'activist':
        //voterdb_debug_msg('activist', '');
        $rid = $report['activistCodeId'];
        $activistCode = $activistCodesObj->getActivistCode('NotADem');
        //voterdb_debug_msg('activistcode', $activistCode);
        //voterdb_debug_msg('activistcode:'.$activistCode.' rid: '.$rid, '');
        if($activistCode['activistCodeId'] != $rid) {
          $processReport = FALSE;
          break;
        }
        $result['rindex'] = $nlpReportsObj->getAcRindex($mcid,'NotADem');
        $result['rid'] = $rid;
        $result['qid'] = NULL;
        $result['type'] = 'Activist';
        $result['value'] = 0;
        $result['text'] = 'NotADem';
        break;
    }
    
    if($processReport) {
      $startTime = voterdb_timer('start',0);
      $nlpReportsObj->setNlReport($result);
      $elapsedTime = voterdb_timer('end',$startTime);
      $reportTime += $elapsedTime;
      if($setStatus) {
        $voterStatus = $nlpVotersObj->getVoterStatus($vanid);
        //voterdb_debug_msg('voterstatus', $voterStatus);
        if(empty($voterStatus['dorCurrent'])) {
          $voter = $nlpVotersObj->getVoterById($vanid);
          $voterStatus['dorCurrent'] = $voter['dorCurrent'];
        }
        $nlpVotersObj->updateVoterStatus($vanid, $voterStatus['dorCurrent'], $stickyStatus[$response], TRUE);
        //voterdb_debug_msg('vanid: '.$vanid.' dorc: '.$voterStatus['dorCurrent'].' field: '.$stickyStatus[$response], '');
        //watchdog('nlp_import_minivan_upload', 'vanid: '.$vanid.' dorc: '.$voterStatus['dorCurrent'].' field: '.$stickyStatus[$response]);
      }
      //voterdb_debug_msg('result', $result);
 
      //$msg1 = $result['county'].', '.$result['firstName'].', '.$result['lastName'].', '.$result['mcid'];
      //drupal_set_message($msg1,'status');
      //watchdog('nlp_import_minivan_upload', $msg1);
      //$msg2 = $result['vanid'].', '.$result['type'].', '.$result['value'].', '.$result['text'].' - ['.
      //    '  '.$result['qid'].', '.$result['rid'].'] ';
      //drupal_set_message($msg2,'status');
      //watchdog('nlp_import_minivan_upload', $msg2);
 
    }

  }
  return $reportTime;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * nlp_import_minivan_upload
 * 
 * Read the provided file and save the Dems.
 * 
 * @param type $arg
 * @param type $context
 * @return FALSE if error.
 */
function nlp_import_minivan_upload($arg,&$context) {
  
  //voterdb_debug_msg('arg', $arg);
  //voterdb_debug_msg('context', $context);
  //watchdog('nlp_import_minivan_upload', 'arg @rec', array('@rec' =>  print_r($arg, true)),WATCHDOG_DEBUG);
  //watchdog('nlp_import_minivan_upload', 'context @rec', array('@rec' =>  print_r($context, true)),WATCHDOG_DEBUG);
  
  $activistCodesObj = new NlpActivistCodes();
  $activistCode = $activistCodesObj->getActivistCode('NotADem');
  $activistCodeId = $activistCode['activistCodeId'];
  //watchdog('nlp_import_minivan_upload', 'activistcode: '.$activistCodeId);
  
  $uri = $arg['uri'];
  $column = $arg['field_pos'];
  $fileType = $arg['file_type'];
  
  $fh = fopen($uri, "r");
  if ($fh == FALSE) {
    watchdog('nlp_import_minivan_upload', 'Failed to open file');
    $context['finished'] = TRUE;
    return;
  }
  //fgets($fh);
  
  $filesize = filesize($uri);
  $context['finished'] = 0;
  // Position file at the start or where we left off for the previous batch.
  if(empty($context['sandbox']['seek'])) {
    // Read the header record.
    fgets($fh);
    $dcnt = 0;
    $context['sandbox']['upload-start'] = voterdb_timer('start',0);
    $context['sandbox']['loop-max'] = 0;
    $context['sandbox']['reportTime'] = 0;
  } else {
    // Seek to where we will restart.
    $seek = $context['sandbox']['seek'];
    fseek($fh, $seek);
    $dcnt = $context['sandbox']['dcnt'];
    
  }
  
  
  
  $minivanObj = new NlpMinivan();
  $reports = array();
  $recordCount = 0;
  do {
    // Get the raw nl record in the upload file.
    $fieldsRaw = fgetcsv($fh);
    
    //voterdb_debug_msg('recordraw', $recordRaw);
    //watchdog('nlp_import_minivan_upload', 'fieldsraw @rec', array('@rec' =>  print_r($fieldsRaw, true)),WATCHDOG_DEBUG);
    if (!$fieldsRaw) {break;}  // Break out of DO loop at end of file.
    // Remove any stuff that might be a security risk.
    $dcnt++;
    $record = array();
    //$fieldsRaw = explode(",", $recordRaw);
    foreach ($fieldsRaw as $fieldRaw) {
      $record[] = sanitize_string($fieldRaw);
    }
    //watchdog('nlp_import_minivan_upload', 'record @rec', array('@rec' =>  print_r($record, true)),WATCHDOG_DEBUG);
    $report = $minivanObj->extractMinivanFields($record,$column);
    $report['fileType'] = $fileType;
    //watchdog('nlp_import_minivan_upload', 'report @rec', array('@rec' =>  print_r($report, true)),WATCHDOG_DEBUG);
    //voterdb_debug_msg('report '.$fileType, $report);
    switch ($fileType) {
      case 'survey':
      case 'canvass':
        if(!empty($report['inputTypeId']) AND $report['inputTypeId'] == 14)  { // MiniVAN report.
          $reports[] = $report;
          $recordCount++;
        }
        break;
      case 'activist':
        //if($dcnt<6) {
        //  watchdog('nlp_import_minivan_upload', 'reportac: '.$report['activistCodeId']);
        //}
        //voterdb_debug_msg('reportac: ['.$report['activistCodeId'].'] ac: ['.$activistCode.']', '');
        if($report['activistCodeId'] == $activistCodeId) {
          $reports[] = $report;
          $recordCount++;
        }
        break;
    }
    
    if($recordCount == MAXQUEUELIMIT) {break;}
    
    //if(!empty($report['inputTypeId']) AND $report['inputTypeId'] == 14)  { // MiniVAN report.
    //  $reports[] = $report;
    //}
  } while (TRUE);  // Keep looping to read records until the break at EOF.
  
  
  $loop_start = voterdb_timer('start',0);
  $reportTime = nlp_record_minivan_reports($reports);
  $context['sandbox']['reportTime'] += $reportTime;
  $loop_time = voterdb_timer('end',$loop_start);
  if($loop_time > $context['sandbox']['loop-max']) {
    $context['sandbox']['loop-max'] = $loop_time;
  }
  
  //$done = FALSE;
  $seek = ftell($fh);
  $context['sandbox']['seek'] = $seek;
  $context['finished'] = $seek/$filesize;
  //voterdb_debug_msg('seek: '.$seek.' progress: '.$context['finished'], '');
  $context['sandbox']['dcnt'] = $dcnt;
  
  if($recordCount != MAXQUEUELIMIT) {
    $context['finished'] = 1;
    $context['results']['dcnt'] = $dcnt;
    $upload_time = voterdb_timer('end',$context['sandbox']['upload-start']);
    $context['results']['upload-time'] = $upload_time;
    $context['results']['loop-max'] = $context['sandbox']['loop-max'];
    $context['results']['uri'] = $uri;
    $context['results']['reportTime'] = $context['sandbox']['reportTime'];
  }
  
  return;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * nlp_import_minivan_finished
 * 
 * The batch operation is finished.  Report the results.
 * 
 * @param type $success
 * @param type $results
 * @param type $operations
 */
function nlp_import_minivan_finished($success, $results, $operations) {
  //$matchbackObj = new NlpMatchback();
  $uri = $results['uri'];
  drupal_unlink($uri);
  if ($success) {

    // Report results.
    $dcnt = $results['dcnt'];
    drupal_set_message(t('@count records processed.', 
      array('@count' => $dcnt)));
    $loop_max = round($results['loop-max'], 1);
    $upload_time = round($results['upload-time'], 1);
    drupal_set_message(t('Upload time: @upload, Max loop time: @loop.', 
      array('@upload' => $upload_time,'@loop'=>$loop_max)),'status');
    
    $reportTime = $results['reportTime'];
    drupal_set_message(t('Time for database inserts: @elapsed', 
      array('@elapsed' => $reportTime)));
    
    drupal_set_message('The miniVan reports successfully updated.','status');

  }
  else {
    drupal_set_message(t('Opps, an error occurred.'),'error');
  }
  //watchdog('import matchbacks', 'Import of matchbacks has finished');
}
