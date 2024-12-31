@php
$mapApiKey = env('GOOGLE_MAPS_API_KEY');
$mapWidth = '100%';
$mapHeight = '500px';
@endphp
<html>
<head>
<link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> 
<script src="//code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="/assets/libaries/bootstrap/bootstrap.bundle.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.css" rel="stylesheet">
<style>
 #address-input{
  display:none;
  
 }
 #fibrecontainer{
  display:none;
  
 }
 body,html{
  background:none;
 }
</style>
<script>
$(document).off("click", "#coveragebtn").on("click", "#coveragebtn", function(e){

 e.preventDefault();
 $("#fibrecontainer").css('display','block');
});
 
 function checkVariable() {
  if($("#address-input").length > 0){
   $("#address-input").appendTo("#addressdiv");
   $("#address-input").addClass("form-control border-secondary border-right-0 rounded-0");
   $("#address-input").attr("placeholder", "Enter your street address to check coverage in your area");
   $("#address-input2").hide();
   $("#address-input").show();
  }
  
  if( typeof map === 'undefined' || map === null ){
   setTimeout(checkVariable, 1000);
  }else{
   map.addListener('tilesloaded', function () { 
       map.setZoom(15);
  
  
 @if(!empty(request()->provision_id))
 $("#provision_text").show();
 @endif
    
   });
  }
 }
 $(document).ready(function(){
  
@if(!empty(request()->provision_id))
@endif
 
 });
 
 setTimeout(checkVariable, 1000);
</script>
</head>
<body>
 
@if(!empty(request()->provision_id))
<form action="/provision_service_post" id="iframe_form">
<div id="provision_text" style="text-align:center;font-weight:bold;font-family:Tahoma, Geneva, sans-serif;font-size:12px;display:none">
 <p>Please ensure to enter your exact address for fibre installation.</p>
</div>
@endif
<div class="container">
<div class="row no-gutters">
     <div class="col" id="addressdiv">
          <input class="form-control border-secondary border-right-0 rounded-0" placeholder="Enter your street address to check coverage in your area" id="address-input2" disabled>
     </div>
     <div class="col-auto">
          <button class="btn btn-primary border-left-0 rounded-0 rounded-right" type="button" id="coveragebtn">
             Check Coverage
          </button>
     </div>
</div>
<hr id="mapline">
</div>
@if(!empty(request()->provision_id))
<input type="hidden" name="provision_plan_id"  value="{{request()->provision_plan_id}}">
<input type="hidden" name="provision_id" value="{{request()->provision_id}}">
<input type="hidden" name="current_step" value="{{request()->current_step}}">
<input type="hidden" name="num_steps" value="{{request()->num_steps}}">
<input type="hidden" name="service_table" value="{{request()->service_table}}">
@endif

<script type="text/javascript" id="fixedwirelessscript">
 (function() {
 var ax = document.createElement('script');
 ax.id = 'fibrescript';
 ax.type = 'text/javascript';
 ax.async = true;
 ax.src = 'https://rcp.axxess.co.za/public/js/fibremapJs.php?key={{ $mapApiKey }}&width={{ $mapWidth }}&height={{ $mapHeight }}';
 var s = document.getElementById('mapline');
 s.parentNode.insertBefore(ax, s);
 })();
</script>

@if(!empty(request()->provision_id))
</form>
@endif
</body>
</html>