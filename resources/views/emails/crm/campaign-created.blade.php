userId={{ $userUuid }}
email={{ $email }}
adserverName={{ config('app.adserver_name') }}
adserverId={{ config('app.adserver_id') }}
campaignName={{ $campaign->name }}
targetUrl={{ $campaign->landing_url }}
budget={{ $budget }}
startDate={{ $startDate }}
endDate={{ $endDate }}
advertiser=true
