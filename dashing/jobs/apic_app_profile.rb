# Copyright Matt Day & Cisco 2015
# Please see the accompanying LICENSE file for license information

# This script pulls all the application profiles configured across the ACI infrastructure
# and reports them in a nice table 

require 'net/http'
require 'json'

# Fill in as needed:
ucsd_ip = '10.52.208.38'
ucsd_api_key = ''

uri = URI('http://'+ ucsd_ip +'/app/api/rest?opName=userAPIGetTabularReport&opData=%7Bparam0:%2290141%22,param1:%22%20accountName==\'APIC-BEDFONT\'%22,param2:%22APPLICATIONS-HEALTH-T52%22%7D')

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
		app_list = Hash.new(0)
		status = Hash.new(0)
		# Loop through all tenants
		response["serviceResult"]["rows"].each do |app|
			# UCS Director returns the tenant name in a strange way, parse this out with some
			# regex magic
                        tenant = app["_report_id"][/tenantName=='(.*?)'/,1]
			# Don't include common tenant
			if (tenant != "common") then
				# Truncate application name for display purposes:
				app_name = app["Application"]
				if (app_name.length > 15) then
					app_name = app_name[0,12] + "..."
				end
				# Store it in a hash to be fired off later (tenant + appName):
				status[app["Application"] + tenant] = {value: app_name, label: tenant }
			end
		end

		# Send event to dashing dashboard:
		send_event('apic_app_list', { items: status.values } )
	end
end
