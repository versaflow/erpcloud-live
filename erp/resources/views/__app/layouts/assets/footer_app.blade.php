

<script src="/assets/main/app.js?v=1"  data-turbolinks-eval="false" ></script>
<script src="/assets/main/dates.js?v=1"  data-turbolinks-eval="false" ></script>
<script src="/assets/main/common.js?v=1"  data-turbolinks-eval="false" ></script>
<script src="/assets/main/components.js?v=1" data-turbolinks-eval="false" ></script>
<script>
    // SUPPORTBOARD NAV


    function supportboard_conversations(){
        var url = window.location.pathname;
        var firstSegment = url.split('/')[1];
 
        window.sb_trigger_nav('http://helpdesk.telecloud.co.za');
        
        // if(firstSegment == 'support'){
        //     // window.sb_trigger_nav('sb-conversations');
        // }else{
        //     window.open('/support?area=conversations','_blank');
        // }
    }
    function supportboard_settings(){
        var url = window.location.pathname;
        var firstSegment = url.split('/')[1];
        
        if(firstSegment == 'support'){
            window.sb_trigger_nav('sb-settings');
        }else{
            window.open('/support?area=settings','_blank');
        }
    }
    function supportboard_users(){
        var url = window.location.pathname;
        var firstSegment = url.split('/')[1];
 
        if(firstSegment == 'support'){
            window.sb_trigger_nav('sb-users');
        }else{
            window.open('/support?area=users','_blank');
        }
    }
    function supportboard_articles(){
        var url = window.location.pathname;
        var firstSegment = url.split('/')[1];
 
        if(firstSegment == 'support'){
            window.sb_trigger_nav('sb-articles');
        }else{
            window.open('/support?area=articles','_blank');
        }
    }
    function supportboard_reports(){
        var url = window.location.pathname;
        var firstSegment = url.split('/')[1];
 
        if(firstSegment == 'support'){
            window.sb_trigger_nav('sb-reports');
        }else{
            window.open('/support?area=reports','_blank');
        }
    }
    
</script>
<script>
   $(document).ready(function(){
       
        $("#main-loading-container").addClass('d-none');
        $("#main-container").removeClass('d-none');
        $("#nav-container").removeClass('d-none');
        
        // if(window['navbar_header'])
        // window['navbar_header'].refresh();
        @if(!empty($grid_id))
        window['headertoolbar{{ $grid_id }}'].refresh();
        @endif
    })
</script>

<script>
   
    
    function showSpinner(reference = false){
        $(".sidebarbtn").attr("disabled","disabled");
      //  if(!reference && $('.sidebarformcontainer:visible:first').length > 0 ){
      //      reference  = "#"+$('.sidebarformcontainer:visible:first').attr('id');
      //  }
       
        if(reference){
            $(reference).busyLoad("show", {
                animation: "slide"
            });
        }else if ($(".e-dialog:visible")[0]){
            var spinnerel;
            var maxz; 
            $('.e-dialog:visible').each(function(){
                var z = parseInt($(this).css('z-index'), 10);
                if (!spinnerel || maxz<z) {
                spinnerel = this;
                maxz = z;
                }
            });
            $(spinnerel).busyLoad("show", {
                animation: "slide"
            });
        }else{
           
            $("#main-container").busyLoad("show", {
                animation: "slide"
            });
        }
    }
    
    function hideSpinner(reference = false){
        
                $(".sidebarbtn").removeAttr("disabled"); 
      // if(!reference && $('.sidebarformcontainer:visible:first').length > 0 ){
       //     reference  = "#"+$('.sidebarformcontainer:visible:first').attr('id');
      //  }
        if(reference){
            $(reference).busyLoad("hide", {
                animation: "slide"
            });
        }else if ($(".e-dialog:visible")[0]){
            var spinnerel;
            var maxz; 
            $('.e-dialog:visible').each(function(){
                var z = parseInt($(this).css('z-index'), 10);
                if (!spinnerel || maxz<z) {
                spinnerel = this;
                maxz = z;
                }
            });
        
            $(spinnerel).busyLoad("hide", {
                animation: "slide"
            });
        }else{
            $("#main-container").busyLoad("hide", {
                animation: "slide"
            });
        }
    }

    function showSpinnerWindow(){
        try {
            
            $("html").busyLoad("show", {
                animation: "slide"
            });
        }
        catch (e) {}
    }
    
    function hideSpinnerWindow(){
        try {
            $("html").busyLoad("hide", {
                animation: "slide"
            });
        }
        catch (e) {}
    }
    
    function validate_session(){
        $.get('validate_session', function(data) {
            if(data == 'logout'){
                window.location.href = '{{ url("/") }}';
            }
        });
    }
    function isMobile() {
        try{ document.createEvent("TouchEvent"); return true; }
        catch(e){ return false; }
    }
        
function currencyFormatter(currency, sign) {
  var sansDec = currency.toFixed(0);
  var formatted = sansDec.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  return sign + `${formatted}`;
}

function getLastDayOfNextMonth() {
var today = new Date();
  var lastday =  new Date(today.getFullYear(), today.getMonth()+2, 0);
  lastday.setHours(23,0,0);
  return lastday;
}

function getPreviousWorkday() {
    var date = new Date();
    const dayOfWeek = date.getDay();

  // If it's Sunday (0), return the date of the previous Friday
  if (dayOfWeek === 0) {
    date.setDate(date.getDate() - 2);
  }
  // If it's Monday (1), return the date of the previous Friday
  else if (dayOfWeek === 1) {
    date.setDate(date.getDate() - 3);
  }
  // For other weekdays, simply subtract one day
  else {
    date.setDate(date.getDate() - 1);
  }

  return date;
}

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
////console.log(nextmonthlastday);
date_3days.setDate(currentdate.getDate() - 3);
date_7days.setDate(currentdate.getDate() - 7);
date_30days.setDate(currentdate.getDate() - 30);
date_35days.setDate(currentdate.getDate() - 35);
date_60days.setDate(currentdate.getDate() - 60);
var date_last_week_day = getPreviousWorkday();


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


setInterval(function () {
var lastmonth_date = new Date();
lastmonth_date.setDate(0);
lastmonth_month = lastmonth_date.getMonth() + 1;
lastmonth_year = lastmonth_date.getFullYear();

var currentdate = new Date();
cur_month = currentdate.getMonth() + 1;
cur_year = currentdate.getFullYear();
cur_day = currentdate.getDate();
cur_week = moment().isoWeek();

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

date_3days.setDate(currentdate.getDate() - 3);
date_7days.setDate(currentdate.getDate() - 7);
date_30days.setDate(currentdate.getDate() - 30);
date_35days.setDate(currentdate.getDate() - 35);
date_60days.setDate(currentdate.getDate() - 60);
var date_last_week_day = getPreviousWorkday();

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
}, 60000);
</script>