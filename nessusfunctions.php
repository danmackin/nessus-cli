<?php
/*
* A suite of nessus function calls to be used on the CLI.
* Written by Dan Mackin @AppliedTrust
* dan at appliedtrust dot com
*/
date_default_timezone_set('America/Denver');

function post($request_url, $post_data) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $request_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  $result = curl_exec($ch);
  
  return $result;
}


function get_logintoken($url, $username, $password) {

  $request_url = $url . '/login';
  $data = array(
    'login' => $username,
    'password' => $password
  );

  $result = post($request_url, $data);
  $xml = simplexml_load_string($result);
  $token = $xml->contents->token;
  return $token;
}


function policy_list($url, $token) {
  $request_url = $url . '/policy/list';
  $token_array = array(
     'token' => (string) $token
     );

  $policy_result = post($request_url, $token_array);
  $policy_xml = simplexml_load_string($policy_result);
  
  foreach ($policy_xml[0]->contents->policies->policy as $policy) {
    print('Policy Name is: "' . $policy->policyName . '"	ID: ' . $policy->policyID . "\n");
  }
}


function get_policy($url, $token, $policy_name) {
  $request_url = $url . '/policy/list';
  $token_array = array(
     'token' => (string) $token
     );

  $policy_result = post($request_url, $token_array);
  $policy_xml = simplexml_load_string($policy_result);
  $policy_id = NULL;
  foreach ($policy_xml[0]->contents->policies->policy as $policy) {
    if ($policy->policyName == $policy_name) {
      $policy_id = $policy->policyID;
      break;
    }
  }

return $policy_id;
}


function list_reports($url, $token) {
  $request_url = $url . '/report/list';
  $token_array = array(
     'token' => (string) $token
     );

  $report_result = post($request_url, $token_array);
  $report_xml = simplexml_load_string($report_result);

  foreach ($report_xml[0]->contents->reports->report as $report) {
      print('Report Name: ' . $report->readableName . "\n");
      print('Report ID: ' . $report->name . "\n");
      print('Report Generated: ' . date(r, (int) $report->timestamp) . "\n");
      print('Status: ' . $report->status . "\n\n");
    }
}


function is_running($url, $token, $scan_id) {
  $request_url = $url . '/report/list';
  $token_array = array(
     'token' => (string) $token
     );
  $report_result = post($request_url, $token_array);
  $report_xml = simplexml_load_string($report_result);

  foreach ($report_xml[0]->contents->reports->report as $report) {
      if ((string) $report->name == $scan_id) {
        if ((string) $report->status == 'running') {
          return true;
        }
        else {
          return false;
        }
      }
    }
}


function get_scan_id($url, $token, $scan_name) {
  $request_url = $url . '/report/list';
  $token_array = array(
    'token' => (string) $token
    );
  $report_result = post($request_url, $token_array);
  $scan_xml = simplexml_load_string($report_result);

  foreach ($scan_xml[0]->contents->reports->report as $report) {
    if ($report->readableName == $scan_name) {
      $readable_name = $report->readableName;
      $report_id = $report->name;
      return (string) $report_id;
    }
  }
  return NULL;
}


function download_report($url, $token, $report_id, $output_file) {
  $request_url = $url . '/file/report/download';
  $download_data = array(
    'token' => (string) $token,
    'report' => (string) $report_id,
    );
  $output_file = post($request_url, $download_data);
  $myFile = $output_file;
  $fh = fopen($myFile, 'w') or die("Can't Open File");
  fwrite($fh, $download_result->data);
  fclose($fh);
}


function launch_scan($url, $token, $policy_id, $scan_name, $target) {
  $request_url = $url . '/scan/new';
  $launch_data = array(
    'token' => (string) $token,
    'policy_id' => $policy_id,
    'target' => $target,
    'scan_name' => $scan_name,
    );
  $launch_result = post($request_url, $launch_data);
  $launch_xml = simplexml_load_string($launch_result);
  (string) $scan_id = $launch_xml[0]->contents->scan->uuid;
  print('Scan ID is: ' . $scan_id . "\n");
  return $scan_id;
}


function pause_scan($url, $token, $scan_id) {
  $request_url = $url . '/scan/pause';
  $pause_data = array(
    'token' => (string) $token,
    'scan_uuid' => $scan_id,
    );
  $pause_result = post($request_url, $pause_data);
  $pause_xml = simplexml_load_string($pause_result);
  (string) $pause_status = $pause_xml[0]->status;
  //print('Scan ID is: ' . $scan_id . "\n");
  //print_r($pause_xml);
  return $pause_status;
}

function resume_scan($url, $token, $scan_id) {
  $request_url = $url . '/scan/stop';
  $resume_data = array(
    'token' => (string) $token,
    'scan_id' => $scan_id,
    );
  $resume_result = post($request_url, $resume_data);
  $resume_xml = simplexml_load_string($resume_result);
  (string) $scan_id = $launch_xml[0]->contents->scan->uuid;
  return $scan_id;
}


function stop_scan($url, $token, $policy_id, $scan_name, $target) {
  $request_url = $url . '/scan/resume';
  $launch_data = array(
    'token' => (string) $token,
    'policy_id' => $policy_id,
    'target' => $target,
    'scan_name' => $scan_name,
    );
  $launch_result = post($request_url, $launch_data);
  $launch_xml = simplexml_load_string($launch_result);
  (string) $scan_id = $launch_xml[0]->contents->scan->uuid;
  return $scan_id;
}

function list_scan_jobs($url, $token) {
  $request_url = $url . '/scan/list';
  $token_array = array(
     'token' => (string) $token
     );

  $scan_result = post($request_url, $token_array);
  $scan_xml = simplexml_load_string($scan_result);

  foreach ($scan_xml[0]->contents->scans->scanList->scan as $scan) {
      print('Scan Name: ' . $scan->readableName . "\n");
      print('Scan ID: ' . $scan->uuid . "\n");
      print('Start Time: ' . date('r', (int) $scan->start_time) . "\n");
      print('Status: ' . $scan->status . "\n\n");
    }
}
