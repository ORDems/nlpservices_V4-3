<?php
/*
 * Name: voterdb_minivan.php     V4.3  10/8/18
 *
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

define('MAXQUEUELIMIT','10');

function voterdb_header_validate($fileType,$fileName) {

  $fileHandle = fopen($fileName, "r");
  if ($fileHandle == FALSE) {
    drupal_set_message("Failed to open miniVAN reports",'error');
    form_set_error('upload', 'Fix the problem before resubmit.');
    return FALSE;
  }
  // Get the header record.
  $headerRaw = fgets($fileHandle);
  if (!$headerRaw) {
    drupal_set_message('Failed to read miniVAN report file File Header', 'error');
    form_set_error('upload', 'Fix the problem before resubmit.');
    return FALSE;
  }
  $headerRecord = sanitize_string($headerRaw);
  // Extract the column headers.
  $columnHeader = explode(",", $headerRecord);

  $minivanObj = new NlpMinivan();
  //voterdb_debug_msg('nlObj', $nlObj);
  $fieldPos['ok'] = FALSE;
  $fieldPos['err'] = 'Bad file type';
  //$fileType = $fileType;
  switch ($fileType) {
    case 'survey':
      $fieldPos = $minivanObj->decodeMinivanSurveyHdr($columnHeader);
      break;
    case 'canvass':
      $fieldPos = $minivanObj->decodeMinivanCanvassHdr($columnHeader);
      break;
    case 'activist':
      $fieldPos = $minivanObj->decodeMinivanActivistHdr($columnHeader);
      break;
  }

  fclose($fileHandle);
  if(!$fieldPos['ok']) {
    foreach ($fieldPos['err'] as $errMsg) {
      drupal_set_message($errMsg,'warning');
    }
    return FALSE;
  }
  return $fieldPos['pos'];   
}


function voterdb_get_minivan_results($fileName, $fieldPos, $fileType, $activistCode) {
  $fileHandle = fopen($fileName, "r");
  if ($fileHandle == FALSE) {
    voterdb_debug_msg("Failed to open file",'');
    return NULL;
  }
  fgets($fileHandle);
  $minivanObj = new NlpMinivan();
  $reports = array();
  do {
    // Get the raw nl record in the upload file.
    $recordRaw = fgets($fileHandle);
    //voterdb_debug_msg('recordraw', $recordRaw);
    if (!$recordRaw) {break;}  // Break out of DO loop at end of file.
    // Remove any stuff that might be a security risk.
    $record = array();
    $fieldsRaw = explode(",", $recordRaw);
    foreach ($fieldsRaw as $fieldRaw) {
      $record[] = sanitize_string($fieldRaw);
    }
    $report = $minivanObj->extractMinivanFields($record,$fieldPos);
    //voterdb_debug_msg('report '.$fileType, $report);
    switch ($fileType) {
      case 'survey':
      case 'canvass':
        if(!empty($report['inputTypeId']) AND $report['inputTypeId'] == 14)  { // MiniVAN report.
          $reports[] = $report;
        }
        break;
      case 'activist':
        //voterdb_debug_msg('reportac: ['.$report['activistCodeId'].'] ac: ['.$activistCode.']', '');
        if($report['activistCodeId'] == $activistCode) {
          $reports[] = $report;
        }
        break;
    }
    
    
    //if(!empty($report['inputTypeId']) AND $report['inputTypeId'] == 14)  { // MiniVAN report.
    //  $reports[] = $report;
    //}
  } while (TRUE);  // Keep looping to read records until the break at EOF.
  db_set_active('default');
  fclose($fileHandle);
  return $reports;   
}


function voterdb_record_minivan_reports($reports) {
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
  
  
  //voterdb_debug_msg('reports', $reports);
  foreach ($reports as $report) {  
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
    $result['reported'] = date('Y-m-d',$cudate);
    
    if(!empty($report['dateCanvassed'])) {
      $idate = $report['dateCanvassed'];
      $udate = strtotime($idate);
      $result['date'] = date('Y-m-d',$udate);
    } else {
      $result['date'] = $result['reported'];
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
        if(!empty($questionArray) AND $qid != $questionArray['qid']) {
          $title = $questionArray['questionName'];
          $surveyResponseList = $questionArray['responses'];
        } else {
          $processReport = FALSE;
          break;
        }
        
        if(empty($questionArray)) {
          $processReport = FALSE;
          break;
        }
        
        if($qid == $questionArray['qid']) {
          $title = $questionArray['questionName'];
          $surveyResponseList = $questionArray['responses'];
        } elseif ($qid == $altSurveyQuestion['qid']) {
          $title = $questionArray['questionName'];
          $surveyResponseList = $questionArray['responses'];
          $qid = $questionArray['qid'];
          $rid = $altSurveyQuestion['rid'][$rid];
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
      //$nlpReportsObj->setNlReport($result);
      if($setStatus) {
        $voterStatus = $nlpVotersObj->getVoterStatus($vanid);
        //voterdb_debug_msg('voterstatus', $voterStatus);
        if(empty($voterStatus['dorCurrent'])) {
          $voter = $nlpVotersObj->getVoterById($vanid);
          $voterStatus['dorCurrent'] = $voter['dorCurrent'];
        }
        //$nlpVotersObj->updateVoterStatus($vanid, $voterStatus['dorCurrent'], $stickyStatus[$response], TRUE);
        voterdb_debug_msg('vanid: '.$vanid.' dorc: '.$voterStatus['dorCurrent'].' field: '.$stickyStatus[$response], '');
      }
      //voterdb_debug_msg('result', $result);
      $msg1 = $result['county'].', '.$result['firstName'].', '.$result['lastName'].', '.$result['mcid'];
      drupal_set_message($msg1,'status');
      $msg2 = $result['vanid'].', '.$result['type'].', '.$result['value'].', '.$result['text'].' - ['.
          '  '.$result['qid'].', '.$result['rid'].'] ';
      drupal_set_message($msg2,'status');
    }

  }
}


function voterdb_minivan() {
  $output = "MiniVAN report update started";
  $hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
  $username = 'oregonnlp@gmail.com';
  $password = 'chinook25';
  $connection = imap_open($hostname,$username,$password);
  if(empty($connection)) {
    $error = imap_last_error();
    voterdb_debug_msg('imaperror',$error);
    return array('#markup' => $output);
  }
  
  $activistCodesObj = new NlpActivistCodes();
  $activistCode = $activistCodesObj->getActivistCode('NotADem');
  //voterdb_debug_msg('not a dem', $activistCode);
  
  $emails = imap_search($connection,'ALL');
  if($emails) {
    $output = '';
    //rsort($emails);
    foreach($emails as $email_number) {
      $overview = imap_fetch_overview($connection,$email_number,0);
      if($overview[0]->seen) {continue;}
      //voterdb_debug_msg('overview', $overview);
      $subject = $overview[0]->subject;
      
      $fileType = 'canvass';
      $miniVan = strstr($subject, 'Neighborhood Leader Program'); 
      if(strstr($subject, '- AC')) {
        $fileType = 'activist';
      } elseif (strstr($subject, '- Responses')) {
        $fileType = 'survey';
      }
      
      voterdb_debug_msg('processing email type: '.$fileType, $overview[0]->subject);
      
      if($miniVan) {
        $structure = imap_fetchstructure($connection,$email_number);
        //voterdb_debug_msg('structure', $structure );
        $attachments = array();
        if(isset($structure->parts) && count($structure->parts)) {
          for($i = 0; $i < count($structure->parts); $i++) {
            $attachments[$i] = array(
              'is_attachment' => false,
              'filename' => '',
              'name' => '',
              'attachment' => '',
              'fileType' => $fileType
            );
            if($structure->parts[$i]->ifdparameters) {
              foreach($structure->parts[$i]->dparameters as $object) {
                if(strtolower($object->attribute) == 'filename') {
                  if(!empty($object->value)) {
                    $attachments[$i]['is_attachment'] = true;
                    $attachments[$i]['filename'] = $object->value;
                    $attachments[$i]['emailNumber'] = $email_number;
                  }
                }
              }
            }
            if($structure->parts[$i]->ifparameters) {
              foreach($structure->parts[$i]->parameters as $object) {
                if(strtolower($object->attribute) == 'name') {
                  if(!empty($object->value)) {
                    $attachments[$i]['is_attachment'] = true;
                    $attachments[$i]['name'] = $object->value;
                    $attachments[$i]['emailNumber'] = $email_number;
                  }
                }
              }
            }
            if($attachments[$i]['is_attachment']) {
              $attachments[$i]['attachment'] = imap_fetchbody($connection, $email_number, $i+1);
              //voterdb_debug_msg('fetchbody', $attachments[$i]['attachment'] );
              if($structure->parts[$i]->encoding == 3) { // 3 = BASE64
                $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
              }
              elseif($structure->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
              }
            }
          }
        }
        
        //voterdb_debug_msg('attachments', $attachments);
        
        foreach ($attachments as $attachment) {
          if($attachment['is_attachment']) {
            $fileName = $attachment['filename'];
            $tempDir = 'public://temp';
            $blob_uri = $tempDir.'/'.$fileName;
            $blob_object = file_save_data('', $blob_uri, FILE_EXISTS_REPLACE);
            $blob_object->status = 0;
            file_save($blob_object);
            $blob_fh = fopen($blob_uri,"w");
            fwrite($blob_fh,$attachment['attachment']);
            fclose($blob_fh); 
          }
        }
        
        foreach ($attachments as $attachment) {
          if($attachment['is_attachment']) {
            $fileName = $attachment['filename'];
            $fileType = $attachment['fileType'];
            $tempDir = 'public://temp';
            $fileUri = $tempDir.'/'.$fileName;
            $fieldPos = voterdb_header_validate($fileType,$fileUri);
            //voterdb_debug_msg('fieldpos', $fieldPos);
            if(!$fieldPos) {
              return array('#markup' => $output); 
            }
            $reports = voterdb_get_minivan_results($fileUri, $fieldPos, $fileType, $activistCode['activistCodeId']);
            //voterdb_debug_msg('reports', $reports);
            $queueItem = array();
            $queCount = 0;
            foreach ($reports as $report) {
              $report['fileType'] = $fileType;
              $queueItem[] = $report;
              $queCount++;
              if($queCount>=MAXQUEUELIMIT) {
                $queCount = 0;
                //voterdb_debug_msg('queue', $queueItem);
                voterdb_record_minivan_reports($queueItem);
                $queueItem = array();
              }
            }
            if($queCount>0) {
              //voterdb_debug_msg('queue', $queueItem);
              voterdb_record_minivan_reports($queueItem);
            }
            $emailNumber = $attachment['emailNumber'];

            //imap_delete($connection, $emailNumber);

          }
        }

      }
    }
  } 

  imap_close($connection);
  $output .= "MiniVAN update complete";
  return array('#markup' => $output);   

}