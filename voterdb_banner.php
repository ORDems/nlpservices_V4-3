<?php
/*
 * Name: voterdb_banner.php   V4.2  6/20/18
 */
define('NLP_VERSION','4.2');

/** * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * voterdb_build_banner
 * 
 * Create a banner for the top of pages visited by users.  It shows which
 * county or campaign is being accessed.
 * 
 * @param type $bb_county
 * @return string - contains the banner string
 */

function voterdb_build_banner ($bb_county) {
  $bb_banner = " \n".'<p><span style="font-size:24px; '
      . 'color:#0033ff;font-family:trebuchet ms,helvetica,sans-serif;">';
  $bb_banner .= $bb_county . ' County'. '</span>';
  $bb_banner .= '<span style="font-size:12px; '
      . 'color:#cccccc;font-family:trebuchet ms,helvetica,sans-serif;'
      . 'padding-left:3em;">';
  $bb_banner .= "(&#169; 2017, 2018 NLP Services, Version ".NLP_VERSION. ')</span></p>'." \n";
  return $bb_banner;
}