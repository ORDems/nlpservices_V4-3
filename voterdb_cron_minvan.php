<?php
/**
 * Name:  voteredb_cron_minivan.php     V4.3  9/1/18
 */


define('MAXQUEUELIMIT','1000');  // Limit of number of reports per queue entry.



function voterdb_minivan_chk() {
  watchdog('voterdb_minivan_chk', 'report chk called');


  return array();
}


function voterdb_minvan_notify() {
  
}


function voterdb_cron_minivan_notify($item) {
  watchdog('voterdb_cron_minivan_notify', 'minivan check processed item created at @time', 
    array('@time' => date_iso8601($item->created),));

  voterdb_minvan_notify();
}
