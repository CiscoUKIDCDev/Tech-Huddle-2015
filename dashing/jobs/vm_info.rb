# Copyright Matt Day & Cisco 2015
# Based on a Dashing job task - see LICENSE for more information
# Please see the accompanying LICENSE file for license information

# This script queries UCS Director at specific intervals to provide various stats including
# average CPU usage and the % powered up

require 'net/http'
require 'json'

# Fill in as needed:
ucsd_ip = '10.52.208.38'
ucsd_api_key = ''

# Init the graph with some random values to make it look good :)
points = []
(1..10).each do |i|
  points << { x: i, y: rand(50) }
end

# UCS Director API Call URL:
uri = URI('http://'+ ucsd_ip +'/app/api/rest?opName=userAPIGetTabularReport&opData=%7Bparam0:%220%22,param1:%22All%20Clouds%22,param2:%22VMS-T0%22%7D')

SCHEDULER.every '20s' do
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
		vm_list = JSON.parse(res.body)

		# Some counters to start off with
		vm_count = 1
		counted_vm_count = 1
		total_cpu = 0
		vm_powered_on = 0
	
		# Iterate through list of VMs procured from above REST call
		vm_list["serviceResult"]["rows"].each do |vm|
			# Construct a cURL command to pull down specific info on the VM (the VM ID) - some VMs 
			# may not exist and this is an exceptionally lazy way to get the info (but functional)
			cmd = "http_proxy=\"\" curl -s -X \"GET\" \"http://" + ucsd_ip + "/app/api/rest?formatType=json&opName=userAPIGetHistoricalReport&opData=%7Bparam0:%22vm%22,param1:%22" + vm_count.to_s + "%22,param2:%22TREND-CPU-USAGE-(MHZ)-H0%22,param3:%22hourly%22%7D\" -H \"x-cloupia-request-key: " + ucsd_api_key + "\""
			# Again very lazy
			request = `#{cmd}`
			# Error checking due to above laziness:
			if (!request.match(/REMOTE_SERVICE_EXCEPTION/)) then
				history_graph = JSON.parse(request)
				# If we've gotten this far, it's possible there's no CPU stats (values) for the VM
				# we check here (.any?) and if it exists we add the current average CPU utilisation to the running
				# total
				if (history_graph["serviceResult"]["series"][0]["values"].any?) then
					total_cpu += history_graph["serviceResult"]["series"][0]["values"][0]["avg"]
					counted_vm_count += 1
				end
			end
			if (vm["Power_State"] == "ON") then
				vm_powered_on += 1
			end
			vm_count += 1
		end
		# Calculate mean CPU average and how many VMs are powered up (or at least giving us stats)
		powered_on_percent = vm_powered_on
		cpu_average = (total_cpu / counted_vm_count)
		# Round to nearest whole decimal, Ruby uses floating point logic and it can go wrong...
		cpu_average = cpu_average.round()
		
		# Send the events to the dashboards, the graph is sent as a set of x,y coordinates - plotting powered-up VMs
		# against CPU usage
		send_event('powered', { value: powered_on_percent })
		points << { x: (counted_vm_count / 10), y: cpu_average }
		send_event('convergence', points: points)
		
	end
end
