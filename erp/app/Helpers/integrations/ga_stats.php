<?php

function shedule_import_google_analytics(){
    //https://github.com/akki-io/laravel-google-analytics/wiki/3.-Usage
    //https://github.com/akki-io/laravel-google-analytics/wiki/4.-Using-avaliable-methods
    // cloudtelecoms property_id - 339141022
    /*
    Config::set('laravel-google-analytics.property_id','339141022');
  $analyticsDataNetStream = LaravelGoogleAnalytics::dateRanges(AkkiIo\LaravelGoogleAnalytics\Period::days(30))
    ->metrics('active1DayUsers', 'active7DayUsers')
    ->dimensions('defaultChannelGrouping')
    ->metricAggregations(Google\Analytics\Data\V1beta\MetricAggregation::TOTAL, Google\Analytics\Data\V1beta\MetricAggregation::MINIMUM)
    ->get();
  
    */
    $websites = [
        '339141022' => 'Cloud Telecoms',
        '337695211' => 'Netstream',
    ];
    //$websites = [
    //    '337695211' => 'Netstream',
    //    '339141022' => 'Cloud Telecoms',
    //];
    foreach($websites as $property_id => $website){
        // netstream property_id - 337695211
        Config::set('laravel-google-analytics.property_id',$property_id);
    
    
        // build a query using the 'get()' method
        $date_start = \Illuminate\Support\Carbon::parse(date('Y-m-d',strtotime('monday last week')));
        $date_end = \Illuminate\Support\Carbon::parse(date('Y-m-d',strtotime('sunday last week')));
       // $date_end = \Illuminate\Support\Carbon::parse(date('Y-m-d'));
        $period = new AkkiIo\LaravelGoogleAnalytics\Period( $date_start, $date_end);
      
        $analyticsDataNetStream = LaravelGoogleAnalytics::dateRange($period)
        ->metrics('advertiserAdClicks')
        ->dimensions('defaultChannelGroup')
        ->metricAggregations(Google\Analytics\Data\V1beta\MetricAggregation::TOTAL)
        ->get();

        $data = [
          'start_date' => date('Y-m-d',strtotime('monday last week')),
          'end_date' => date('Y-m-d',strtotime('sunday last week')),
          'website' => $website,
          'ads_traffic' => 0,
          'social_organic_traffic' => 0,
          'organic_traffic' => 0,
          'direct_traffic' => 0,
        ];
        $lookup_data = [
          'start_date' => date('Y-m-d',strtotime('monday last week')),
          'end_date' => date('Y-m-d',strtotime('sunday last week')),
          'website' => $website,
        ];
        if(count($analyticsDataNetStream->table) > 0){
          
            foreach($analyticsDataNetStream->table as $row){
                if($row['defaultChannelGrouping'] == 'Organic Social'){
                    $data['social_organic_traffic'] = $row['active1DayUsers'];
                }
                if($row['defaultChannelGrouping'] == 'Organic Search'){
                    $data['organic_traffic'] = $row['active1DayUsers'];
                }
                if($row['defaultChannelGrouping'] == 'Direct'){
                    $data['direct_traffic'] = $row['active1DayUsers'];
                }
                
                $data['ads_traffic'] += $row['advertiserAdClicks'];
            }
        }
    
        \DB::table('crm_google_analytics')->updateOrInsert($lookup_data,$data);
    }
}