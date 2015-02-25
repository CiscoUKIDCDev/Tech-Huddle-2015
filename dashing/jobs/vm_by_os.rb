# Copyright Matt Day & Cisco 2015
# Please see the accompanying LICENSE file for license information

# This script queries UCS Director at specific intervals to count the VMs across all clouds
# and list them by operating system. Takes a while to run.

require 'net/http'
require 'json'

# Fill in as needed:
ucsd_ip = '10.52.208.38'
ucsd_api_key = ''


uri = URI('http://'+ ucsd_ip +'/app/api/rest?opName=userAPIGetTabularReport&opData=%7Bparam0:%220%22,param1:%22All%20Clouds%22,param2:%22VMS-T0%22%7D')

SCHEDULER.every '120s' do
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
		# Assume JSON is valid:
		response = JSON.parse(res.body)
		# Store the VMs and status in a hash for sorting later
		vm_count = Hash.new(0)
		status = Hash.new(0)
	
		response["serviceResult"]["rows"].each do |vm|
			vm_count[vm["Guest_OS_Type"]] += 1
		end
	
	        vm_count.keys.sort_by { |key| vm_count[key] }.reverse.each do |key|
	        	status[key] = { label: key, value: (vm_count[key].to_i) }
	        end

		# Send the event to all dashboards
		send_event('vm_by_os', { items: status.values } )
	
	end
end
