
    function getLastDayOfNextMonth() {
        var today = new Date();
        var lastday =  new Date(today.getFullYear(), today.getMonth()+2, 0);
        lastday.setHours(23,0,0);
        return lastday;
    }
    
    function setDateGlobals(){
        var lastmonth_date = new Date();
        lastmonth_date.setDate(0);
        lastmonth_month = lastmonth_date.getMonth() + 1;
        lastmonth_year = lastmonth_date.getFullYear();
        
        
        
        var currentdate = new Date();
        cur_month = currentdate.getMonth() + 1;
        cur_year = currentdate.getFullYear();
        cur_day = currentdate.getDate();
        cur_week = moment().isoWeek();
        var lastYear = new Date();
        lastYear.setFullYear(lastYear.getFullYear() - 1);
        
        last_year = lastYear.getFullYear();
        
        lastYear.setFullYear(lastYear.getFullYear() - 1);
        
        last_year2 = lastYear.getFullYear();
        var last_3_years = [cur_year,last_year,last_year2];
        
        
        
        var yesterdaydate = new Date();
        yesterdaydate.setDate(currentdate.getDate() - 1);
        
        yesterday_month = yesterdaydate.getMonth() + 1;
        yesterday_year = yesterdaydate.getFullYear();
        yesterday_day = yesterdaydate.getDate();
        
        
        lastmonth_lastday = new Date(currentdate.getFullYear(), currentdate.getMonth(), 0);
        
        var date_today = new Date();
        var date_3days = new Date();
        var date_7days = new Date();
        var date_30days = new Date();
        var date_35days = new Date();
        var date_60days = new Date();
        var nextmonthlastday = getLastDayOfNextMonth();
        //console.log(nextmonthlastday);
        date_3days.setDate(currentdate.getDate() - 3);
        date_7days.setDate(currentdate.getDate() - 7);
        date_30days.setDate(currentdate.getDate() - 30);
        date_35days.setDate(currentdate.getDate() - 35);
        date_60days.setDate(currentdate.getDate() - 60);
        
        
        
        // Get the current date
        var currentDate = new Date();
        
        // Calculate the date six months ago
        var date_3months = new Date(currentDate);
        date_3months.setMonth(currentDate.getMonth() - 3);
        var date_6months = new Date(currentDate);
        date_6months.setMonth(currentDate.getMonth() - 6);
        var date_12months = new Date(currentDate);
        date_12months.setMonth(currentDate.getMonth() - 12);
        
        date_today.setHours(0,0,0);
        date_3days.setHours(0,0,0);
        date_7days.setHours(0,0,0);
        date_30days.setHours(0,0,0);
        date_35days.setHours(0,0,0);
        date_60days.setHours(0,0,0);
        date_3months.setHours(0,0,0);
        date_6months.setHours(0,0,0);
        date_12months.setHours(0,0,0);
    }
    
    setInterval(function () {
        setDateGlobals();
    }, 60000);
    setDateGlobals();