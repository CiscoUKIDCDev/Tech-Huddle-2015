# Copyright Matt Day & Cisco 2015
# Please see the accompanying LICENSE file for license information

# This script grabs a sorted list of tenants from UCS Director and returns the top 'n'
# sorted by health (worst to best)

require 'net/http'
require 'json'

# Fill in as needed:
ucsd_ip = '10.52.208.38'
ucsd_api_key = ''

# How many tenants to show
show = 5;

uri = URI('http://' ucsd_ip + '/app/api/rest?opName=userAPIGetTabularReport&opData=%7Bparam0:%22551%22,param1:%22APIC-BEDFONT%22,param2:%22TENANTS-HEALTH-T52%22%7D')

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
		tenant_list = Hash.new(0)
		status = Hash.new(0)
		# Loop through all tenants
		response["serviceResult"]["rows"].each do |tenant|
			tenant_list[tenant["Tenant_Name"]] = tenant["Health_Score"]
		end
		# Sort tenants by %
		shown = 0
		tenant_list.keys.sort_by { |key| tenant_list[key] }.reverse.each do |key|
			status[key] = { label: key, value: (tenant_list[key].to_i) }
			shown += 1
			# Only show top 'n'
			if (shown == show) then
				break
			end
		end

		# Send event to dashing:
		send_event('apic_tenant_list', { items: status.values } )
	end
end
