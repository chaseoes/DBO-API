<?php
  header('Content-type: application/json');
  $key = 'bleeding';
  $lines = file('cache.txt');
  if ($_GET["key"] == $key) {
    echo json_encode(array('project_count' => json_decode($lines[1]), 'file_count' => json_decode($lines[0]), 'unclaimed_project_count' => json_decode($lines[4]), 'unclaimed_file_count' => json_decode($lines[3]), 'unclaimed_report_count' => json_decode($lines[2]), 'project_listing' => json_decode($lines[5]), 'file_listing' => json_decode($lines[6]), 'report_listing' => json_decode($lines[7]), 'last_updated_timestamp' => filemtime('cache.txt')), JSON_PRETTY_PRINT);
  } else {
    echo 'Invalid Key';
  }
?>