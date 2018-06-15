<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpLegFix.
 */
/*
 * Name: voterdb_class_legislative_fixes.php   V4.2  6/5/18
 */

namespace Drupal\voterdb;


class NlpLegFix{
  
  const LEGFIXTBL = 'leg_district';
  
  private $legList = array(
    'county' => 'County',
    'mcid' => 'MCID',
    'firstName' => 'FName',
    'lastName' => 'LName',
    'hd' => 'HD',
    'pct' => 'Pct'
  );
  
  public function createLegFix($fix) {
    $fields = array();
    foreach ($fix as $nlpKey => $dbField) {
      if(isset($fix[$nlpKey])) {
        $fields[$dbField] = $fix[$nlpKey];
      } else {
        $fields[$dbField] = NULL;
      }
    }
    try {
      db_set_active('nlp_voterdb');
      db_merge(self::LEGFIXTBL)
        ->key(array('MCID' => $fix['mcid']))
        ->fields($fields)
        ->execute();
    }
     catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    db_set_active('default');
    return TRUE;
  }
  
  public function getLegFixes ($county) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::LEGFIXTBL, 'l');
      $query->fields('l');
      $query->condition('County',$county);
      $query->orderBy('HD');
      $query->orderBy('Pct');
      $query->orderBy('LName');
      $query->orderBy('FName');
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    $fixes = array();
    do {
      $fix = $result->fetchAssoc();
      if(empty($fix)) {break;}
      $nlpFix = array();
      foreach ($fix as $nlpKey => $dbField) {
        $nlpFix[$nlpKey] = $fix[$dbField];
      }
      $fixes[$nlpFix['mcid']] = $nlpFix;
    } while (TRUE);
    return $fixes;
  }
  
  public function deleteLegFix($county,$mcid) {
    db_set_active('nlp_voterdb');
    try {
    db_delete(self::LEGFIXTBL)
      ->condition('County', $county)
      ->condition('MCID', $mcid)
      ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() , __FILE__, __LINE__);
      return FALSE;
    }
    db_set_active('default');
    return TRUE;
  }
}
