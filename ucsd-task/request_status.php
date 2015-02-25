<?php

/*
Copyright Matt Day & Cisco 2015
Please see LICENSE for licensing information

*/

include_once "ucsd_api.php";

$status[0] = "Not started"; $colour[0] = "black";
$status[1] = "In Progress"; $colour[1] = "blue; font-weight: bold;";
$status[2] = "Failed"; $colour[2] = "red";
$status[3] = "Completed"; $colour[3] = "green";
$status[4] = "Completed with Warning"; $colour[4] = "orange";
$status[5] = "Cancelled"; $colour[5] = "gray";
$status[6] = "Paused"; $colour[6] = "gray";
$status[7] = "Skipped"; $colour[7] = "gray";


if (!array_key_exists('id',$_GET)) {
	$query_string = 'opName=userAPIGetTabularReport&opData='.rawurlencode('{param0:"6",param1:"",param2:"SERVICE-REQUESTS-T10"}');
	$response = ucsd_api_call($query_string)->{'serviceResult'};
?>
<!doctype html>
<html>
<head>
<title>Service Requests</title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
<style>
	div#sr_req {
		border: 1px solid #000080;
		margin-left: 15%;
		margin-right: 15%;
		padding: 1em;
		margin-top: 3em;
	}
	div#sr_req h3 {
		margin-top: 0;
		font-size: 1.2em;
	}
</style>
<meta http-equiv="refresh" content="60" />
</head>
<body>
<h1>Service Requests</h1>
<p><a href="/ucsd-task">&lt;&lt;Request a new Catalog Item</a></p>
<div id="sr_req">
<?php
	foreach ($response->{'rows'} as $sr) {
		print '<h3>#'.$sr->{'Service_Request_Id'}.': '.$sr->{'Catalog_Workflow_Name'}.'</h3>';
		print '<table style="margin-bottom: 2em;">';
		print '<tr><td style="padding-right: 1em;">Started: </td><td>'.$sr->{'Request_Time'}.'</td></tr>';
		print '<tr><td style="padding-right: 1em;">Task Owner: </td><td>'.$sr->{'Initiating_User'}.'</td></tr>';
		print '<tr><td>Status: </td><td>'.$sr->{'Request_Status'}.'</td></tr>';
		print '<tr><td>More info: </td><td><a href="request_status?id='.$sr->{'Service_Request_Id'}.'#current">Link</a></a></td></tr>';
		print '</table>';
	}

?>


<?php
	exit;
}

$query_string = 'formatType=json&opName=userAPIGetServiceRequestWorkFlow&opData='.rawurlencode('{param0:'.$_GET['id'].'}');

$response = ucsd_api_call($query_string)->{'serviceResult'};

#print var_dump($response);
# Loop through sub-components:
$step = 1;
$completed = true;
$out = '';
foreach ($response->{'entries'} as $entry) {
	# Write to a buffer instead of directly out for now:
	$stat = $entry->{'executionStatus'};
	$curr = '';
	if ($stat == 1) {
		$curr = ' id="current" ';
	}
	$out .= '<h3'.$curr.'>Step '.$step++.': '.$entry->{'stepId'}.'</h3>';
	$out .= '<p style="color: '.$colour[$stat].'">Status: '.$status[$stat].'</p>';
	if ($stat != 3) {
		$completed = false;
	}
}
?>
<!doctype html>
<html>
<head>
<title>Service Request #<?=$response->{'requestId'}?></title>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
<style>
	div#sr_req {
		border: 1px solid #000080;
		margin-left: 15%;
		margin-right: 15%;
		padding: 1em;
		margin-top: 3em;
		max-height: 30em;
		overflow: scroll;
	}
	div#sr_req h3 {
		margin-top: 0;
		font-size: 1.2em;
	}
</style>
<?php
# Auto reload page if all tasks aren't complete:
if ($completed == false) {
	print '<meta http-equiv="refresh" content="10" />';
}
?>
</head>
<body>
<h1>Service Request #<?=$response->{'requestId'}?></h1>
<p>Note: This page updates automatically. You can also <a href="javascript:location.reload();">reload it</a> to track progress.</p>
<div id="sr_req">
<?php
print $out;
?>
</div>
<p style="margin: 1em;">
<a href="/ucsd-task/">Request a new Service</a> | <a href="http://ukidcv-web.cisco.com/ucsd-task/request_status">View all Service Requests</a> | <a href="cancel_task?id=<?=$_GET['id']?>">Cancel this Task</a> | <a href="roll_back_task?id=<?=$_GET['id']?>">Undo and Roll-Back this task</a>
</p>
</body></html>
