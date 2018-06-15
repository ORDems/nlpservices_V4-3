<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpPaths.
 */
/*
 * Name: voterdb_class_paths.php   V4.1  5/29/18
 */

namespace Drupal\voterdb;

class NlpPaths {
  
  const NLPFILES = 'voterdb_files'; 
  const CALLLIST_PAGE = 'nlp_call_list';
  const MAILLIST_PAGE = 'nlp_mail_list';
  const ERROR_PAGE = 'login_error';
  const FRONT_PAGE = 'front_page';
  
  public function getPath($type,$county) {
    $dir = 'public://'.self::NLPFILES."/";
    if($county === 'ALL') {return $dir;}
    $dir .= $county.'/';
    switch ($type) {
      case 'PDF':
        $dir .= 'turfpdf/';
        break;
      case 'CALL':
        $dir .= 'call-list/';
        break;
      case 'MAIL':
        $dir .= 'mail-list/';
        break;
      case 'INST':
        $dir .= 'nlp_instructions/';
        break;
    }
    return $dir;
  }

}
