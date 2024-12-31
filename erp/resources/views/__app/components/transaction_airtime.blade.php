{!! Form::open(array("url"=> "airtime_form_post", "class"=>"form-horizontal","id"=> "buy_airtime_form")) !!}	

<div class="container-fluid m-0 p-0">
       
        <div class="card">
            <div class="card-header text-center text-white bg-primary">
               <h3>Buy Airtime</h3>
            </div>
            <div class="card-body">
                <div class="row mt-3">
                    <div class='col-2 align-self-center'>
                        <strong>Company</strong>
                    </div>
                    <div class='col-4'>
                        <input id="company" />
                    </div>
              
                    <div class='col-2 align-self-center reseller_user_col'>
                        <strong>Partner Customer</strong>
                    </div>
                    <div class='col-4 reseller_user_col'>
                        <input id="reseller_user" />
                    </div>
                </div>
                <div class="row mt-3" >
                    <div class='col-2 align-self-center'>
                        <strong>Type</strong>
                    </div>
                    <div class='col-4'>
                        <input id="type_once_off" type="checkbox"/>  <label for="type_once_off">Once Off </label>
                        <input id="type_monthly" type="checkbox"/>  <label for="type_monthly">Monthly </label>
                    </div>
               
                    <div class='col-2 align-self-center'>
                        <strong>Quantity</strong>
                    </div>
                    <div class='col-4'>
                        <input id="qty" />
                    </div>
                </div>
                <div class="row mt-3" >
                    <div class='col-2 align-self-center'>
                        <strong>Total</strong>
                    </div>
                    <div class='col-4'>
                        <input id="total" />
                    </div>
                    <div class='col-2 align-self-center'>
                        <strong>Monthly Total</strong>
                    </div>
                    <div class='col-4'>
                        <input id="monthly_total" />
                    </div>
                </div>
                
            </div>
        </div>
</div>


{!! Form::close() !!}
<style>
.reseller_user_col{
    display: none;    
}
</style>
<script type="text/javascript">
(function() {
    @if(!$is_admin)
        company = new ej.inputs.TextBox({
            placeholder: "Company",
            readonly: true,
            value: '{{ $account->company }}',
        });
        company.appendTo('#company');
    @else
        
		company = new ej.dropdowns.DropDownList({
			dataSource: {!! json_encode($partners) !!},
			fields: {groupBy: 'type', text: 'company', value: 'id', type: 'type'},
			placeholder: 'Company Name',
			ignoreAccent: true,
			allowFiltering: true,
			popupWidth: 'auto',
			filterBarPlaceholder: 'Type Company Name',
			change: function(e){
			
				var itemData = company.dataSource.filter(obj => {
				return obj.id === company.value
				})[0];
		        //console.log(itemData);
		        //console.log(itemData.type);
				if(company.value && itemData){
					
					if(itemData.type == 'reseller'){
						reseller_user_datasource();
						$(".reseller_user_col").show();
					}else{
						$("#reseller_user").val('');
						$(".reseller_user_col").hide();
					}
					
				}
					
				
			},
	        filtering: function(e){
				if(e.text == ''){
					e.updateData(company.dataSource);
				}else{ 
					var query = new ej.data.Query().select(['id','company']);
					query = (e.text !== '') ? query.where('company', 'contains', e.text, true) : query;
					e.updateData(company.dataSource, query);
				}
	        },
		});
		company.appendTo('#company');
    @endif
    
    var type_once_off = new ej.buttons.CheckBox({ 
        value:1,
        change: function(){ 
            if(type_monthly.checked){
                type_monthly.click();
            }
        },
    });
    type_once_off.appendTo('#type_once_off');
    var type_monthly = new ej.buttons.CheckBox({ 
        value:1,
        change: function(){
            if(type_once_off.checked){
                type_once_off.click();
            }
        },
    });
    type_monthly.appendTo('#type_monthly');
  

    qty = new ej.inputs.NumericTextBox({
        format: 'n0',
        min: 1,
        value:1,
    });
    qty.appendTo('#qty');
    total = new ej.inputs.TextBox({
        readonly: true,
        enabled:false,
    });
    total.appendTo('#total');
    monthly_total = new ej.inputs.TextBox({
        readonly: true,
        enabled:false,
    });
    monthly_total.appendTo('#monthly_total');

    reseller_users = new ej.dropdowns.DropDownList({
   
    fields: {text: 'company', value: 'id'},
    placeholder: 'Company Name',
    ignoreAccent: true,
    allowFiltering: true,
    filterBarPlaceholder: 'Type Company Name',
    filtering: function(e){
    	if(e.text == ''){
    		e.updateData(reseller_users.dataSource);
    	}else{ 
    		var query = new ej.data.Query().select(['id','company']);
    		query = (e.text !== '') ? query.where('company', 'contains', e.text, true) : query;
    		e.updateData(reseller_users.dataSource, query);
    	}
    },
    });
    reseller_users.appendTo('#reseller_user');
				
	function reseller_user_datasource(){
	    @if(session('role_id') == 11)
	    var partner_id = {{ session('account_id') }};
	    @else
	    var partner_id = company.value;
	    @endif
	    //console.log(partner_id);
	    reseller_users.value = null; 
		if(partner_id){
    		$.ajax({
    			url: '/form_reseller_users/'+partner_id,
    			dataType:"json",
    			success: function(data){
    			    //console.log(data);
    			    reseller_users.dataSource = data;
    			    reseller_users.dataBind();
    			}
    		});
		}else{
            reseller_users.value = null; 
            reseller_users.dataBind(); 
		}
	    
	}
	
    $('#buy_airtime_form').on('submit', function(e) {
        e.preventDefault();
        formSubmit("buy_airtime_form");
    });
})();
</script>