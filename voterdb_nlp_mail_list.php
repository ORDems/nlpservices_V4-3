<?php
// Voterdb_mail_list.
$mv_mail_file_name = filter_input(INPUT_GET,'FileName',FILTER_SANITIZE_STRING);
if ($mv_mail_file_name == NULL) {
  echo "Opps! You are not authorized for this.";
  return;
}
$mv_i = 0;
$mv_mail_file_fh = fopen($mv_mail_file_name,"r");
if (!$mv_mail_file_fh) {
  echo "Opps! No file to display";
  return;
}
echo '<table border="1" style="font-size:small; padding:0px; border-color:#d3e7f4; border-width:1px; width:550px;">'; 
$mv_mail_hdr_string = fgets($mv_mail_file_fh);
$mv_hdr_info = explode("\t", $mv_mail_hdr_string);
echo '<thead><tr>';
for ($mv_index = 0; $mv_index < count($mv_hdr_info); $mv_index++) {
    if ($mv_index==0) {echo '<th style="width:300px;">';} 
    else {echo "<th>";}
    echo $mv_hdr_info[$mv_index];
    echo "</th>";
  }
echo '</tr></thead>';
echo '<tbody>';
do {
  $mv_mail_record_string = fgets($mv_mail_file_fh);
  if (!$mv_mail_record_string) {break;}
  $mv_mail_info = explode("\t", $mv_mail_record_string);
  echo "<tr>";
  for ($mv_index = 0; $mv_index < count($mv_mail_info); $mv_index++) {
    echo "<td>";
    echo $mv_mail_info[$mv_index];
    echo "</td>";
  }
  echo "</tr>";
} while (TRUE);
echo "</tbody></table>";
fclose($mv_mail_file_fh);