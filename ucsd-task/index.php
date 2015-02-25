<?php

/*
Copyright Matt Day & Cisco 2015
Please see LICENSE for licensing information

*/

include_once "ucsd_api.php";

# Print the html headers etc:
?>
<!doctype html>
<html><head>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css">
<title>Request Catalog Item</title>

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
</head>
<body>
<h1>Select Catalog Item</h1>
<?php

# Get a list of all available catalog items:
$query_string = 'opName=userAPIGetAllCatalogs&opData=%7B%&D';
$catalog_items = ucsd_api_call($query_string);

print '<table rows="3" style="margin-left: 10%; border: 1px solid #000080; margin-right: 10%; width: 80%;">'."\n";
$last_category = '';
# Loop through them all
foreach ($catalog_items->{'serviceResult'}->{'rows'} as $row) {
	# For now only pull out advanced catalog items:
	if ($row->{'Catalog_Type'} == "Advanced") {
		# Assume this catalog item is both supported and clear the output buffer:
		$supported = 1;
		# Get catalog item detail:
		$query_string = 'formatType=json&opName=userAPIWorkflowInputDetails&opData=%7Bparam0:%22'. \
			rawurlencode($row->{'Catalog_Name'}).'%22%7D';
		$catalog_item = ucsd_api_call($query_string);

		# Loop through the inputs this item takes and ensure they're all supported by this web app:
		foreach ($catalog_item->{'serviceResult'}->{'details'} as $input) {
			if (!ucsd_input_supported($input->{'type'})) {
				print '<!-- Warning: '.$row->{'Catalog_Name'}." unsupported due to ".$input->{'type'}." input field expected for ".$input->{'name'}." -->\n";
				$supported = 0;
				continue(2);
			}
		}
		$category = $row->{'Folder'};
		if ($category != $last_category) {
			$last_category = $category;
			# I'm unapologetically anal about this...
			$category = preg_replace('/VMWare/', 'VMware', $category);
			print '<tr><td colspan="3"><h2>Category: '.$category.'</h2></td></tr>';
		}
		# Print the icon and name:
		print '<tr><td style="padding-top: 1em; vertical-align: middle; text-align: center">'."\n";
		print '<img src="http://10.52.208.38/'.$row->{'Icon'}.'" /></td>'."\n";
		print '<td style="padding-top: 1em;"><h3 style="padding: 0; margin: 0;">'.$row->{'Catalog_Name'}.'</h3><p style="margin: 0; padding: 0;">'."\n";
		if ($row->{'Catalog_Description'} != '') {
			print $row->{'Catalog_Description'};
		}
		else {
			print 'No description available for this catalog entry';
		}
		print '</p></td>'."\n";
		print '<td style="padding-left: 1em; vertical-align: middle; text-align: center; min-width: 15em;">'."\n";
		print '<a href="service_info?catalog='.htmlspecialchars($row->{'Catalog_Name'}).'&amp;id='.htmlspecialchars($row->{'Catalog_ID'}).'" style="font-weight: bold; ">&gt;&gt;Request Service</a>'."\n";
		
		print '</td></tr>'."\n";
	}
}
print '</table>';
?>
<p style="margin-top: 1em; padding-left: 1em;">
<a href="request_status">&gt;&gt;See all Service Requests</a>
</p>
</body></html>
