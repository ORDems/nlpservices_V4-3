<?php
/**
 * Name: voterdb_path.php    V3.1 11/3/17
 * 
 */
/**
 * Path to the NLP folders.  The path will be used to locate the folder for
 * saving either a turf or a generated call list.
 */
// Direcory names used by NLP Services.
define('VO_DIR', 'voterdb_files');
define('VO_TURFPDF_DIR', 'turfpdf');
define('VO_CALLLIST_DIR', 'call-list');
define('VO_MAILLIST_DIR', 'mail-list');
define('VO_INSTRUCTIONS_DIR', 'nlp_instructions');
// URL alias for these pages created during setup.
define('VO_CALLLIST_PAGE', 'nlp_call_list');
define('VO_MAILLIST_PAGE', 'nlp_mail_list');
define('VO_ERROR_PAGE','login_error');  // Doesn't start with nlp_ so login is shown.
define('VO_FRONT_PAGE','front_page'); 

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_get_path
 * 
 * Return the path to the folder where the requested file type can be found.
 *
 * @param type $gp_type - PDF, CALL, MAIL, INST or '' (where '' is just the 
 *                        base for the county).
 * @param type $gp_county - Name of the county or campaign.
 * @return string with path to the NLP folder for this county
 */
function voterdb_get_path($gp_type,$gp_county) {
  $gp_votedb_dir = 'public://'.VO_DIR."/";
  if($gp_county === 'ALL') {
    return $gp_votedb_dir;
  }
  $gp_votedb_dir .= $gp_county.'/';
  switch ($gp_type) {
    case 'PDF':
      $gp_votedb_dir .= VO_TURFPDF_DIR.'/';
      break;
    case 'CALL':
      $gp_votedb_dir .= VO_CALLLIST_DIR.'/';
      break;
    case 'MAIL':
      $gp_votedb_dir .= VO_MAILLIST_DIR.'/';
      break;
    case 'INST':
      $gp_votedb_dir .= VO_INSTRUCTIONS_DIR.'/';
      break;
  }
  return $gp_votedb_dir;
}