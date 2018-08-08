  <?php
/*
 * Name: voterdb_dataentry_redirect.php   V4.3 8/7/18
 *
 */


function voterdb_dataentry_redirect() {
  global $base_url;
  
  $output = "redirect";
  
  drupal_goto($base_url);
  
  return $output;
}
