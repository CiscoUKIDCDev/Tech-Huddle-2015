<?php
/*
Copyright Matt Day

Please see LICENSE for more information

*/

# Not really an API calling function, more of a place that chucks in REST calls to UCSD
# and holds the API key etc
function ucsd_api_call ($query_string) {
	# UCSD API Key
	$ucsd_api_key = 'replace-me';
	# URL for requests
	$ucsd_api_url = 'http://<IP ADDRESS OF UCS DIRECTOR>/app/api/rest?';
	# cURL standard command (should replace with a library call):
	$curl_cmd = 'http_proxy="" curl -X "GET" -s ';
	$ucsd_api_cmd = ' -H "X-Cloupia-Request-Key: '.$ucsd_api_key.'"';
	$cmd = $curl_cmd.'"'.$ucsd_api_url.$query_string.'"'.$ucsd_api_cmd;
	$response = `http_proxy="" $cmd`;
	# Just return the raw JSON - will return 'null' if error in parsing, calling function should
	# take care of this
	return json_decode($response);
}

# A custom task in UCSD may require 1 or more inputs, this returns if they are supported or not
# Lazily written but does the job
function ucsd_input_supported($type) {
	if (($type == "gen_text_input") || ($type == "vm") || ($type == "vCPUCount")
		|| ($type == 'memSizeMB') || ($type == "no-multiVM") || ($type == 'nocatalog')) {
		return true;
	}
	return false;
}

# Returns an input form for each input type - for example returning a text input form for the UCSD
# gen_text_input type
function return_custom_form($input) {
	$type = $input->{'type'};
	switch ($type) {
		case gen_text_input:
			return create_plain_text_form($input);
		case vm:
			return create_vm_picker($input);
		case memSizeMB:
			return create_memory_picker($input);
		case vCPUCount:
			return create_cpu_picker($input);
		case multiVM:
			return create_multi_vm_picker($input);
		case catalog:
			return create_catalog_picker($input);
		default:
			return "<tr><td>".$input->{'label'}.":</td><td>Unsupported (type: ".$type.")</td></tr>";
	}
}

# Return a list of standard catalog items (i.e. VMs that can be created)
function create_catalog_picker($input) {
	$query_string = 'opName=userAPIGetAllCatalogs&opData=%7B%&D';
	$response = ucsd_api_call($query_string)->{'serviceResult'};
	$out = '<tr><td><label for="'.$name.'">'.$input->{'label'}.':&nbsp; &nbsp;</label></td>';
	$out .= '<td><select name="'.$name.'[]" id="'.$name.'">';
	foreach ($response->{'rows'} as $row) {
		if ($row->{'Catalog_Type'} == "Standard") {
			$out .= '<option value="'.$row->{'Catalog_ID'}.'">'.$row->{'Catalog_Name'}.'</option>';
		}
	}
	$out .= '</select>';
	return $out."\n";

}

# Return a list of VMs available within UCSD with the ability to select multiple items
# FIME: Currently broken (needs corresponding code in submit_api_request.php - perhaps
# split out as a function here)
function create_multi_vm_picker($input) {
	$name = 'multi_'.$input->{'name'};
	$query_string = 'opName=userAPIGetTabularReport&opData='.rawurlencode('{param0:"0",param1:"All Clouds",param2:"VMS-T0"}');
	$response = ucsd_api_call($query_string)->{'serviceResult'};
	$out = '<tr><td><label for="'.$name.'">'.$input->{'label'}.':&nbsp; &nbsp;</label></td>';
	$out .= '<td><select name="'.$name.'[]" id="'.$name.'" multiple>';
	foreach ($response->{'rows'} as $row) {
		$out .= '<option value="'.$row->{'VM_ID'}.'">'.$row->{'VM_Name'}.'</option>';
	}
	$out .= '</select><br />Hint: Cold ctrl (windows/linux) or cmd (Mac) to select multiple items</td></tr>';
	return $out."\n";
}

