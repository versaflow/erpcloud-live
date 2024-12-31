{!! Form::open(array("url"=> "setup_bank_connection", "class"=>"form-horizontal","files" => true , "parsley-validate"=>"","novalidate"=>" ","id"=> "bank_connection")) !!}		
<div class="card mt-2" >
    <div class="card-header">Setup Bank Connection</div>
    <div class="card-body">

    <div class="row">
        <div class="col-md-3 text-left align-self-center">
        <label for="from_date"> From Date </label>
        </div>
        <div class="col-md-9">
            <input id="from_date" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 text-left align-self-center">
        <label for="country_code"> Country Code </label>
        </div>
        <div class="col-md-9">
            <input id="country_code" />
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 text-left align-self-center">
        <label for="provider_code"> Provider Code </label>
        </div>
        <div class="col-md-9">
            <input id="provider_code" />
        </div>
    </div>

    
</div>
</div>
{!! Form::close() !!}
<script type="text/javascript">

$(document).ready(function() {
  
    
    country_code = new ej.dropdowns.DropDownList({
		fields: {text: 'text', value: 'value'},
        dataSource: {!! json_encode(get_country_codes_select()) !!},
        showClearButton: true,
        ignoreAccent: true,
        allowFiltering: true,
        popupHeight: '200px',
        filtering: function(e){
            if(e.text == ''){
                e.updateData(country_code.dataSource);
            }else{ 
                var query = new ej.data.Query().select(['text','value']);
                query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                e.updateData(country_code.dataSource, query);
            }
        },
        change: function(){
            if(country_code.value > ''){
                $.ajax({
                    url:'se_ajax_providers/'+country_code.value,
                    type:'post',
                    success:function(data){
                        provider_code.dataSource = data;
                        provider_code.enabled = true;
                        provider_code.dataBind();
                    }
                });
            }else{
                provider_code.dataSource = [];
                provider_code.enabled = false;
                provider_code.dataBind();
            }
        }
    });
    country_code.appendTo('#country_code');
    
    provider_code = new ej.dropdowns.DropDownList({
		fields: {text: 'text', value: 'value'},
        showClearButton: true,
        ignoreAccent: true,
        allowFiltering: true,
        popupHeight: '200px',
        enabled: false,
        filtering: function(e){
            if(e.text == ''){
                e.updateData(provider_code.dataSource);
            }else{ 
                var query = new ej.data.Query().select(['text','value']);
                query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
                e.updateData(provider_code.dataSource, query);
            }
        },
    });
    provider_code.appendTo('#provider_code');
    
    from_date = new ej.calendars.DatePicker({
		format: 'yyyy-MM-dd',
	});
    from_date.appendTo('#from_date');
    
});
$('#bank_connection').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("bank_connection");
});
</script>