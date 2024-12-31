{!! Form::open(array("url"=> "cdr_export", "class"=>"form-horizontal","id"=> "cdrexport")) !!}	
<input type="hidden" name="connection" value="{{$connection}}">
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Export Month
            </div>
            <div class="card-body">
                
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="export_date"> Date </label>
                    </div>
                    <div class="col-md-9">
                        <input id="export_date" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    
    export_date = new ej.dropdowns.DropDownList({
        dataSource: {!! json_encode($months) !!},
        showClearButton: true,
        ignoreAccent: true,
        popupHeight: '200px',
     
    });
    export_date.appendTo('#export_date');
    
  
});

$('#cdrexport').on('submit', function(e) {
	e.preventDefault();
   formSubmit("cdrexport");
   
});
</script>