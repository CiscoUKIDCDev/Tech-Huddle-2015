# Copyright Matt Day & Cisco 2015
# Please see the accompanying LICENSE file for license information

# This script reports the health scores for the bottom 5 nodes in an ACI
# infrastructure

require 'net/http'
require 'json'

# Fill in as needed:
ucsd_ip = '10.52.208.38'
ucsd_api_key = ''

show = 5

uri = URI('http://' + ucsd_ip + '/app/api/rest?opName=userAPIGetTabularReport&opData=%7Bparam0:%22551%22,param1:%22APIC-BEDFONT%22,param2:%22NODES-HEALTH-T52%22%7D')

SCHEDULER.every '30s' do
	tenant_count = 0

	# http request
	req = Net::HTTP::Get.new(uri.request_uri)

	# Add API key
	req.add_field "X-Cloupia-Request-Key", ucsd_api_key

	# Fetch Request
	res = Net::HTTP.start(uri.hostname, uri.port) {|http|
		http.request(req)
	}
	# If we get a HTTP 200 (OK) response then parse:
	if (res.code == "200") then
		# Parse JSON response (assume it's valid)
		response = JSON.parse(res.body)
		# hahes to store results
		node_list = Hash.new(0)
		status = Hash.new(0)
		# Loop through all tenants
		response["serviceResult"]["rows"].each do |node|
			# Add entry to hash:
			node_list[node["Node_Name"]] = node["Health_Score"]
		end
		shown = 0;
		# Sort nodes by health %
		node_list.keys.sort_by { |key| node_list[key] }.each do |key|
			status[key] = { label: key, value: (node_list[key].to_i) }
			shown += 1
			# Only show top 'n'
			if (shown > show) then
				break
			end
		end
		# Send event to dashing:
		send_event('apic_node_list', { items: status.values } )
	end
end
