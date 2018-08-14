<?php
/**
 * @file
 * Contains Drupal\voterdb\NlpPaths.
 */
/*
 * Name: voterdb_class_paths.php   V4.1  5/29/18
 */
//require_once "voterdb_debug.php";

namespace Drupal\voterdb;

class NlpPaths {
  
  const NLPFILES = 'voterdb_files'; 
  const CALLLIST_PAGE = 'nlp_call_list';
  const MAILLIST_PAGE = 'nlp_mail_list';
  const ERROR_PAGE = 'login_error';
  const FRONT_PAGE = 'front_page';

  const TURFPDF_DIR = 'turfpdf';
  const CALLLIST_DIR = 'call-list';
  const MAILLIST_DIR = 'mail-list';
  const INSTRUCTIONS_DIR = 'nlp_instructions';

  
  public function getPath($type,$county) {
    $dir = 'public://'.self::NLPFILES."/";
    if($county === 'ALL') {return $dir;}
    $dir .= $county.'/';
    switch ($type) {
      case 'PDF':
        $dir .= self::TURFPDF_DIR.'/';
        break;
      case 'CALL':
        $dir .= self::CALLLIST_DIR.'/';
        break;
      case 'MAIL':
        $dir .= self::MAILLIST_DIR.'/';
        break;
      case 'INST':
        $dir .= self::INSTRUCTIONS_DIR.'/';
        break;
    }
    return $dir;
  }

  public function createDir($type,$county) {
    
    switch ($type) {
	  case 'TEMP':
	    $dir = 'public://temp';
	    break;
      case 'NLP':
	    $dir = 'public://'.self::NLPFILES;
	    break;
	  case 'COUNTY':
	    $dir = 'public://'.self::NLPFILES."/".$county;
	    break;
      case 'PDF':
        $dir = 'public://'.self::NLPFILES."/".$county."/".self::TURFPDF_DIR;
        break;
      case 'CALL':
        $dir = 'public://'.self::NLPFILES."/".$county."/".self::CALLLIST_DIR;
        break;
      case 'MAIL':
        $dir = 'public://'.self::NLPFILES."/".$county."/".self::MAILLIST_DIR;
        break;
      case 'INST':
        $dir = 'public://'.self::NLPFILES."/".$county."/".self::INSTRUCTIONS_DIR;
        break;
    }
	//voterdb_debug_msg('dir',$dir);
	file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    return;
  }

}
