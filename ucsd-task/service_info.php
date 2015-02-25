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
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap-theme.min.css">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
<script src="jquery.caret.js" type="text/javascript"></script>
<script src="jquery.ipaddress.js" type="text/javascript"></script>
<script type="text/javascript">
$(function(){
	$('.ip').ipaddress();
});
</script>
<style>
.ip_container {
	  border: 1px inset #999;
}
.ip_octet {
	  border: 0;
	    text-align: center;
	      width: 2em;
}

</style>
<title>Request Catalog Item</title>
</head>
<body>
<h1>Customise Service Request</h1>
<?php

# Get a list of all available catalog items:
$query_string = 'opName=userAPIGetAllCatalogs&opData=%7B%&D';
$catalog_items = ucsd_api_call($query_string);

# Loop through them all
foreach ($catalog_items->{'serviceResult'}->{'rows'} as $row) {
	# For now only pull out advanced catalog items:
	# Lazily done as a retcon of old code - could request directly (probably)
	if (($row->{'Catalog_Type'} == "Advanced") && ($row->{'Catalog_Name'} == $_GET['catalog']) && ($_GET['id'] == $row->{'Catalog_ID'})) {
		print '<div style="border: 1px solid #000080; margin-left: 10%; margin-right: 10%; padding: 1.5em; margin-bottom: 2em;">';
		# Print the icon and name:
		print '<h3><img src="http://10.52.208.38/'.$row->{'Icon'}.'" /> '.$row->{'Catalog_Name'}.'</h3>'."\n";
		print '<p>'.$row->{'Catalog_Description'}.'</p>';
		# HTML Form:
		print '<form action="submit_api_request" method="post">'."\n";
		print '<input type="hidden" name="Catalog_ID" value="'.$row->{'Catalog_ID'}.'" />'."\n";
		print '<input type="hidden" name="Catalog_Name" value="'.$row->{'Catalog_Name'}.'" />'."\n";
		# Format in a table:
		print '<table>';
		# Get catalog item detail:
		$query_string = 'formatType=json&opName=userAPIWorkflowInputDetails&opData=%7Bparam0:%22'. \
			rawurlencode($row->{'Catalog_Name'}).'%22%7D';
		$catalog_item = ucsd_api_call($query_string);
		# Loop through inputs required
		$rows = 0;
		foreach ($catalog_item->{'serviceResult'}->{'details'} as $input) {
			print return_custom_form($input);
			$rows++;
		}
		print '<tr><td>&nbsp;</td><td>&nbsp;</td><td><input type="submit" value="&gt;&gt; Submit" /></td</tr>';
		print '</table></form></div>';
	}
}

print '<a href="/ucsd-task/">&lt;&lt;Go Back to main catalog</a>';

?>
</body></html>
