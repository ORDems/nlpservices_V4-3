<?php
/*
 * Name: voterdb_debug.php   V4.2 6/16/18
 */

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_current_file
 * 
 * @param type $cf_file
 * @return type
 */
function voterdb_current_file($cf_file) {
  $cf_file_path = explode("/", $cf_file);
  $cf_end = sizeof($cf_file_path);
  $cf_file_name = $cf_file_path[$cf_end-1];
  $cf_fnp = str_replace('.php','', $cf_file_name);
  $cf_fnb = str_replace('voterdb_','', $cf_fnp);
  return $cf_fnb;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_debug_msg
 * 
 * @param type $dm_structure
 * @param type $se_file
 * @param type $se_line
 */
function voterdb_debug_msg($dm_msg,$dm_structure) {
  $backTrace = debug_backtrace(); 
  $caller = voterdb_current_file($backTrace[0]['file']);
  $callerLine = $backTrace[0]['line'];
  drupal_set_message("DEBUG ".$dm_msg." (".$caller." ".$callerLine.")"   ,'error');
  if ($dm_structure != '') {
    drupal_set_message('<pre>'.print_r($dm_structure, true).'</pre>','status');
  }
}


/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_timer
 * 
 * Calculate the duration of an event.
 *
 * @param type $vt_event - start or stop.
 * @param type $vt_stime - the starting time.
 * @return either the start time or the elapsed time.
 */
function voterdb_timer($vt_event,$vt_stime) {
  $vt_ctime = microtime();
  $vt_atime = explode(' ', $vt_ctime);
  $vt_time = $vt_atime[1] + $vt_atime[0];
  switch ($vt_event) {
    case 'start':
      $vt_rtime = $vt_time;
      break;
    case 'end':
      $vt_rtime = ($vt_time - $vt_stime);
      break;
  }
return $vt_rtime;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * This is a standard method of reducing the likelihood of an MySQL insertion
 * attack.
 * @param type $var
 * @return type
 */
function sanitize_string($var) {
  $var1 = stripslashes($var);
  $var2 = htmlentities($var1);
  $var3 = strip_tags($var2);
  $var4 = trim($var3);  // get rid of extra blanks and new line
  return $var4;
}

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * This converts a string to hex for debugging
 * @param type $string
 * @return type
 */
function strToHex($string) {
  $hex = '';
  for ($i = 0; $i < strlen($string); $i++) {
    $sh = dechex(ord($string[$i]));
    $shp = str_pad($sh, 2, '0',STR_PAD_LEFT);
    if (strlen($sh) == 1) {
      $hex .= ' '.$shp.' ';
    } else {
      $hex .= $shp;
    }
  }
  return $hex;
}
