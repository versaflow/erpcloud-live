{!! Form::open(array("url"=> "pbx_number_change", "class"=>"form-horizontal","id"=> "pbx_number_change")) !!}	
<input type="hidden" name="id" value="{{ $id }}">

<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
                Numbers
            </div>
            <div class="card-body">
                
                <div class="row align-items-center d-flex justify-content p-2">
                   
                    <div class="col-auto">
                       {{ $number }}  {{ $gateway_name }}
                    </div>
                    
                    <div class="col">
			            <input name="spam_number" id="spam_number" type="checkbox" value="1" > Spam Number
                    </div>  
                    
                </div>
               <div class="row p-2">     
                    <div class="col">
                        <input id="number" />
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary float-right mr-2" ref="button">
Submit
</button>
</div>
{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
	var checkbox = { label: 'Remove Current File',checked: true };
	var spam_number = new ej.buttons.Switch(checkbox);
	spam_number.appendTo("#spam_number");
    number = new ej.dropdowns.DropDownList({
		fields: {groupBy:'prefix',text: 'text', value: 'id', },
        placeholder: 'Select new number',
        dataSource: {!! json_encode($numbers) !!},
        allowFiltering: true,
        popupHeight: '200px',
        filtering: function(e){
        if(e.text == ''){
        e.updateData(number.dataSource);
        }else{ 
        var query = new ej.data.Query().select(['text','id','prefix']);
        query = (e.text !== '') ? query.where('text', 'contains', e.text, true) : query;
        e.updateData(number.dataSource, query);
        }
        },
    });
    number.appendTo('#number');
});   
   

$('#pbx_number_change').on('submit', function(e) {
	e.preventDefault();
   formSubmit("pbx_number_change");
   
});
</script>