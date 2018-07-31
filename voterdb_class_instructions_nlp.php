<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpInstructions.
 */
/*
 * Name: voterdb_class_instructions_nlp.php   V4.3 7/29/18
 *
 */
namespace Drupal\voterdb;

class NlpInstructions {
  
  const INSTRUCTIONSTBL = "instructions";

  private $instructionsList = array(
    'county' => 'County',
    'type' => 'Type',
    'fileName' => 'FileName',
    'title' => 'Title',
    'blurb' => 'Blurb',
  );
  
  private $typeList = array('canvass','postcard','absentee');

  
  public function createInstructions($req) {
    db_set_active('nlp_voterdb');
    db_merge(self::INSTRUCTIONSTBL)
      ->key(array(
        'County' => $req['county'],
        'Type' => $req['type'],))
      ->fields(array(
        'FileName' => $req['fileName'],
        'Title' => $req['title'],
        'Blurb' => $req['blurb'],))
      ->execute();
    db_set_active('default');
  }
  
  public function getInstructions($county) {

    $rec = array(
      'county'=>'',
      'type'=>'',
      'fileName'=>'',
      'title'=>'',
      'blurb'=>''
    );
    
    $instructs = array(
      'canvass'=>$rec,
      'postcard'=>$rec,
      'absentee'=>$rec       
    );
    
    $dbList = array_flip($this->instructionsList);
    db_set_active('nlp_voterdb');
    $tselect = "SELECT * FROM {".self::INSTRUCTIONSTBL."} WHERE County = :county ";
    $targs = array(':county' => $county);
    $result = db_query($tselect,$targs);
    db_set_active('default');
    do {
      $record = $result->fetchAssoc();
      if(empty($record)) {break;}
      foreach ($record as $dbKey => $value) {
        $instruct[$dbList[$dbKey]] = $value;
      }
      $instructs[$instruct['type']] = $instruct;
    } while (TRUE);
    return $instructs;
  }
  

}
