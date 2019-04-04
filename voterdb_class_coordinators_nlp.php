<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpCoordinators.
 */
/*
 * Name: voterdb_class_coordinators_nlp.php   V4.3 7/29/18
 *
 */
namespace Drupal\voterdb;

class NlpCoordinators {
  
  const COORDINATORTBL = "coordinator";
  const PCTCOORDINATORTBL = "pct_coordinator";

  
  public $coordinatorList = array(
    'cindex' => 'CIndex',
    'county' => 'County',
    'firstName' => 'FirstName',
    'lastName' => 'LastName',
    'email' => 'Email',
    'phone' => 'Phone',
    'scope' => 'Scope',
    'hd' => 'HD',
    'partial' => 'Partial',
  );
  
 
  
  public function createCoordinator($req) {
    foreach ($req as $nlpKey => $value) {
      $fields[$this->coordinatorList[$nlpKey]] = $value;
    }
    $pctList = '';
    if($req['scope'] == 'Pct') {
      $pctList = $req['partial'];
      $fields[$this->coordinatorList['partial']] = TRUE;
    }
    db_set_active('nlp_voterdb');
    $cindex = db_insert(self::COORDINATORTBL)
      ->fields($fields)
      ->execute();
    db_set_active('default');
    // If we have a list of precincts for this coordinator, add the list to the
    // database.
    if(!empty($pctList)) {
      $pcts = explode(',', $pctList);
      db_set_active('nlp_voterdb');
      foreach ($pcts as $pct) {
        db_insert(self::PCTCOORDINATORTBL)
          ->fields(array(
            'CIndex' => $cindex,
            'pct' => trim($pct),
          ))
          ->execute();
      }
      db_set_active('default');
    }
  }
  
  public function getCoordinators($county) {
     // Get all the coordinators defined for this county.
    db_set_active('nlp_voterdb');
    $select = "SELECT * FROM {".self::COORDINATORTBL."} WHERE County = :county ";
    $args = array(
      ':county' => $county,);
    $result = db_query($select,$args);
    $dbList = array_flip($this->coordinatorList);
    db_set_active('default');
    $coordinators = array();
    do {
      $record = $result->fetchAssoc();
      //voterdb_debug_msg('record', $record);
      if(empty($record)) {break;}
      $coordinator = array();
      foreach ($record as $dbKey => $value) {
        $coordinator[$dbList[$dbKey]] = $value;
      }
      $pcts = array();
      $pctList = '';
      if ($coordinator['partial']) {
        db_set_active('nlp_voterdb');
        $pselect = "SELECT * FROM {".self::PCTCOORDINATORTBL."} WHERE CIndex = :index ";
        $args = array(
          ':index' => $coordinator['cindex'],);
        $presult = db_query($pselect,$args);
        db_set_active('default');
        do {
          $pct = $presult->fetchAssoc();
          if(empty($pct)) {break;}
          $pcts[$pct['Pct']] = $pct['Pct'];  
        } while (TRUE);
        $pctList = implode(',', $pcts);
      }
      $coordinator['pcts'] = $pcts;
      $coordinator['pctList'] = $pctList;
      $coordinators[$coordinator['cindex']] = $coordinator;
    } while (TRUE);  
    return $coordinators;
  }
  
  public function deleteCoordinator($cindex) {
    db_set_active('nlp_voterdb');
    db_delete(self::COORDINATORTBL)
      ->condition('CIndex', $cindex)
      ->execute();
    // Delete any precincts defined for this coordinator, if any.
    db_delete(self::PCTCOORDINATORTBL)
      ->condition('CIndex', $cindex)
      ->execute();
    db_set_active('default');
  }
  
  public function updateCoordinator($req) {
    foreach ($req as $nlpKey => $value) {
      if($nlpKey!='cindex') {
        $fields[$this->coordinatorList[$nlpKey]] = $value;
      }
    }
    db_set_active('nlp_voterdb');
    db_merge(self::COORDINATORTBL)
      ->key(array('CIndex'=> $req['cindex']))
      ->fields($fields)
      ->execute();
    db_set_active('default');
  }
  
  function getCoordinator($region) {
    $allCos = $region['coordinators'];
    $co = array();
    if(empty($allCos)) {
      return $co;
    }
    $county  = $region['county'];
    if(empty($allCos[$county])) {
      return $co;  // No one in the county is a coordinator.
    }
    // If there is a coordinator assigned to the precinct, use that person.  Else
    // chose the house district coordinator.  If there is no HD coordinator, 
    // then the county coordinator.  There should be at least one of these.  
    // If not, no one will be chosen.
    $pct = $region['pct'];
    $hd = $region['hd'];
    $cntyCos = $allCos[$county];
    if(isset($cntyCos['pct'][$pct])) {
      $co = $cntyCos['pct'][$pct];
    } elseif(isset($cntyCos['hd'][$hd])) {
      $co = $cntyCos['hd'][$hd];
    } elseif (isset($cntyCos['county'])) {
      $co = $cntyCos['county'];
    }
    return $co;
  }
  
  function getAllCoordinators() {
    db_set_active('nlp_voterdb');
    $select = "SELECT * FROM {".self::COORDINATORTBL."} WHERE  1 ";
    $result = db_query($select);
    db_set_active('default');
    $dbList = array_flip($this->coordinatorList);
  	$cos = array();
    do {
      $record = $result->fetchAssoc();
      if(empty($record)) {break;}
      $co = array();
      foreach ($record as $dbKey => $value) {
        $co[$dbList[$dbKey]] = $value;
      }
      $cos[$co['cindex']] = $co;
    } while (TRUE);
    //voterdb_debug_msg('cos', $cos);
    $coordinators = array();
    foreach ($cos as $co) {
      $county = $co['county'];
      $scope = $co['scope'];
      $cindex = $co['cindex'];
      switch ($scope) {
        case 'PCT':
          db_set_active('nlp_voterdb');
          $tselect = "SELECT * FROM {".self::PCTCOORDINATORTBL."} WHERE  CIndex = :index";
          $targs = array(
            ':index' => $cindex,);
          $tresult = db_query($tselect,$targs);
          db_set_active('default');
          
          do {
            $record = $tresult->fetchAssoc();
            if(empty($record)) {break;}
            $pct = $record['Pct'];
            if(!isset($coordinators[$county]['pct'][$pct])) {
              $coordinators[$county]['pct'][$pct] = $co;
            }
          } while (TRUE);
          break;
        case 'HD':
          $hd = $co['hd'];
          if(!isset($coordinators[$county]['hd'][$hd])) {
            $coordinators[$county]['hd'][$hd] = $co;
          }
          break;
        case 'COUNTY':
          if(!isset($coordinators[$county]['county'])) {
            $coordinators[$county]['county'] = $co;
          }
          break;
      }
    }
    return $coordinators;
  }
 
}
