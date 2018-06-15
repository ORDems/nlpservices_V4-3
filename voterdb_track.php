<?php
/*
 * Name: voterdb_track.php      V3.0   2/12/17
 *
 */
require_once "voterdb_constants_log_tbl.php";

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_login_tracking
 *
 * Keep track of login attempts, either for Russians or for NLs who are
 * having trouble.
 *
 * @param type $lt_type
 * @param type $lt_county
 * @param type $lt_status
 * @param type $lt_info
 */
function voterdb_login_tracking($lt_type,$lt_county,$lt_status, $lt_info) {
  date_default_timezone_set('America/Los_Angeles');
  $lt_name = 'anon';
  $lt_user = $GLOBALS['user'];  
  if($lt_user->uid != 0) {  // Check if user is logged in.
    $lt_name = $lt_user->name;
  }
  $lt_ip = $lt_user->hostname;
  //$lt_ip = sanitize_string($_SERVER['REMOTE_ADDR']);
  $lt_date = date('Y-m-d G:i:s');
  
  db_set_active('nlp_voterdb');
  db_insert(DB_TRACK_TBL)
    ->fields(array(
      TR_TYPE=>$lt_type, 
      TR_COUNTY=>$lt_county, 
      TR_DATE=>$lt_date, 
      TR_USER =>$lt_name,
      TR_IP=>$lt_ip, 
      TR_STATUS=>$lt_status, 
      TR_INFO=>$lt_info,
    ))
    ->execute();
  db_set_active('default');
}