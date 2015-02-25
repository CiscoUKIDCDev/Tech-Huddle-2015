<?php
/*
Copyright Matt Day & Cisco 2015
Please see LICENSE for licensing information

*/
include_once "ucsd_api.php";

if ($_POST['Catalog_Name'] == "") {
	header("Location: http://10.52.208.8/ucsd-task/");
	exit;
}

# Loop through POST requests to get Input variables:

# Construct URL:
$query_string = 'formatType=json&opName=userAPISubmitVAppServiceRequest&opData='.rawurlencode('{param0:"'.$_POST['Catalog_Name'].'",param1:{"list":[');
$i = 0;

# Very bad way to do this... :( - but search http POST keys for anything prefixed with input_
foreach (array_keys($_POST) as $req) {
	if (preg_match('/^input_/', $req)) {
		# Gross hack as I need to put a ',' after each item but cannot be on last - as input array size is
		# unknown I'm basically hacking it... :(
		$input_array[$i++] = rawurlencode('{"name":"'.$req.'","value":"'.$_POST[$req].'"}');
	}
	else if (preg_match('/^multi_/', $req)) {
		$real_name = preg_replace('/^multi_/','',$req);
		$out = '';
		foreach ($_POST[$req] as $item) {
			$input_array[$i++] = rawurlencode('{"name":"'.$real_name.'","value":"'.$item.'"}');
		}
	}
}
# Due to hack above need to loop back through - probably a more elegant solution using PHP libraries!
for ($i = 0; $i < sizeof($input_array); $i++) {
	$query_string .= $input_array[$i];
	# Append a ',' only if not last item
	if ($i < (sizeof($input_array) - 1)) {
		$query_string .= ',';
	}
}

$query_string .= rawurlencode(']},param2:1000}');

# Call the API:
$response = ucsd_api_call($query_string);
$sr_no = $response->{'serviceResult'};

# Redirect to status page:
header('Location: http://10.52.208.8/ucsd-task/request_status?id='.$sr_no.'&name='.$_POST['Catalog_Name'].'#current');





?>
