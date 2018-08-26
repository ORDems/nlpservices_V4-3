<?php
/*
 * Name: voterdb_class_magic_word.php   V4.2 7/13/18
 *
 */
namespace Drupal\voterdb;

class NlpMagicWord {
  
  const MAGICWORDTBL = 'magic_word';
  const MAGICWORDSTBL = 'magic_words';
  
  const NUMWORDS = 15788;
  
  function __construct() {
    $this->result = NULL;
  }

  public function createMagicWord() {
    $index = rand(1, self::NUMWORDS);
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::MAGICWORDSTBL, 'm');
      $query->addField('m', 'Word');
      $query->condition('MWKey',$index);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->errorInfo;
      voterdb_debug_msg('error', $error );
      return FALSE;
    }
    db_set_active('default');
    $wordArray = $result->fetchAssoc();
    if(empty($wordArray)) {return NULL;}
    $word = $wordArray['Word'];
    //$lcLetters = substr(str_shuffle('abcdefghijkmnopqrstuvwxyz'), 0, 6);
    //$ucLetter = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ'), 0, 1);
    $number = substr(str_shuffle('23456789'), 0, 1);
    //$password = substr(str_shuffle($lcLetters.''.$ucLetter.''.$number), 0, 8);
    $password = $word.$number;
    return $password;
  }
  
  public function setMagicWord($mcid,$magicWord) {
    db_set_active('nlp_voterdb');
    try {
      db_merge(self::MAGICWORDTBL)
        ->key(array('MCID' => $mcid))
        ->fields(array(
            'MagicWord' => $magicWord,
        ))
        ->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->errorInfo;
      voterdb_debug_msg('error', $error );
      return FALSE;
    }
    db_set_active('default');
    return TRUE;
  }
  
  public function getMagicWord($mcid) {
    db_set_active('nlp_voterdb');
    try {
      $query = db_select(self::MAGICWORDTBL, 'm');
      $query->addField('m', 'MagicWord');
      $query->condition('MCID',$mcid);
      $result = $query->execute();
    }
    catch (Exception $e) {
      db_set_active('default');
      $error = $e->errorInfo;
      voterdb_debug_msg('error', $error );
      return FALSE;
    }
    db_set_active('default');
    $magicWord = $result->fetchAssoc();
    if(empty($magicWord)) {return NULL;}
    return $magicWord['MagicWord'];
  }
  
}
