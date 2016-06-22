<?php
  ini_set('display_errors', 1);
  ini_set('default_socket_timeout',120);
  $BASE_URL = "http://dev.bukkit.org";
  $QUEUE_URL = "http://dev.bukkit.org/admin/approval-queue/";
  $ADMIN_URL = "http://dev.bukkit.org/admin/";
  $API_KEY = "THIS IS WHERE MY TOP SECRET API KEY GOES";
  $file_count = 0;
  $files = 0;
  $reports = 0;
  $projects = 0;
  $unclaimed_projects = 0;
  $unclaimed_files = 0;
  $unclaimed_reports = 0;
  $unclaimed_project_listing = array();
  $file_listing = array();
  $project_listing = array();
  $report_listing = array();

  include('/var/www/dbo/simple_html_dom.php');
  $context = stream_context_create();
  stream_context_set_params($context, array('user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.8; rv:24.0) Gecko/20100101 Firefox/24.0'));
  $replace = array('<a href="/admin/reports/">Reports pending (', ')', 'Approval queue (', 'projects, ', ' files', '<a href="/admin/approval-queue/">', '</a>');
  $html = file_get_html($QUEUE_URL . '?api-key=' . $API_KEY, 0, $context);
  
  // PROJECTS
  $id = 0;
  foreach($html->find('table[id="projects"] td[class="col-project"] a[!target]') as $e) {
    $projects = $projects + 1;
    $project_name = trim($e->innertext);
    $project_url = 'http://dev.bukkit.org' . trim($e->href);
    array_push($project_listing, array('name' => $project_name));
    $project_listing[$id]['project_url'] = $project_url;
    $id = $id + 1;
  }
  
  $num_claimed = 0;
  $id = 0;
  foreach($html->find('table[id="projects"] td[class="col-project"]') as $e) {
    $project_status = 'needs approval';
    $previous_changes_required = 'null';
    if (strpos($e,'Under') !== false) {
      $project_status = 'under review';
      $num_claimed = $num_claimed + 1;
    } else if (strpos($e,'move from') !== false) {
      $project_status = 'experimental needs final approval';
    } else if (strpos($e,'Requesting experimental') !== false) {
      $project_status = 'requesting experimental';
    } else if (strpos($e,'Previous changes required') !== false) {
      $project_status = 'needs approval after changes';
      $name = trim($e->find('a[!target]')[0]->innertext);
      $previous_changes_required = trim(str_replace($name, '', preg_replace('/\(|\)/','',str_replace('Previous changes required: ', '', $e->plaintext))));
    }
    $project_listing[$id]['status'] = $project_status;
    $project_listing[$id]['previous_changes_required'] = $previous_changes_required;
    $id = $id + 1;
    $unclaimed_projects = ($id - $num_claimed);
  }
  
  $id = 0;
  foreach($html->find('table[id="projects"] td[class="col-category"] ul') as $e) {
    $project_categories = array();
    foreach($e->find('a') as $ea) {
      $project_categories[] = $ea->innertext;
    }
    $project_listing[$id]['categories'] = $project_categories;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="projects"] td[class="col-user"]') as $e) {
    $project_author = trim($e->find('a')[0]->innertext);
    $project_listing[$id]['author'] = $project_author;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="projects"] td[class="col-count"] a') as $e) {
    $associated_file_count = intval(trim(explode(' ', $e->innertext)[0]));
    $project_listing[$id]['associated_file_count'] = $associated_file_count;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="projects"] td[class="col-date"]') as $e) {
    date_default_timezone_set('UTC');
    $uploaded_on = strtotime(trim(str_replace(" at", "", trim($e->plaintext))));
    $project_listing[$id]['timestamp'] = $uploaded_on;
    $id = $id + 1;
  }
  
  // FILES
  
  foreach($html->find('table[id="files"] td[class="col-file-link"] a') as $e) {
    $uploaded_file_name = trim($e->innertext);
    array_push($file_listing, array('uploaded_file_name' => $uploaded_file_name));
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-file"] a') as $e) {
    $file_name = trim($e->innertext);
    $file_page_url = 'http://dev.bukkit.org' . $e->href;
    $file_listing[$id]['file_name'] = $file_name;
    $file_listing[$id]['file_page_url'] = $file_page_url;
    $id = $id + 1;
    $files = $id;
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-filesize"]') as $e) {
    $file_size = trim($e->plaintext);
    $file_listing[$id]['file_size'] = $file_size;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-file-link"]') as $e) {
    $status = 'ERROR';
    $claimed_by = 'null';
    if (strpos(($e->innertext),'Under') == false) {
      $status = 'needs approval';
      $unclaimed_files = $unclaimed_files + 1;
    } else {
      $status = 'under review';
      $claimed_by = preg_replace('/\(|\)/','',preg_replace("/\s+/", " ", trim(explode(' ', preg_replace( '/\s+/', ' ', trim($e->plaintext)))[4])));
    }
    $file_listing[$id]['status'] = $status;
    $file_listing[$id]['claimed_by'] = $claimed_by;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-file-link"] a') as $e) {
    $file_url = $e->href;
    $file_listing[$id]['file_url'] = $file_url;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-project"] a') as $e) {
    $associated_project = $e->innertext;
    $associated_project_url = 'http://dev.bukkit.org' . $e->href;
    $file_listing[$id]['associated_project'] = $associated_project;
    $file_listing[$id]['associated_project_url'] = $associated_project_url;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-user"] a') as $e) {
    $uploaded_by = $e->plaintext;
    $file_listing[$id]['uploaded_by'] = $uploaded_by;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[id="files"] td[class="col-date"] span') as $e) {\
    date_default_timezone_set('UTC');
    $uploaded_on = strtotime(trim(str_replace(" at", "", $e->plaintext)));
    $file_listing[$id]['timestamp'] = $uploaded_on;
    $id = $id + 1;
  }
  
  // REPORTS
  
  $html = file_get_html($ADMIN_URL . 'reports/?api-key=' . $API_KEY, 0, $context);
  $id = 0;
  foreach($html->find('table[class="listing"] td[class="col-report"]') as $e) {
    $title = trim(str_replace('Report: ', '', $e->plaintext));
    $report_url = trim($e->find('a')[0]->href);
    $reports = $reports + 1;
    $report_listing[$id]['title'] = $title;
    $report_listing[$id]['report_url'] = $report_url;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[class="listing"] td[class="col-target"]') as $e) {
    $type = strtolower(trim(str_replace(':', '', explode(' ', $e->plaintext)[0])));
    $url = 'http://dev.bukkit.org' . trim($e->find('a')[0]->href);
    $report_listing[$id]['target_type'] = $type;
    $report_listing[$id]['target_url'] = $url;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[class="listing"] td[class="col-user"] a') as $e) {
    $reporter = trim($e->innertext);
    $report_listing[$id]['reporter'] = $reporter;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[class="listing"] td[class="col-report-category"]') as $e) {
    $category = trim($e->plaintext);
    $report_listing[$id]['category'] = $category;
    $id = $id + 1;
  }
  
  $id = 0;
  foreach($html->find('table[class="listing"] td[class="col-date] span') as $e) {\
    date_default_timezone_set('UTC');
    $uploaded_on = strtotime(trim(str_replace(" at", "", $e->plaintext)));
    $report_listing[$id]['timestamp'] = $uploaded_on;
    $id = $id + 1;
  }
  
  $cache = $files . "\n" . $projects . "\n" . $reports . "\n" . $unclaimed_files . "\n" . $unclaimed_projects . "\n" . json_encode($project_listing) . "\n" . json_encode($file_listing) . "\n" . json_encode($report_listing);
  file_put_contents('/var/www/dbo/api/v1/cache.txt', $cache);
  return;
?>