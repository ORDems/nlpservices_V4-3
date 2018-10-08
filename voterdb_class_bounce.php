<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpBounce.
 */
/*
 * Name: voterdb_class_bounce.php   V4.3  9/20/18
 */

namespace Drupal\voterdb;

class NlpBounce {
  
  const BOUNCEBLOCKTBL = 'bounce_blocked';
  const BOUNCENONDELIVERYTBL = 'bounce_non_delivery_report';
  const BOUNCECODESCORETBL = 'bounce_code_score';
  const BOUNCEREPORTNOTIFYTBL = 'bounce_notified';



  
  private $fields = array('VANID','DateIndex','County');
  
  private $crosstabCountList = array(
    'index'=>'Index',
    'county'=>'County',
    'party'=>'Party',
    'regVoters'=>'RegVoters',
    'regVoted'=>'RegVoted',
  );
  
  public $bounceNotifyList = array(
    'reportId' => 'report_id',
    'blockedId' => 'blocked_id',
    'notified' => 'notified',
    'date' => 'date',
    'county' => 'county',
    'recipientFirstName' => 'NLfname',
    'recipientLastName' => 'NLlname',
    'recipientEmail' => 'NLemail',
    'senderFirstName' => 'sfname',
    'senderLastName' => 'slname',
    'senderEmail' => 'semail',
    'code' => 'code',
    'description' => 'description',
  );
  
  
  

  public function deleteNotification($reportId) {
    db_delete(self::BOUNCEREPORTNOTIFYTBL)
      ->condition('report_id', $reportId)
      ->execute();
  }
  
  public function getBounces() {
    try {
      $query = db_select(self::BOUNCEREPORTNOTIFYTBL, 'n');
      $query->orderBy('county');
      $query->orderBy('NLemail');
      $query->fields('n');
      $result = $query->execute();
    }
    catch (Exception $e) {
      voterdb_debug_msg('e', $e->getMessage() );
      return array();
    }
    $flipList = array_flip($this->bounceNotifyList);
    $bounceList = array();
    do {
      $bouncer = $result->fetchAssoc();
      if (!$bouncer) {break;}
      $bounceInfo = array();
      foreach ($bouncer as $dbKey => $field) {
        $bounceInfo[$flipList[$dbKey]] = $field;
      }
      $bounceList[$bounceInfo['reportId']] = $bounceInfo;
    } while (TRUE);
    return $bounceList;
  }

}
