<?php
/*
 * Name: voterdb_class_counties.php   V4.3 8/7/18
 *
 */
namespace Drupal\voterdb;


class NlpCounties {
  
  const HDTBL = 'hd_def';
  

  function getCountyNames() {
    db_set_active('nlp_voterdb');
    try {
      $select = "SELECT * FROM {".self::HDTBL."} WHERE 1";
      $query = db_query($select);
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return array();
    }
    db_set_active('default');
    $countyNames = array();
    do {
      $hdRecord = $query->fetchAssoc();
      if(!$hdRecord) {break;}
      $countyNames[$hdRecord['County']] = $hdRecord['County'];
    } while (TRUE);
    if(empty($countyNames)) {return $countyNames;}
    ksort($countyNames);
    return $countyNames;
  }
  
  public function getHdNames($county) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::HDTBL, 'h');
      $query->fields('h');
      $query->condition('County',$county);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      voterdb_debug_msg('e', $e->getMessage() );
      return array();
    }
    db_set_active('default');
    $hdNames = array();
    do {
      $hdRecord = $result->fetchAssoc();
      if(!$hdRecord) {break;}
      $hdNames[$hdRecord['Number']] = $hdRecord['Number'];
    } while (TRUE);
    if(empty($hdNames)) {return $hdNames;}
    ksort($hdNames);
    return $hdNames;
  }
  
}
