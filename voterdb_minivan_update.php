<?php
/*
 * Name: voterdb_formtest_minivan.php   V4.3 8/31/18
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


use Drupal\voterdb\NlpMinivan;
use Drupal\voterdb\NlpReports;
use Drupal\voterdb\NlpNls;
use Drupal\voterdb\NlpSurveyQuestion;
use Drupal\voterdb\NlpSurveyResponse;
use Drupal\voterdb\NlpResponseCodes;
use Drupal\voterdb\NlpActivistCodes;


function voterdb_get_minivan_results($fileName, $fieldPos) {
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
    if (!$recordRaw) {break;}  // Break out of DO loop at end of file.
    // Remove any stuff that might be a security risk.
    $record = array();
    $fieldsRaw = explode(",", $recordRaw);
    foreach ($fieldsRaw as $fieldRaw) {
      $record[] = sanitize_string($fieldRaw);
    }
    $report = $minivanObj->extractMinivanFields($record,$fieldPos);
    if($report['inputTypeId'] == 14)  { // MiniVAN report.
      $reports[] = $report;
    }
  } while (TRUE);  // Keep looping to read records until the break at EOF.
  db_set_active('default');
  fclose($fileHandle);
  return $reports;   
}

function voterdb_record_minivan_reports($reportType,$reports) {
  $nlpReportsObj = new NlpReports();
  $nlObj = new NlpNls();
  $result['county'] = NULL;
  $result['firstName'] = NULL;
  $result['lastName'] = NULL;
  $result['cycle'] = variable_get('voterdb_ecycle', 'xxxx-mm-G');
  
  foreach ($reports as $report) {
    
    $mcid = $report['mcid'];
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
    
    switch ($reportType) {
      case 'survey':
        $rid = $report['surveyResponseId'];
        
        
        $surveyResponseObj = new NlpSurveyResponse();
        $surveyQuestionObj = new NlpSurveyQuestion($surveyResponseObj);
        $questionArray = $surveyQuestionObj->getSurveyQuestion();
        if(!empty($questionArray)) {
          $title = $questionArray['questionName'];
          $surveyResponseList = $questionArray['responses'];
        } else {
          $surveyResponseList = NULL;
          $title = NULL;
        }
        
        $response = '';
        if(!empty($surveyResponseList)) {
          $response = $surveyResponseList[$rid];
          //$response = 'test';
        }

        $result['type'] = 'Survey';
        $result['value'] = $response;
        $result['text'] = $title;

        

        $result['qid'] = $report['surveyQuestionId'];
        $result['rid'] = $rid;
        break;
      case 'canvass':
        $rid = $report['resultid'];
        
        $canvassResponsesObj = new NlpResponseCodes();
        $canvassResponseList = $canvassResponsesObj->getNlpResponseCodesList();

        if(!empty($canvassResponseList)) {
          $response = $canvassResponseList[$rid];
          //$response = 'test';
        }

        $result['type'] = 'Contact';
        $result['value'] = $response;
        $result['text'] = '';

        $result['qid'] = NULL;
        $result['rid'] = $rid;
        break;
      case 'activist':
                $result['rindex'] = $nlpReportsObj->getAcRindex($mcid,'NotADem');
        
        $result['rid'] = $report['activistCodeId'];
        
        $activistCodesObj = new NlpActivistCodes();
        
        $activistCode = $activistCodesObj->getActivistCode('NotADem');

        $result['type'] = 'Activist';
        $result['value'] = $report['activistCodeActionId'];
        $result['text'] = 'NotADem';
        
        
        
        
        
        break;
    }
    
        

    $nlpReportsObj->setNlReport($result);
    voterdb_debug_msg('result', $result);

  }
}


function voterdb_minivan_update($form,&$form_state) {
  
  $types = array('survey'=>'survey','canvass'=>'canvass','activist'=>'activist');
  
  $form['filetype'] = array(
      '#type' => 'select',
      '#title' => t('Choose a file type'),
      '#options' => $types,
      '#description' => t('Pick a file exported from VAN to upload.')
  );

  $form['upload'] = array(
      '#type' => 'file',
      '#title' => t('Choose a file and then click Upload'),
      '#description' => t('Pick a file exported from VAN to upload.')
  );
  // Add a submit button.
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Upload',
  );

  return $form;
}

function voterdb_minivan_update_validate($form, &$form_state) {

  if (isset($_FILES['files']) && is_uploaded_file($_FILES['files']['tmp_name']['upload'])) {
    $tmpName = $_FILES['files']['tmp_name']['upload']; // system temp name
    $fileHandle = fopen($tmpName, "r");
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
    $fileType = $form_state['values']['filetype'];
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
    
    //voterdb_debug_msg('decoe', $fieldPos);
    if(!$fieldPos['ok']) {
      foreach ($fieldPos['err'] as $errMsg) {
        drupal_set_message($errMsg,'warning');
      }
      form_set_error('upload', 'Fix the problem before resubmit.');
      return FALSE;
    }

    fclose($fileHandle);
    // Set files to form_state, to process when form is submitted.
    $form_state['voterdb']['file'] = $tmpName;
    $form_state['voterdb']['pos'] = $fieldPos['pos'];
  } else {
    // Set error.
    drupal_set_message('The file was not uploaded.', 'error');
    form_set_error('upload', 'Error uploading file.');
    return FALSE;
  }
}

function voterdb_minivan_update_submit($form, &$form_state) {
  
  //voterdb_debug_msg('values',$form_state['values']);
  
  $reports = voterdb_get_minivan_results($form_state['voterdb']['file'], $form_state['voterdb']['pos']);
  voterdb_debug_msg('reports', $reports);
  
  $fileType = $form_state['values']['filetype'];
  
  voterdb_record_minivan_reports($fileType,$reports);
  
 
  
}