# Return a list of CPU options - 1-8
function create_cpu_picker($input) {
	$name = $input->{'name'};
	$out = '<tr><td><label for="'.$name.'">'.$input->{'label'}.':&nbsp; &nbsp;</label></td><td>';
	$out .= create_number_picker($name, 1, 8);
	$out .= '</td></tr>';
	return $out;
}

# Return a plain text input in most cases - as a kludge return a drop-down form to present a VM count
# (could probably be done in UCSD)
function create_plain_text_form($input) {
	$name = $input->{'name'};
	$out = '<tr><td><label for="'.$name.'">'.$input->{'label'}.':&nbsp; &nbsp;</label></td><td>';
	# Hack for a number picker for certain tasks... Could be done with custom input on UCSD or some other way to
	# match certain inputs
	if ((preg_match('/^Number of /', $input->{'label'}))) {
		$out .= create_number_picker($input->{'name'}, 1, 5);
	}
	# YAHack for IP addresses
	else if ((preg_match('/Gateway IP$/', $input->{'label'}))) {
		$out .= create_ip_address_field($input->{'name'});
	}
	else if ((preg_match('/^Prefix for subnets/', $input->{'label'}))) {
		$out .= create_subnet_picker($input->{'name'});
	}
	else {
		$out .= '<input type="text" name="'.$input->{'name'}.'" id="'.$input->{'name'}.'" />'."\n";
	}
	$out .= '</td></tr>';
	
	return $out."\n";
}

function create_ip_address_field ($name) {
	$out = '<input type="text" name="'.$name.'" id="'.$name.'" class="ip" />';
	return $out;
}

# Memory picker for various sizes of mb
function create_memory_picker($input) {
	$name = $input->{'name'};
	$out = '<tr><td><label for="'.$name.'">'.$input->{'label'}.':&nbsp; &nbsp;</label></td>';
	$out .= '<td><select name="'.$name.'" id="'.$name.'">';
	$out .= '<option value="256">256 MiB</option>';
	$out .= '<option value="512">512 MiB</option>';
	# Lazy...
	for ($i = 1024; $i <= 8192; $i += 256) {
		$out .= '<option value="'.$i.'">'.($i / 1024).' GiB</option>';
	}
	$out .= '<option value="16384">16 GiB</option>';
	$out .= '</select></td></tr>';
	return $out."\n";

}

# Single VM picker - lists all VMs in UCSD and allows a single choice
function create_vm_picker ($input) {
	$name = $input->{'name'};
	$query_string = 'opName=userAPIGetTabularReport&opData='.rawurlencode('{param0:"0",param1:"All Clouds",param2:"VMS-T0"}');
	$response = ucsd_api_call($query_string)->{'serviceResult'};
	$out = '<tr><td><label for="'.$name.'">'.$input->{'label'}.':&nbsp; &nbsp;</label></td>';
	$out .= '<td><select name="'.$name.'" id="'.$name.'">';
	foreach ($response->{'rows'} as $row) {
		$out .= '<option value="'.$row->{'VM_ID'}.'">'.$row->{'VM_Name'}.'</option>';
	}
	$out .= '</select></td></tr>';
	return $out."\n";
}

function create_subnet_picker ($name) {
	$out = ''; $start = 1; $end = 32;
	$out .= '<select name="'.$name.'" id="'.$name.'">';
	for ($i = $start; $i <=  $end; $i++) {
		$selected = '';
		if ($i == 24) {
			$selected = ' selected';
		}
		$out .= '<option'.$selected.' value="'.$i.'">/'.$i.'</option>';
	}
	$out .= '</select>';
	return $out."\n";
}


# Creates a number picker (e.g. how many VMs)
function create_number_picker ($name, $start, $end) {
	$out = '';
	$out .= '<select name="'.$name.'" id="'.$name.'">';
	for ($i = $start; $i <=  $end; $i++) {
		$out .= '<option value="'.$i.'">'.$i.'</option>';
	}
	$out .= '</select>';
	return $out."\n";
}

?>
