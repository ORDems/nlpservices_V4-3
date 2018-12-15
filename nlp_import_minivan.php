<?php
/*
 * Name: nlp_import_minivan.php     V4.3  12/9/18
 *
 */

require_once "voterdb_debug.php";
require_once "voterdb_class_minivan.php";


use Drupal\voterdb\NlpMinivan;


function nlp_minivan_header_validate($fileType,$fileName) {

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




function nlp_minivan_form_submit($form, &$form_state) {
  //voterdb_debug_msg("MiniVAN report update started",'status' );
  $hostname = '{imap.gmail.com:993/imap/ssl}INBOX';
  $username = 'oregonnlp@gmail.com';
  $password = 'chinook25';
  $connection = imap_open($hostname,$username,$password);
  if(empty($connection)) {
    $error = imap_last_error();
    voterdb_debug_msg('imaperror',$error);
    return;
  }

  
  $emails = imap_search($connection,'ALL');
  if($emails) {
    //rsort($emails);
    foreach($emails as $email_number) {
      $overview = imap_fetch_overview($connection,$email_number,0);
      if($overview[0]->seen) {continue;}

      $subject = $overview[0]->subject;
      
      $fileType = 'canvass';
      $miniVan = strstr($subject, 'Neighborhood Leader Program'); 
      if(strstr($subject, '- AC')) {
        $fileType = 'activist';
      } elseif (strstr($subject, '- Responses')) {
        $fileType = 'survey';
      }
      
      //voterdb_debug_msg('processing email type: '.$fileType, $overview[0]->subject);
      
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
            $fieldPos = nlp_minivan_header_validate($fileType,$fileUri);
            //voterdb_debug_msg('fieldpos', $fieldPos);
            if(!$fieldPos) {
              return; 
            }
            
            $modulePath = drupal_get_path('module','voterdb');
            // Setup the call to start a batch operation.
            $args = array (
              'uri' => $fileUri,
              'field_pos' => $fieldPos,
              'file_type' => $fileType,
              //'date_indexes' => $form_state['nlp']['dates'],
            );
            $batch = array(
              'operations' => array(
                array('nlp_import_minivan_upload', array($args))
                ),
              'file' => $modulePath.'/nlp_import_minivan_upload.php',
              'finished' => 'nlp_import_minivan_finished',
              'title' => t('Processing import_minivan upload.'), 
              'init_message' => t('MiniVAN import is starting.'), 
              'progress_message' => t('Processed @percentage % of minivan reports file.'), 
              'error_message' => t('import_minivan has encountered an error.'),
            );
            //voterdb_debug_msg('batch', $batch);
            batch_set($batch);
          }
        }
      }
    }
  } 

  imap_close($connection);
  //drupal_set_message("MiniVAN update complete",'status' ); 
  return;
}

function nlp_minivan_form($form, &$form_state) {
  $form['submit'] = array(
      '#type' => 'submit',
      '#value' => 'Process the email inbox for new minivan reports >>',
  );
  return $form;
}

function nlp_minivan() {
  return drupal_get_form('nlp_minivan_form');
}

