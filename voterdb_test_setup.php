<?php


require_once "voterdb_debug.php";


function voterdb_test() {
  
  $countyNames = array('Baker','Benton','Clackamas','Clatsop','Columbia','Coos','Crook','Curry','Deschutes','Douglas','Gilliam','Grant','Harney','Hood_River','Jackson','Jefferson','Josephine','Klamath','Lake','Lane','Lincoln','Linn','Malheur','Marion','Morrow','Multnomah','Polk','Sherman','Tillamook','Umatilla','Union','Wallowa','Wasco','Washington','Wheeler','Yamhill');
  
  //$counties = implode(',', $countyNames);
  
  $counties = "'Baker','Benton','Clackamas','Clatsop','Columbia','Coos','Crook','Curry','Deschutes','Douglas','Gilliam','Grant','Harney','Hood_River','Jackson','Jefferson','Josephine','Klamath','Lake','Lane','Lincoln','Linn','Malheur','Marion','Morrow','Multnomah','Polk','Sherman','Tillamook','Umatilla','Union','Wallowa','Wasco','Washington','Wheeler','Yamhill'";
  
  
  require_once "voterdb_schema.php";
      
  //voterdb_debug_msg('schema', $schema);
  
  //$name = 'activist_codes';
  //$table = $schema[$name];
  
  
  foreach ($schema as $name => $table) {
    voterdb_debug_msg($name.' created', '');
    db_set_active('nlp_voterdb');
    db_drop_table($name);
    db_create_table($name,$table);
    db_set_active('default');
  }

  $output = "test complete";
  return array('#markup' => $output);   

}