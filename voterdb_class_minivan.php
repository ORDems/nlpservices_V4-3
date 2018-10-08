<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpMinivan.
 */
/*
 * Name: voterdb_class_minivan.php   V4.3  8/31/18
 */

namespace Drupal\voterdb;

class NlpMinivan {

  private $minivanSurveyHdr = array(
    'vanid' => array('name'=>'vanid','err'=>'vanid'),
    'dateCanvassed' => array('name'=>'datecanvassed','err'=>'datecanvassed'),
    'dateCreated' => array('name'=>'datecreated','err'=>'datecreated'),
    'inputTypeId' => array('name'=>'inputtypeid','err'=>'inputtypeid'),
    'surveyQuestionId' => array('name'=>'surveyquestionid','err'=>'surveyquestionid'),
    'surveyResponseId' => array('name'=>'surveyresponseid','err'=>'surveyresponseid'), 
  );
  
  private $minivanCanvassHdr = array(
    'vanid' => array('name'=>'vanid','err'=>'vanid'),
    'dateCanvassed' => array('name'=>'datecanvassed','err'=>'datecanvassed'),
    'dateCreated' => array('name'=>'datecreated','err'=>'datecreated'),
    'inputTypeId' => array('name'=>'inputtypeid','err'=>'inputtypeid'),
    'contacttypeid' => array('name'=>'contacttypeid','err'=>'contacttypeid'),
    'resultid' => array('name'=>'resultid','err'=>'resultid'), 
  );
  
  private $minivanActivistHdr = array(
    'vanid' => array('name'=>'vanid','err'=>'vanid'),
    'dateCreated' => array('name'=>'datecreated','err'=>'datecreated'),
    //'inputTypeId' => array('name'=>'inputtypeid','err'=>'inputtypeid'),
    'activistCodeId' => array('name'=>'activistcodeid','err'=>'activistcodeid'),
  );
  
  private $nlsResultObj;
  

  private function decodeMinivanHdr($fileHdr,$requiredFields) {
    //voterdb_debug_msg('header', $fileHdr);
    $hdrErr = array();
    $hdrPos = array();
    foreach ($requiredFields as $nlpKey => $vanField) {
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
  
  public function decodeMinivanSurveyHdr($fileHdr) { 
    return $this->decodeMinivanHdr($fileHdr, $this->minivanSurveyHdr);
  }
  
  public function decodeMinivanCanvassHdr($fileHdr) { 
    return $this->decodeMinivanHdr($fileHdr, $this->minivanCanvassHdr);
  }
  
  public function decodeMinivanActivistHdr($fileHdr) { 
    return $this->decodeMinivanHdr($fileHdr, $this->minivanActivistHdr);
  }
  
  public function extractMinivanFields($record,$hdrPos) {
    $fields = array();
    foreach ($hdrPos as $fieldName => $pos) {
      $fields[$fieldName] = $record[$pos];
    }
    return $fields;
  }
  
}
