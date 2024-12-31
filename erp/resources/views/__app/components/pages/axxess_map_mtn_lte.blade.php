@php
$mapApiKey = env('GOOGLE_MAPS_API_KEY');
$mapWidth = '100%';
$mapHeight = '300px';
@endphp
<html>
<head>
<title>MTN LTE Coverage</title>
<link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> 
<script src="//code.jquery.com/jquery-3.1.1.min.js"></script>
<script src="/assets/libaries/bootstrap/bootstrap.bundle.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.js"></script>
<script src="/assets/iframeresizer/iframeResizer.contentWindow.js"></script>
<link href="https://cdn.jsdelivr.net/npm/busy-load/dist/app.min.css" rel="stylesheet">
<style>
 #address-input{
  display:none;
  width: 100% !important;
  
 }

 #mapline{display:none}
 body,html{
  background:none;
 }
#alertbox {
    background-image: linear-gradient(31deg,#19457e 0%,rgba(15,15,15,0.49) 100%);
    background-color: #19457e;
    color: #fff;
}

#alertbox .resultitem{

    color: #000;
}
#coveragebtn{
 background-color: #28a745;
 font-weight: bold;
 color: #fff;

}
#address-input{
    border: 2px solid #28a745;
    border-color: #28a745 !important;
    font-weight: bold;
}
 .form-control:focus {

    box-shadow: none;
}
.btn.focus, .btn:focus {
    box-shadow: none;
}
</style>
<script>
$(document).off("click", "#coveragebtn").on("click", "#coveragebtn", function(e){

 e.preventDefault();
  if (IsplaceChange == false) {
    $("#address-input").val('');
    
				   $('#alertbox').html("<b>Please select an address from the provided results.</b>");
				   $('#alertbox').show();
    return false;
  }
 $("#mtnfixedltechoicecontainer").css('display','block');
 
  if ($("#j-input").val() == 0) {
    $("#address-input").val('');
    
				   $('#alertbox').html("<b>Please select an address from the provided results.</b>");
				   $('#alertbox').show();
    return false;
  }


 var coverage_url = '/check_lte_coverage';

 var data = {
  provider: 'mtn',
  latlonginput:  $("#latlong-input").val(),
  addressinput:  $("#address-input").val(),
  bbox:  $("#bbox-input").val(),
  width:  $("#width-input").val(),
  height:  $("#height-input").val(),
  ico:  $("#i-input").val(),
  jco:  $("#j-input").val(),
 };

 	$.ajax({
			   url: coverage_url,
			   data: data,
			   type: 'post',
      beforeSend: function() {
        $('#alertbox').html('<b>Verifying address...');
  				   $('#alertbox').show();
      },
			   success: function(data){
			   
			    if(data.lat != 0 && data.long != 0){
       marker.setPosition(new google.maps.LatLng(parseFloat(data.lat), parseFloat(data.long)));
       map.setCenter(new google.maps.LatLng(parseFloat(data.lat), parseFloat(data.long)));
       map.setZoom(15);
			    }
			   

				   $('#alertbox').html(data.message);
				   $('#alertbox').show();
				}
			});

});
 
 function checkVariable() {
  if($("#address-input").length > 0){
   $("#address-input").appendTo("#addressdiv");
   $("#address-input").addClass("form-control border-secondary border-right-0 rounded-0");
   $("#address-input").attr("placeholder", "Enter your street address");
   $("#address-input").attr("autocomplete", "off");
   $("#address-input2").hide();
   $("#address-input").show();
  }
  
  if( typeof map === 'undefined' || map === null ){
   setTimeout(checkVariable, 1000);
  }else{
   map.addListener('click', function () { 
    setTimeout(function(){ $("#coveragebtn").click(); }, 1000);
   });
   google.maps.event.addListener(autocomplete, 'place_changed', function () {
      var place = autocomplete.getPlace();
   
      IsplaceChange = true;
     
      $("#coveragebtn").click();
   });
   
   $("#address-input").keydown(function () {
      IsplaceChange = false;
   });
  
  }
 }
 $(document).ready(function(){
   $("#alertbox").hide();
 
 });
 
 setTimeout(checkVariable, 1000);
</script>
</head>
<body>

<div style="width: 70%;margin: 0 auto;">
<div class="row no-gutters">
     <div class="col" id="addressdiv">
          <input class="form-control border-secondary border-right-0 rounded-0" placeholder="Enter your street address to check coverage in your area" id="address-input2" disabled>
     </div>
     <div class="col-auto">
          <button class="btn border-left-0 rounded-0 rounded-right" type="button" id="coveragebtn">
             Check Coverage
          </button>
     </div>
     
</div>
<hr id="mapline">
<div id="alertbox" class="my-2 p-4"></div>

</div>

<script type="text/javascript" id="mtn-fixed-lte-script">
 (function() {
 var ax = document.createElement('script');
 var mapApiKey = '{{$mapApiKey}}';
 var mapWidth = '70%';
 var mapHeight = '400px';
 ax.id = 'fibrescript';
 ax.type = 'text/javascript';
 ax.async = true;
 ax.src = 'https://rcp.axxess.co.za/public/js/mtnFixedLteCoverageJs.php?key='+mapApiKey+'&width='+mapWidth+'&height='+mapHeight;
 var s = document.getElementById('mapline');
 s.parentNode.insertBefore(ax, s);
 })();
</script>
<style>#mtn-fixed-lte-map{margin: 0 auto;}</style>

</body>
</html>