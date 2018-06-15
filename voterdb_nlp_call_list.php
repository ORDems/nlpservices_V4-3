<?php
// voterdb_call_list.
$bv_call_file_name = filter_input(INPUT_GET,'FileName',FILTER_SANITIZE_STRING);
if ($bv_call_file_name == NULL) {
  echo "Opps! You are not authorized for this.";
  return;
}
$bv_i = 0;
$bv_call_file_fh = fopen($bv_call_file_name,"r");
if (!$bv_call_file_fh) {
  echo "Opps! No file to display";
  return;
}
echo '<table border="1" style="width:600px;">'; 
do {
  $bv_call_record_string = fgets($bv_call_file_fh);
  if (!$bv_call_record_string) {break;}
  $bv_call_info = explode("\t", $bv_call_record_string);
  echo "<tr>";
  for ($index = 0; $index < count($bv_call_info); $index++) {
    if ($index==0) {echo '<td style="width:250px;">';} 
    elseif ($index==3) {echo '<td style="width:250px;">';} else {echo "<td>";}
    echo $bv_call_info[$index];
    echo "</td>";
  }
  echo "</tr>";
} while (TRUE);
echo "</table>";
fclose($bv_call_file_fh);