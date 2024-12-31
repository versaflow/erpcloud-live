@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif

@section('content')



<div  class="container-fluid m-0">
<form id="transactionForm" action="/{{ $menu_route }}/save" class="form-horizontal" >
	<input type="hidden" id="id" name="id" value="{{ $document->id }}" >

	<input type="hidden" id="status" name="status" value="{{ $document->status }}"> 
	<input type="hidden" id="reversal_id" name="reversal_id" value="{{ $document->reversal_id }}">
	<input type="hidden" id="account_id" name="account_id" value='{{ $account_id }}' >

	<input type="hidden" id="account_type" value='{{ $account_type }}' >
	<input type="hidden" id="document_currency" name="document_currency" value="{{ $document_currency }}">

	<input type="hidden" id="vat_enabled" value="{{ $vat_enabled }}">
	<input type="hidden" id="send_email_on_submit" name="send_email_on_submit" value="0">
	<input type="hidden" id="send_approve_on_submit" name="send_approve_on_submit" value="0">
@if(!request()->ajax() || !empty($document->id))
	<div class="flex flex-wrap justify-between">
		<div>
			<h3 class="text-2xl font-bold text-left text-black">{{ ($document->doctype) ? $document->doctype."# ".$document->id : 'New '.$doctype }}</h3>
		</div>
	</div>
@endif
<div class="row mb-0">
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Company <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="company"class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
		<br>
		<div class="relative w-full text-left serviceaccountrow d-none">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Partner Customer <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="reseller_user" name="reseller_user" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
	</div>
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Document Date <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="docdate" name="docdate" required="true" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
	</div>
		

	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Document Type <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="doctype" name="doctype" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
		
		@if(session('role_level') == 'Admin')
		<div class="relative w-full text-left cnr_row d-none">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Credit note reason <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="credit_note_reason" name="credit_note_reason" value='{{ $document->credit_note_reason }}' class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
		@endif
	</div>
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Reference <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="reference" name="reference" value='{{ $document->reference }}' class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
	</div>
	
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Bill frequency <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
					<select name="bill_frequency" id="bill_frequency" class="bill_frequency font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					<option value="0"></option>
					<option value="1">Monthly/Once off</option>
					<option value="12">Annualy</option>
					</select>
					</div>
				</div>
		</div>
	</div>
</div>

@if(session('role_level') == 'Admin')
<hr class="border my-1">

<div class="row mb-0">
	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Salesman <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="salesman_id" name="salesman_id" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
	
	</div>
	

	<div class="col">
		<div class="relative w-full text-left">
			<label class="flex text-sm not-italic items-center font-medium text-gray-800 whitespace-nowrap justify-between">
				<div>Commitment Date <span class="text-sm text-red-500"> * </span></div>
				</label>
				<div class="flex flex-col mt-1">
					<div class="relative rounded-md shadow-sm font-base">
						<input type="text" id="commitment_date" name="commitment_date" class="font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
				</div>
		</div>
	</div>
	<div class="col">
	</div>
</div>
@endif
<hr class="border">	


	<div class="row mt-2">
		<div class="table-responsive">
			<table class="table item-table min-w-full mb-0">
			<thead class="bg-white border border-gray-200 border-solid">
				<tr>
					<th style="width: 38%">Item</th>
					<th class="text-right" style="width: 8%">Quantity</th>
					<th class="text-right">Price</th>
					@if(!$remove_tax_fields)
					<th class="text-right">Price Incl</th>
					@endif
					<th class="text-right">Total</th>
					@if(!$remove_tax_fields)
					<th class="text-right line_tax">Total Incl</th>
					@endif
					<th></th>
				</tr>
			</thead>
			<tbody id="lines">
			@foreach ($document_lines as $i => $line)
			<tr class="clone box-border bg-white border border-gray-200 border-solid rounded-b">
				<td>
					<div class="e-input-group product-input-group">
					<input type="text" name="product_category_id[]" value="{{ $line->product_category_id }}"  class="categories font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div><br>
					<div class="e-input-group product-input-group">
					<input type="text" name="product_id[]" value="{{ $line->product_id }}"  class="products font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div><br>
					<div class="e-input-group product_description_div">
					<textarea name='description[]' class="form-control description">{{ $line->description }}</textarea>
					</div>
					
					@if($is_supplier)
					<br><div class="e-input-group ledger_account-input-group">
					<input type="text" name="ledger_account_id[]" value="{{ $line->ledger_account_id }}"  class="ledger_accounts font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
					@endif
					@if($db_table == 'crm_supplier_documents')
					<br><div class="cdr_destinations_div e-input-group">
					<input type="text" name="cdr_destination[]" value="{{ $line->cdr_destination }}"  class="cdr_destination font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
					@endif
					@if($db_table == 'crm_documents')
					<br><div class="contract_period_div">
					<input type="text" name="contract_period[]" value="{{ $line->contract_period }}" class="contract_period font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					</div>
					@endif
				
				</td>
			
				<td>
					<input type="text" name="qty[]" value="{{ $line->qty }}" class="qty  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
				</td>
			
				<td>
					<input type="hidden" name="full_price[]" value="{{ currency($line->full_price) }}" class="full_price  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					<input type="text" name="price[]" value="{{ currency($line->price) }}" class="price  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
				</td>
				@if(!$remove_tax_fields)
				<td>
					@if($vat_enabled)
					<input type="text" value="{{ currency($line->price*1.15) }}" class="price_incl  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					@else
					<input type="text" value="{{ currency($line->price) }}" class="price_incl  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
					@endif
				</td>
				@endif
			
				<td class="total">
					<input type="text" value="{{ currency($line->qty * $line->price) }}" class="line_total  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
				
				</td>
				@if(!$remove_tax_fields)
				<td class="line_tax">
					<input type="text" class="line_total_tax  font-base block w-full sm:text-sm border-gray-200 rounded-md text-black focus:ring-primary-400 focus:border-primary-400">
				</td>
				@endif
				<td>
				<span href="javascript:void(0)" title="Remove" class="remove fas fa-trash" /></span>
				</td>
			</tr>
			@endforeach
			</tbody>
		</table>
		<button id="cloneButton" type="button" class="btn btn-light btn-lg w-100 text-sm"><i class="far fa-plus-circle"></i>Add new item</button>
		</div>
	
	</div>
	
	

<hr class="border">		


  <div class="row">
    <div class="col-md-6">
      <!-- First 2/4 content -->
      <div class=" p-3">
			<div class="row mb-3" style="display:none" id="coveragerow">
				<div class='col-12'>
					<strong>Coverage Address</strong>
				</div>
				<div class='col-12 mt-1'>
					<a href="{{ url('/coverage_maps') }}" target="_blank" class="e-btn e-light">Check Coverage</a>
					<input type="text" id="coverage_address" name="coverage_address" value='{{ $document->coverage_address }}' class="form-control">
				</div>
				<div class='col-12 mt-3'>
					<strong>Confirmation</strong>
				</div>
				<div class='col-12 mt-1'>
					<input id="coverage_confirmed" name="coverage_confirmed" type="checkbox" value="1" > I confirm that the provided address has coverage. 
				</div>
			</div>
			
			<div class="row mt-0">
			<div class="col">
			<h5>Notices</h5>
			<hr>
			<p>{!! nl2br($document_footer) !!}</p>
			</div>
			</div>
      </div>
    </div>
    <div class="col-md-2">
      <!-- 1/4 gap -->
      <div class="w-100 bg-light">
        <!-- Gap -->
      </div>
    </div>
    <div class="col-md-4 ">
      <!-- Second 1/4 content -->
      <div class="bg-white">
		<div class="row mt-3" id="totals">
			<div class="col p-4 text-right" id="document_totals">
				@if(!$is_supplier)
				<div class="row  mt-1 text-right">
					<div class="col text-right">
					<button id="add_delivery" class="e-btn" type="button">Add Delivery</button>
					</div>
				</div>
		
				@endif
			
		
				
				
				<div class="row mt-3">
					<div class='col col-md-8 '>
						<span class="lead text-uppercase fw-bold text-sm">Sub total</span>
					</div>
					<div class='col col-md-4'>
					<input type="text" id="subtotal" name="subtotal" value="{{ currency($document->total - $document->tax) }}" class="form-control" >
					</div>
				</div>
				
				@if(!$remove_tax_fields)
				<div class="row">
					<div class='col col-md-8 '>
						<span class="lead text-uppercase fw-bold text-sm">Tax</span>
					</div>
					<div class='col col-md-4'>
					<input type="text" id="tax" name="tax" value="{{ currency($document->tax) }}" class="form-control" >
					</div>
				</div>
				@endif
				
			
			
				<div class="row">
					<div class='col col-md-8 '>
						<span class="lead text-uppercase fw-bold text-sm">Grand Total</span>
					</div>
					<div class='col col-md-4'>
					<input type="text" id="total" name="total" value="{{ currency($document->total) }}" class="form-control" >
					</div>
				</div>
				
				
				<div class="row monthlyrow ">
					<div class='col col-md-8 '>
						<span class="lead text-uppercase fw-bold text-sm">Monthly Total Thereafter</span>
					</div>
					<div class='col col-md-4'>
					<input type="text" id="monthly_total" name="monthly_total" class="form-control" >
					</div>
				</div>
				
				
				
				
			</div>
		</div>
      </div>
    </div>
  </div>



	

	</form>
</div>
@endsection
@push('page-scripts')

<script type="text/javascript">

		@if(!$is_supplier)
		@if($document->doctype == 'Credit Note Draft' || $doctype == 'Credit Note Draft')
		$("#form_toolbar_submit_approve_btn").removeClass('d-none');
		@if(session('role_level') == 'Admin')
		$(".cnr_row").removeClass('d-none');
		@endif
		@else
	
		$("#form_toolbar_submit_email_btn").removeClass('d-none');
		@endif
		@endif
		
		$("#form_toolbar_submit_btn").text('Submit');


//// Initial Load
	currency_symbol = 'R';
	
	@if(!empty($document_currency) && $document_currency !='ZAR')
	setTimeout(function() {update_currency_input_formats('{{$document_currency}}');}, 500);
	@endif
	
	function update_currency_input_formats(currency){
		$("#document_currency").val(currency);
    	var format = 'R ###########.00';
    	
		currency_symbol = 'R';
		if(currency == 'USD'){
	    	var format = '$ ###########.00';
			currency_symbol = '$';
			$("#vat_enabled").val(0);
		}
		
		subtotal_input.format = format;
		subtotal_input.dataBind();
		
		@if(!$remove_tax_fields)
		tax_input.format = format;
		tax_input.dataBind();
		@endif
		
		total_input.format = format;
		total_input.dataBind();
		
		monthly_total_input.format = format;
		monthly_total_input.dataBind();
			
		$('.price').each(function(index) {
			window["price_input" + index].format = format;
			window["price_input" + index].dataBind();
			
			@if(!$remove_tax_fields)
			window["price_incl_input" + index].format = format;
			window["price_incl_input" + index].dataBind();
			@endif
			
			window["line_total_input" + index].format = format;
			window["line_total_input" + index].dataBind();
			
			@if(!$remove_tax_fields)
			window["line_total_tax_input" + index].format = format;
			window["line_total_tax_input" + index].dataBind();
			@endif
		
		});
		
		$('.categories').each(function(index) {
			if(currency == 'USD'){
				window["category_input" + index].dataSource= window.categories_usd;
				window["category_input" + index].dataBind();
			}else{
				window["category_input" + index].dataSource= window.categories;
				window["category_input" + index].dataBind();
			}
		});
	}
	
	window.doctypes = {!! json_encode($doctypes) !!};
	window.lead_doctypes = {!! json_encode($lead_doctypes) !!};
	window.accounts = {!! json_encode($accounts) !!};
	window.products = {!! json_encode($products) !!};
	window.categories = {!! json_encode($categories) !!};
	window.categories_usd = {!! json_encode($categories_usd) !!};
	window.tld_prices = {!! json_encode($tld_prices) !!};
	
	@if($is_supplier)
	window.ledger_accounts = {!! json_encode($ledger_accounts) !!};
	@endif
	
//// Document Ready

	$(document).off('change', '.domain_tld').on('change', '.domain_tld', function() {
	
		var domain_tld =  $(this).val();
		var inputEl = $(this);
		var priceEl = 	inputEl.closest('.clone').find('.price');
		var fullpriceEl = 	inputEl.closest('.clone').find('.full_price');
		var productEl = 	inputEl.closest('.clone').find('.products');
		
	
		
		$(window.tld_prices).each(function(index, el) {
			
			if(el.tld == domain_tld){
			
			
				if(company.itemData.type == "reseller"){
				
					//	inputEl.closest('.clone').find('.price').val(el.wholesale_price).trigger("change");
					priceEl[0].ej2_instances[0].value = el.wholesale_price;
					fullpriceEl.val(el.wholesale_full_price);
					productEl[0].ej2_instances[0].itemData.price = el.wholesale_price;
					productEl[0].ej2_instances[0].itemData.full_price = el.wholesale_full_price;
				}else{
					
					//inputEl.closest('.clone').find('.price').val(el.retail_price).trigger("change");
					priceEl[0].ej2_instances[0].value = el.retail_price;
					fullpriceEl.val(el.retail_full_price);
					productEl[0].ej2_instances[0].itemData.price = el.retail_price;
					productEl[0].ej2_instances[0].itemData.full_price = el.retail_full_price;
				}
			}
		});
	});
	
	$(document).ready(function() {
		
	
		
		ej.base.enableRipple(true);
		var supplier_document = '{{ $is_supplier }}';
		
		@if(session('role_level') == 'Admin')
		product_bundles = new ej.dropdowns.DropDownList({
			dataSource: {!! json_encode($product_bundles) !!},
			placeholder: 'Product Bundles',
			fields: {text: 'name', value: 'id'},
			filterBarPlaceholder: 'Type Product Bundles',
			@if($account_id)
			enabled: true,
			@else
			enabled: false,
			@endif
			change: function(e){
				if(product_bundles.value > ''){
					set_bundle_lines(function(){
							var rowCount = $('#lines tr').length;
							
							if(rowCount == 1){
							$('.remove').hide();
							}else{
							$('.remove').css("display", "flex");
							}
							
							$('.products').each(function(index) {
							$(this).trigger("change");
							});
							product_bundles.value = null;
							product_bundles.dataBind();
							if(window['product_input0'].value == null){
							setTimeout(function(){$('#lines tr:first').find('.remove').trigger('click')},500);
						}	
					});
				
				}
			},
		});
		product_bundles.appendTo('#product_bundles');
		
		function set_bundle_lines(callback) {
		
			var product_bundle_details = {!! json_encode($product_bundle_details) !!}
			if(product_bundles.value > ''){
				////console.log(product_bundle_details);
				$.each(product_bundle_details,function(i, el){
					if(el.product_bundle_id == product_bundles.value){
						if(el.product_id && el.qty){
						$.when(insert_line()).then(set_line_inputs(true,el.product_id,el.qty));
						}
					}
				});
				
				
			
				callback();
			}
		}
    	@endif
		
    	
	    coverage_address = new ej.inputs.TextBox({
			placeholder: "Coverage Address as entered on coverage map",
			@if(!empty($document->coverage_address))
			value: '{{$document->coverage_address}}',
			@endif
		});
	    coverage_address.appendTo('#coverage_address');
	    
		@if (!empty($document->coverage_confirmed))
		var coverage_checkbox = { name: 'coverage_confirmed', label: 'I confirm that the provided address has coverage.', checked: true };
		@else
		var coverage_checkbox = { name: 'coverage_confirmed', label: 'I confirm that the provided address has coverage.' };
		@endif
		
		
		var coverage_confirmed = new ej.buttons.Switch(coverage_checkbox);
		coverage_confirmed.appendTo("#coverage_confirmed");
    
		@if(session('role_level') == 'Admin')
		var salesman_id = new ej.dropdowns.DropDownList({
			dataSource: {!! json_encode($salesman_ids) !!},
			fields: {text: 'full_name', value: 'id'},
			placeholder: 'Salesman',
			ignoreAccent: true,
			allowFiltering: true,
			filterBarPlaceholder: 'Select User',
			@if(!empty($document->salesman_id))
			value: {{$document->salesman_id}},
			@endif
			@if(!check_access('1,2,7'))
			disabled: true,
			@endif
	        filtering: function(e){
				if(e.text == ''){
					e.updateData(company.dataSource);
				}else{ 
					var query = new ej.data.Query().select(['id','full_name']);
					query = (e.text !== '') ? query.where('full_name', 'contains', e.text, true) : query;
					e.updateData(company.dataSource, query);
				}
	        },
		});
		
		salesman_id.appendTo('#salesman_id');
		
		
		
	

		@endif
		
		
		bill_frequency = new ej.dropdowns.DropDownList({
			allowFiltering: false,
			@if(!empty($document->bill_frequency))
			value: '{{$document->bill_frequency}}',
			@else
			value: '0',
			@endif
	        change: function(e){
	        	//console.log('bill_frequency change');
				update_product_prices();
	        },
		});
		bill_frequency.appendTo('#bill_frequency');
		
		
	
	
		company = new ej.dropdowns.DropDownList({
			dataSource: window.accounts,
			fields: {groupBy: 'type', text: 'company', value: 'id', type: 'type'},
			placeholder: 'Company Name',
			ignoreAccent: true,
			allowFiltering: true,
			popupWidth: 'auto',
			filterBarPlaceholder: 'Type Company Name',
			@if($account_id)
			value: {{ $account_id }},
			@endif
			@if(check_access('21'))
			enabled: false,
			@endif
			
			change: function(e){
					
				var itemData = company.dataSource.filter(obj => {
				return obj.id === company.value
				})[0];
		
				if(company.value && itemData){
					@if(session('role_level') == 'Admin')
					product_bundles.enabled = true;
					@endif
				
					update_currency_input_formats(itemData.currency);
					
					if($("#account_id").val() > ''){
					
						$('.products').each(function(index) {
							window["product_input" + index].value = null;
							window["product_input" + index].refresh();
						});
					}
					
				
					$("#account_id").val(company.value);	
					
					load_products();
			
					doc_doctype.enabled = true;
				
					$("#account_type").val(itemData.type);
					if(itemData.type == 'supplier'){
						$("#vat_enabled").val(itemData.taxable);
					}
					
					if(itemData.type == 'customer'){
						doc_doctype.dataSource = window.doctypes;
					}
					//console.log(itemData);
					if(itemData.type == 'lead'){
						doc_doctype.dataSource = window.lead_doctypes;
						doc_doctype.value = 'Quotation';
					}
					if(itemData.type == 'reseller'){
						doc_doctype.dataSource = window.doctypes;
						reseller_user_select();
					}else{
						try{
							reseller_users.destroy();
						}catch(e){}
						$("#reseller_user").val('');
						$(".serviceaccountrow").addClass('d-none');
						input_required_check();
					}
					
					doc_reference.enabled = true;
				
					
				}else{
					
					@if(session('role_level') == 'Admin')
					product_bundles.enabled = false;
					@endif
					doc_doctype.enabled = false;
					doc_reference.enabled = false;
				
				}
			},
			created: function(e){
				
				var itemData = company.dataSource.filter(obj => {
				return obj.id === company.value
				})[0];
				
				if(company.value && itemData){
					if(itemData.type == 'reseller'){
						reseller_user_select();
					}
			
				
					$("#account_id").val(company.value);	
					
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
	
	    doc_docdate = new ej.calendars.DatePicker({
	    	format: 'yyyy-MM-dd',
			value: '{{ ($document->docdate) ? $document->docdate : date("Y-m-d") }}',
			@if(session('role_level') != 'Admin')
			enabled: false,
			@endif
			@if($is_supplier == 0)
			//min: '{{ ($document->docdate) ? $document->docdate : date("Y-m-d") }}',
			@endif
	    });
	    doc_docdate.appendTo('#docdate');
	    
		@if(session('role_level') == 'Admin')
		    doc_commitment_date = new ej.calendars.DatePicker({
		    	format: 'yyyy-MM-dd',
				value: '{{ ($document->commitment_date) ? $document->commitment_date : '' }}',
				@if($is_supplier == 0)
				min: '{{ ($document->commitment_date) ? $document->commitment_date : '' }}',
				@endif
		    });
		    doc_commitment_date.appendTo('#commitment_date');
		@endif
			
		doc_doctype = new ej.dropdowns.DropDownList({
			placeholder: 'Select document type',
			dataSource: window.doctypes,
			value: '{{ ($document->doctype) ? $document->doctype : $doctype }}',
		//	readonly: true,
			@if($document->doctype && $document->doctype != 'Credit Note Draft')
				enabled: false,
			@endif
			change: function(){
				$("#doctitle").html(doc_doctype.value);
				input_required_check();
				
        		var approve_manager = {{ (check_access('1,2,7')) ? 1: 0 }};
				if(doc_doctype.value == 'Quotation' || doc_doctype.value == 'Order' || doc_doctype.value == 'Supplier Order'){
					@if(!empty($document->id))
						$(".docEmailBtn").removeClass("hidebtn");
					@else
						$(".docEmailBtn").addClass("hidebtn");
					@endif
					$(".transactSubmitBtn").addClass("hidebtn");
					$(".docSaveBtn").removeClass("hidebtn");
					$(".docSaveEmailBtn").removeClass("hidebtn");
					
					@if($access['is_approve'])
					if(doc_doctype.value == 'Order'){
						if(approve_manager){
							$(".docApproveBtn").removeClass("hidebtn");
							$(".docApproveEmailBtn").removeClass("hidebtn");
						}else{
							$(".docApproveBtn").addClass("hidebtn");
							$(".docApproveEmailBtn").addClass("hidebtn");
						}
					}else{
						$(".docApproveBtn").removeClass("hidebtn");
						$(".docApproveEmailBtn").removeClass("hidebtn");
					}
					@else
						$(".docApproveBtn").addClass("hidebtn");
						$(".docApproveEmailBtn").addClass("hidebtn");
					@endif
					
					$(".creditDraftBtn").addClass("hidebtn");
					$(".creditApproveBtn").addClass("hidebtn");
					$(".creditDraftEmailBtn").addClass("hidebtn");
					$(".creditApproveEmailBtn").addClass("hidebtn");
				}else if(doc_doctype.value == 'Credit Note' || doc_doctype.value == 'Credit Note Draft'){
					@if(!empty($document->id))
						$(".docEmailBtn").removeClass("hidebtn");
					@else
						$(".docEmailBtn").addClass("hidebtn");
					@endif
					$(".transactSubmitBtn").addClass("hidebtn");
					$(".docSaveBtn").addClass("hidebtn");
					$(".docApproveBtn").addClass("hidebtn");
					$(".creditDraftBtn").removeClass("hidebtn");
					$(".docSaveEmailBtn").addClass("hidebtn");
					$(".docApproveEmailBtn").addClass("hidebtn");
					$(".creditDraftEmailBtn").removeClass("hidebtn");
					@if($access['is_approve'])
						$(".creditApproveBtn").removeClass("hidebtn");
						$(".creditApproveEmailBtn").removeClass("hidebtn");
					@else
						$(".creditApproveBtn").addClass("hidebtn");
						$(".creditApproveEmailBtn").removeClass("hidebtn");
					@endif
				}else{
					$(".creditDraftBtn").addClass("hidebtn");
					$(".creditApproveBtn").addClass("hidebtn");
					$(".docSaveBtn").addClass("hidebtn");
					$(".docApproveBtn").addClass("hidebtn");
					$(".creditDraftEmailBtn").addClass("hidebtn");
					$(".creditApproveEmailBtn").addClass("hidebtn");
					$(".docSaveEmailBtn").addClass("hidebtn");
					$(".docApproveEmailBtn").addClass("hidebtn");
					if(doc_doctype.value > ''){
						$(".transactSubmitBtn").removeClass("hidebtn");
						
					@if(!empty($document->id))
						$(".docEmailBtn").removeClass("hidebtn");
					@else
						$(".docEmailBtn").addClass("hidebtn");
					@endif
					}else{
						$(".transactSubmitBtn").addClass("hidebtn");
						$(".docEmailBtn").addClass("hidebtn");
					}
				}
			},
			created: function(){
			
				var approve_manager = {{ (check_access('1,2,7')) ? 1: 0 }};
				if(doc_doctype.value == 'Quotation' || doc_doctype.value == 'Order' || doc_doctype.value == 'Supplier Order'){
					@if(!empty($document->id))
					$(".docEmailBtn").removeClass("hidebtn");
					@else
					$(".docEmailBtn").addClass("hidebtn");
					@endif
					$(".transactSubmitBtn").addClass("hidebtn");
					$(".docSaveBtn").removeClass("hidebtn");
					$(".docSaveEmailBtn").removeClass("hidebtn");
					
					@if($access['is_approve'])
					if(doc_doctype.value == 'Order'){
						if(approve_manager){
							$(".docApproveBtn").removeClass("hidebtn");
							$(".docApproveEmailBtn").removeClass("hidebtn");
						}else{
							
							$(".docApproveBtn").addClass("hidebtn");
							$(".docApproveEmailBtn").addClass("hidebtn");
						}
					}else{
						$(".docApproveBtn").removeClass("hidebtn");
						$(".docApproveEmailBtn").removeClass("hidebtn");
					}
					@else
					$(".docApproveBtn").addClass("hidebtn");
					$(".docApproveEmailBtn").addClass("hidebtn");
					@endif
					
					$(".creditDraftBtn").addClass("hidebtn");
					$(".creditApproveBtn").addClass("hidebtn");
					$(".creditDraftEmailBtn").addClass("hidebtn");
					$(".creditApproveEmailBtn").addClass("hidebtn");
				}else if(doc_doctype.value == 'Credit Note' || doc_doctype.value == 'Credit Note Draft'){
					@if(!empty($document->id))
					$(".docEmailBtn").removeClass("hidebtn");
					@else
					$(".docEmailBtn").addClass("hidebtn");
					@endif
					$(".transactSubmitBtn").addClass("hidebtn");
					$(".docSaveBtn").addClass("hidebtn");
					$(".docApproveBtn").addClass("hidebtn");
					$(".creditDraftBtn").removeClass("hidebtn");
					$(".docSaveEmailBtn").addClass("hidebtn");
					$(".docApproveEmailBtn").addClass("hidebtn");
					$(".creditDraftEmailBtn").removeClass("hidebtn");
					@if($access['is_approve'])
					$(".creditApproveBtn").removeClass("hidebtn");
					$(".creditApproveEmailBtn").removeClass("hidebtn");
					@else
					$(".creditApproveBtn").addClass("hidebtn");
					$(".creditApproveEmailBtn").removeClass("hidebtn");
					@endif
				}else{
					$(".creditDraftBtn").addClass("hidebtn");
					$(".creditApproveBtn").addClass("hidebtn");
					$(".docSaveBtn").addClass("hidebtn");
					$(".docApproveBtn").addClass("hidebtn");
					$(".creditDraftEmailBtn").addClass("hidebtn");
					$(".creditApproveEmailBtn").addClass("hidebtn");
					$(".docSaveEmailBtn").addClass("hidebtn");
					$(".docApproveEmailBtn").addClass("hidebtn");
					if(doc_doctype.value > ''){
						$(".transactSubmitBtn").removeClass("hidebtn");
						
						@if(!empty($document->id))
						$(".docEmailBtn").removeClass("hidebtn");
						@else
						$(".docEmailBtn").addClass("hidebtn");
						@endif
					}else{
						$(".transactSubmitBtn").addClass("hidebtn");
						$(".docEmailBtn").addClass("hidebtn");
					}
				}
			}
		});
		doc_doctype.appendTo('#doctype');
		
		
		
		doc_reference = new ej.inputs.TextBox({
			placeholder: "Reference",
		});
		doc_reference.appendTo("#reference");
		
		
	
	    
		
		subtotal_input = new ej.inputs.NumericTextBox({
		    format: currency_symbol+' ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->total  - $document->tax) }}',
		});
		
	    subtotal_input.appendTo('#subtotal');
		
		@if(!$remove_tax_fields)
		tax_input = new ej.inputs.NumericTextBox({
		    format: currency_symbol+' ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->tax ) }}',
		});
		
	    tax_input.appendTo('#tax');
	    @endif
	    
	 
		
		total_input = new ej.inputs.NumericTextBox({
		    format: currency_symbol+' ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->total ) }}',
		});
		
	    total_input.appendTo('#total');
		
		monthly_total_input = new ej.inputs.NumericTextBox({
		    format: currency_symbol+' ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: 0,
		});
		
	    monthly_total_input.appendTo('#monthly_total');
	    
	    
		var rowCount = $('#lines tr').length;
	
		if(rowCount == 1){
			$('.remove').hide();
		}else{
			$('.remove').css("display", "flex");
		}
		
	
		@if(!$document->id)
		$("#company").focus();
		@endif
		@if($document->doctype == 'Quotation')
		$.when(save_line_template()).then(set_line_inputs()).then(calculate_document_total()).then(update_product_prices());
		@else
		$.when(save_line_template()).then(set_line_inputs()).then(calculate_document_total());
		@endif
		
	});

//// Event Functions
	function save_line_template(){
		line_template = $("#lines tr:last").clone();
	}
	
	function insert_line(){
	
		var cloned = line_template.clone();
		$(cloned).insertAfter("#lines tr:last");
	}


	function set_line_inputs(new_line = false, product_id = false, qty = false, price = false){
	////console.log('set_line_inputs');
	////console.log(new_line);
	////console.log(product_id);
		    
		var line_count = $("#lines tr").length;
		
	
	
		
		$('.qty').each(function(index) {
			var input_val = parseInt($(this).val());
			if(new_line)
			var input_val = parseInt(1);
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && qty){
					input_val = qty;
				}
				window["qty_input" + index] = new ej.inputs.NumericTextBox({
					format: 'n0',
					decimals: 0,
				    min: 1,
				    value: input_val,
					enabled: false,
					showSpinButton: false,
					showClearButton: false,
				    change: function(){
				    	update_product_prices();
				    }
				});
				
			    window["qty_input" + index].appendTo(this);
			}
		});
		
	
		$('.description').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				window["description_input" + index] = new ej.inputs.TextBox({
					placeholder: 'Description',
					enabled: false,
				});
				
			    window['description_input' + index].appendTo(this);
			}
		});
		
		$('.contract_period').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				var input_val = parseInt($(this).val());
			
				if(isNaN(input_val)){
					var input_val = 0;
				}
				window["contract_period" + index] = new ej.dropdowns.DropDownList({
					allowFiltering: false,
					placeholder: 'Contract',
					dataSource: [
						{text:'Month to month',value:0},
						{text:'12 month contract',value:12}
					],
				    value: input_val,
					fields: {text: 'text', value: 'value'},
					change: function(){
						update_product_prices();
					}
				});
				
			    window['contract_period' + index].appendTo(this);
			}
		});
		
		$('.price').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					if(price == 0){
					input_val = null;
					price = null;
					}else{
					input_val = price/1.15;
					}
				}
				window["price_input" + index] = new ej.inputs.NumericTextBox({
				    format: currency_symbol+' ###########.00',
					showSpinButton: false,
				    decimals: 2,
				    value: input_val,
					enabled: false,
				    @if(is_superadmin() || (session('role_level') == 'Admin' && !is_main_instance()) || ($db_table == 'crm_supplier_documents' && session('role_level') == 'Admin'))
					readonly: false,
				    @else
					readonly: true,
					@endif
					
				    change: function(){
				    	
						@if(!$remove_tax_fields)
				    	if(window['price_input' + index].value == null){
				    		window['price_incl_input' + index].value = null;
				    	}else{
							if($("#vat_enabled").val() == 1){
								window['price_incl_input' + index].value  = window['price_input' + index].value + (window['price_input' + index].value * 0.15);	
							}else{
								window['price_incl_input' + index].value  = window['price_input' + index].value;	
							}
				    	}
				    	@endif
				    	calculate_document_total();
				    }
				});
				
			    window['price_input' + index].appendTo(this);
			}
		});
		
		@if(!$remove_tax_fields)
		$('.price_incl').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					input_val = price;
				}
				window["price_incl_input" + index] = new ej.inputs.NumericTextBox({
				    format: currency_symbol+' ###########.00',
					showSpinButton: false,
				    decimals: 2,
				    value: input_val,
					enabled: false,
				    @if(is_superadmin() || (session('role_level') == 'Admin' && !is_main_instance()) ||  ($db_table == 'crm_supplier_documents' && session('role_level') == 'Admin'))
					readonly: false,
				    @else
					readonly: true,
					@endif
				    change: function(){
				    	
							if($("#vat_enabled").val() == 1){
								window['price_input' + index].value  = window['price_incl_input' + index].value / 1.15;	
							}else{
								window['price_input' + index].value  = window['price_incl_input' + index].value;	
							}
				    	
				    	
				    }
				});
				
			    window['price_incl_input' + index].appendTo(this);
			}
		});
		@endif
		$('.line_total').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					input_val = price/1.15;
				}
				window["line_total_input" + index] = new ej.inputs.NumericTextBox({
				    format: currency_symbol+' ###########.00',
					showSpinButton: false,
				    decimals: 2,
				    value: input_val,
					enabled: false,
				});
				
			    window["line_total_input" + index].appendTo(this);
			}
		});
		
		@if(!$remove_tax_fields)
		$('.line_total_tax').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					input_val = price;
				}
				window["line_total_tax_input" + index] = new ej.inputs.NumericTextBox({
				    format: currency_symbol+' ###########.00',
					showSpinButton: false,
				    decimals: 2,
					enabled: false,
				    value: input_val,
				});
				
			    window["line_total_tax_input" + index].appendTo(this);
			}
		});
		@endif
		var category_id = false;
		
		
		if(product_id){
			$(window.products).each(function(index,el) {
			
				if(parseInt(el.id) == parseInt(product_id)){
				
					category_id = el.category_id;	
				}
			});
		}
	

	
		$('.categories').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				var input_val = parseInt($(this).val());
				
				if(category_id){
				var input_val = parseInt(category_id);
				}
				if(isNaN(input_val)){
					var input_val = 0;
				}
			
				window["category_input" + index] =new ej.dropdowns.DropDownList({
					//set the data to dataSource property
					dataSource: window.categories,
					fields: { value: 'id', text: 'category' },
				    value: input_val,
					//bind the change event handler
					change: function(e) {
				
					//Query the data source based on country DropDownList selected value
					window["product_input" + index].query = new ej.data.Query().where('category_id', 'equal', window["category_input" + index].value);
					// enable the state DropDownList
					window["product_input" + index].enabled = true;
					//clear the existing selection.
					// bind the property changes to state DropDownList
					window["product_input" + index].dataBind();
					//clear the existing selection in city DropDownList
					
					},
					placeholder: 'Select a category',
					focus: function(e){
						input_required_check();
					}
					
				});
				
			    window["category_input" + index].appendTo(this);
			}
		});
		
		@if($is_supplier)
		$('.ledger_accounts').each(function(index) {
	
			var input_val = parseInt($(this).val());
			if(new_line){
				var input_val = '';
				$('#lines tr:last').find('.ledger_account_description_div').hide();
				
			}
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
			
				input_val = parseInt(input_val);
			
				window["ledger_account_input" + index] = new ej.dropdowns.DropDownList({
					dataSource: window.ledger_accounts,
					fields: {text: 'name', value: 'id'},
        			htmlAttributes: {name: 'ledger_account_id[]'}, 
					placeholder: 'Ledger account  (optional)',
					ignoreAccent: true,
					allowFiltering: false,
				    value: input_val,
					popupWidth:'200%',
					filterBarPlaceholder: 'Ledger account name (optional)',
					change: function(e){
						
					},
					created: function(){
					
					},
					dataBound: function(e){
						if(window["ledger_account_input" + index].listData && parseInt(window["ledger_account_input" + index].listData.length) > 0){
							
							if(input_val != ""){
								window["ledger_account_input" + index].value = input_val;
							}
							window["ledger_account_input" + index].dataBind();
						}
						
					},
				});
				
			    window["ledger_account_input" + index].appendTo(this);
			}
		});
		@endif
		$('.products').each(function(index) {
	
			var input_val = parseInt($(this).val());
			if(new_line){
				var input_val = '';
				$('#lines tr:last').find('.product_description_div').hide();
				
			}
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				
				if(new_line && isLastElement && product_id){
					input_val = product_id;
				}
				input_val = parseInt(input_val);
			
				window["product_input" + index] = new ej.dropdowns.DropDownList({
					dataSource: window.products,
					fields: {text: 'code', value: 'id'},
        			htmlAttributes: {name: 'product_id[]'}, 
					placeholder: 'Product',
					ignoreAccent: true,
					allowFiltering: false,
				    value: input_val,
					popupWidth:'200%',
					filterBarPlaceholder: 'Type Product Name',
			       
					change: function(e){
							//console.log('change');
							//console.log(window["product_input" + index]);
						
						if (window["product_input" + index].value > 0){
							// coverage map button
						
						
							if(window["category_input" + index].value == 0 || window["category_input" + index].value == '' || window["category_input" + index].value == null){
								////console.log('set category val');
								////console.log(window["product_input" + index].itemData);
								////console.log(window["product_input" + index].itemData.category_id);
								window["category_input" + index].value = window["product_input" + index].itemData.category_id;
							}
							
							console.log(window["product_input" + index].itemData.provision_type);
						
							if(window["product_input" + index].itemData.provision_type && window["product_input" + index].itemData.provision_type.toLowerCase().indexOf("fibre_product") >= 0){
							
								$("#coveragerow").show();
							}else if(window["product_input" + index].itemData.provision_type && window["product_input" + index].itemData.provision_type.toLowerCase().indexOf("fibre") >= 0){
								$("#coveragerow").show();
							}else if(window["product_input" + index].itemData.provision_type && window["product_input" + index].itemData.provision_type.toLowerCase().indexOf("lte") >= 0){
								$("#coveragerow").show();
							}
						
						//	if(window['description_input' + index].value == null || window['description_input' + index].value == ''){
							//	window['description_input' + index].value = window["product_input" + index].itemData.description;
						//	}
						//	//console.log(window["product_input" + index]);
						//	//console.log(window["product_input" + index].itemData.provision_type);
							@if($db_table == 'crm_supplier_documents')
							if(window["product_input" + index].value == 1689){
								$(this.inputElement).closest('.clone').find('.cdr_destinations_div').show();
							}else{
								$(this.inputElement).closest('.clone').find('.cdr_destinations_div').hide();
							}
							@endif
							@if($db_table == 'crm_documents')
							if(window["product_input" + index].value > 0){
								$(this.inputElement).closest('.clone').find('.contract_period_div').show();
							}else{
								$(this.inputElement).closest('.clone').find('.contract_period_div').hide();
							}
							@endif
							if(window['description_input' + index].value > '' || (window["product_input" + index].value == 147 
							|| window["product_input" + index].itemData.provision_type == 'fibre' ||  window["product_input" + index].itemData.provision_type == 'fibre_product'
							|| window["product_input" + index].itemData.provision_type == 'ip_range_route' ||  window["product_input" + index].itemData.provision_type == 'ip_range_gateway')){
									//console.log('desc show');
								window['description_input' + index].enabled = true;
								$(this.inputElement).closest('.clone').find('.product_description_div').show();
							
							}else{
								$(this.inputElement).closest('.clone').find('.product_description_div').hide();
							}
							
							@if(is_superadmin())
									window["price_input" + index].readonly = false;
									window["price_incl_input" + index].readonly = false;
							@elseif(session('role_level') == 'Admin')
								@if(session('instance')->directory == 'eldooffice')
									window["price_input" + index].readonly = false;
									window["price_incl_input" + index].readonly = false;
								@elseif($db_table == 'crm_supplier_documents')
									window["price_input" + index].readonly = false;
									window["price_incl_input" + index].readonly = false;
								@else
									if(window["product_input" + index].value == 147){
										window["price_input" + index].readonly = false;
									}else{
										window["price_input" + index].readonly = true;
									}
									@if(!$remove_tax_fields)
									if(window["product_input" + index].value == 147){
										window["price_incl_input" + index].readonly = false;
									}else{
										window["price_incl_input" + index].readonly = true;
									}
									@endif
								@endif
							@endif
							
							
							
							window["price_input" + index].value = window["product_input" + index].itemData.price;
							
						
						}else{
							$(this.inputElement).closest('.clone').find('.product_description_div').hide();
							window['description_input' + index].enabled = false;
							window['description_input' + index].value = null;
							window["qty_input" + index].value = 1;
							window["price_input" + index].value = null;
						}
						
						input_required_check();
						calculate_document_total();
					},
					created: function(){
						if(window["category_input" + index].value > ''){
							//Query the data source based on country DropDownList selected value
							window["product_input" + index].query = new ej.data.Query().where('category_id', 'equal', window["category_input" + index].value);
							// enable the state DropDownList
							window["product_input" + index].enabled = true;
							//clear the existing selection.
							// bind the property changes to state DropDownList
							window["product_input" + index].dataBind();
							//clear the existing selection in city DropDownList
						}
							if(window["product_input" + index].value > 0){
								if(window["product_input" + index].itemData.provision_type && window["product_input" + index].itemData.provision_type.toLowerCase().indexOf("fibre_product") >= 0){
									$("#coveragerow").show();
								}else if(window["product_input" + index].itemData.provision_type && window["product_input" + index].itemData.provision_type.toLowerCase().indexOf("fibre") >= 0){
									$("#coveragerow").show();
								}else if(window["product_input" + index].itemData.provision_type && window["product_input" + index].itemData.provision_type.toLowerCase().indexOf("lte") >= 0){
									$("#coveragerow").show();
								}
							
								if(window["category_input" + index].value == 0 || window["category_input" + index].value == '' || window["category_input" + index].value == null){
									setTimeout(function() {
									//	//console.log('set category val2');
									//	//console.log(window["product_input" + index]);
									//	//console.log(window["product_input" + index].value);
									//	//console.log(window["product_input" + index].itemData);
									//	//console.log(window["product_input" + index].itemData.category_id);
									//	window["category_input" + index].value = window["product_input" + index].itemData.category_id;
									//	window["category_input" + index].refresh();
									    
									},1000)
								}
							}
							if(window['description_input' + index].value == null ){
							//	window['description_input' + index].value = window["product_input" + index].itemData.description;
							}
							@if($db_table == 'crm_supplier_documents')
							if(window["product_input" + index].value == 1689){
								$(this.inputElement).closest('.clone').find('.cdr_destinations_div').show();
							}else{
								$(this.inputElement).closest('.clone').find('.cdr_destinations_div').hide();
							}
							@endif
							
							@if($db_table == 'crm_documents')
							if(window["product_input" + index].value > 0){
								$(this.inputElement).closest('.clone').find('.contract_period_div').show();
							}else{
								$(this.inputElement).closest('.clone').find('.contract_period_div').hide();
							}
							@endif
							if(window["product_input" + index].value > 0){
								if(window['description_input' + index].value > '' || (window["product_input" + index].value == 147 
							|| window["product_input" + index].itemData.provision_type == 'fibre' ||  window["product_input" + index].itemData.provision_type == 'fibre_product'
							|| window["product_input" + index].itemData.provision_type == 'ip_range_route' ||  window["product_input" + index].itemData.provision_type == 'ip_range_gateway')){
							
								window['description_input' + index].enabled = true;
								$(this.inputElement).closest('.clone').find('.product_description_div').show();
							
							}else{
								$(this.inputElement).closest('.clone').find('.product_description_div').hide();
							}
							}else{
								$(this.inputElement).closest('.clone').find('.product_description_div').hide();
							}
						@if(!empty(request()->buy_product_id))
							window["product_input" + index].change();
						@endif
						if(new_line && isLastElement && product_id && !price){
							window["product_input" + index].change();
						}
						
					input_required_check();
					},
					dataBound: function(e){
						if(window["product_input" + index].listData && parseInt(window["product_input" + index].listData.length) > 0){
							
							if(input_val != ""){
								window["product_input" + index].value = input_val;
							}
							window["product_input" + index].dataBind();
						}
						
					},
					focus: function(e){
						input_required_check();
					}
				});
				
			    window["product_input" + index].appendTo(this);
			}
		});
		
		//console.log('set inputs');
		@if($db_table == 'crm_supplier_documents')
		
		//console.log('set inputs 2');
		$('.cdr_destination').each(function(index) {
		//console.log('set inputs 3');
	
			var input_val = $(this).val();
			if(new_line){
				var input_val = '';
			}
			//console.log(index);
			//console.log(new_line);
			
			
			var isLastElement = (index == line_count -1);
			//console.log(isLastElement);
			//console.log(line_count);
			if(!new_line || (new_line && isLastElement)){
				
		//console.log('set inputs 4');
				window["cdr_destination" + index] = new ej.dropdowns.DropDownList({
					dataSource: {!! json_encode($cdr_destinations) !!},
        			htmlAttributes: {name: 'cdr_destination[]'}, 
					placeholder: 'CDR Destination',
					ignoreAccent: true,
					allowFiltering: false,
				    value: input_val,
					popupWidth:'200%',
				});
				
			    window["cdr_destination" + index].appendTo(this);
			    //console.log(window["cdr_destination" + index]);
			}
		});
		@endif
		
	
		
	}
	
	function input_required_check(){

		if($("#account_type").val() == "reseller" &&  ((company.value == ""  || company.value == null) || (reseller_users && (reseller_users.value == "" || reseller_users.value == null)) || (doc_doctype.value == "" || doc_doctype.value == null))  ){
			if(company.value == ""  || company.value == null){
				$(company.element).addClass('input-required');
			}else{
				$(company.element).removeClass('input-required');
			}
			if(reseller_users.value == "" || reseller_users.value == null){
				$(reseller_users.element).addClass('input-required');
			}else{
				$(reseller_users.element).removeClass('input-required');
			}
			if(doc_doctype.value == "" || doc_doctype.value == null){
				$(doc_doctype.element).addClass('input-required');
			}else{
				$(doc_doctype.element).removeClass('input-required');
			}
		
			$('.categories').each(function(index) {
				window["category_input" + index].enabled = false;
				window["category_input" + index].dataBind();
			});
			$('.products').each(function(index) {
				window["product_input" + index].enabled = false;
				window["product_input" + index].dataBind();
			});
		}else if((company.value == ""  || company.value == null) || (doc_doctype.value == "" || doc_doctype.value == null)){
			if(company.value == ""  || company.value == null){
				$(company.element).addClass('input-required');
			}else{
				$(company.element).removeClass('input-required');
			}
			if(doc_doctype.value == "" || doc_doctype.value == null){
				$(doc_doctype.element).addClass('input-required');
			}else{
				$(doc_doctype.element).removeClass('input-required');
			}
			$('.categories').each(function(index) {
				window["category_input" + index].enabled = false;
				window["category_input" + index].dataBind();
			});
			$('.products').each(function(index) {
				window["product_input" + index].enabled = false;
				window["product_input" + index].dataBind();
			});
		}else{
			$(doc_doctype.element).removeClass('input-required');
			$(company.element).removeClass('input-required');
			
			if($("#account_type").val() == "reseller"){
				$(reseller_users.element).removeClass('input-required');
			}
			
			$('.categories').each(function(index) {
				if(window["category_input" + index].value > ''){
					if(window["product_input" + index]){
					window["product_input" + index].enabled = true;
					window["product_input" + index].dataBind();
					}
				}else{
					if(window["product_input" + index]){
					window["product_input" + index].enabled = false;
					window["product_input" + index].dataBind();
					}
				}
				window["category_input" + index].enabled = true;
				window["category_input" + index].dataBind();
			});
			
			
			$('.products').each(function(index) {
				if(window["product_input" + index] && window["product_input" + index].value > ''){
					window["qty_input" + index].enabled = true;
					window["qty_input" + index].dataBind();
					window["price_input" + index].enabled = true;
					window["price_input" + index].dataBind();
					
					@if(!$remove_tax_fields)
					window["price_incl_input" + index].enabled = true;
					window["price_incl_input" + index].dataBind();
					@endif
				}else{
					window["qty_input" + index].enabled = false;
					window["qty_input" + index].dataBind();
					window["price_input" + index].enabled = false;
					window["price_input" + index].dataBind();
					@if(!$remove_tax_fields)
					window["price_incl_input" + index].enabled = false;
					window["price_incl_input" + index].dataBind();
					@endif
				}
			});
		}
	}
	
	function load_products(){
	
		var account_id = $("#account_id").val();
	
		
		@if($db_table == 'crm_supplier_documents')
		var type = 'supplier';
		@else
		var type = 'account';
		@endif
		
	
		//console.log('/form_products/'+account_id+'/'+type);
		$.ajax({
			url: '/form_products/'+account_id+'/'+type,
			dataType:"json",
			success: function(data){
				//console.log(data);
				window.products = data;
				$('.products').each(function(index) {
					window["product_input" + index].dataSource = window.products;
					window["product_input" + index].dataBind();
				});
			}
		});
	}
	
	
	function update_product_prices(){
		
		var lines_set = true;
		$('.products').each(function(index) {
			//console.log(window["product_input" + index].value);
			if(window["product_input" + index].value > 0){
			}else{
				 lines_set = false;	
			}
		})
		//console.log(lines_set);
		if(!lines_set){
		return false;	
		}
		
		@if($enable_discounts && $db_table != 'crm_supplier_documents')
			var form = $('#transactionForm');
			var formData = new FormData(form[0]);
			
			@if($db_table == 'crm_supplier_documents')
			var type = 'supplier';
			@else
			var type = 'account';
			@endif
		
        	formData.append('account_type', type);
        	try{
			$.ajax({
				method: "post",
				url: '/form_products_update',
				data: formData,
				contentType: false,
				processData: false,
				beforeSend: function(){
					showSpinner();
				},
				success: function(data){
					hideSpinner();
					window.products = data;
					$('.products').each(function(index) {
						window["product_input" + index].dataSource = window.products;
						window["product_input" + index].dataBind();
						window["product_input" + index].trigger('change');
					});
					
					calculate_document_total();
				}
			});
        	}catch(e){hideSpinner();}
		@else
			calculate_document_total();
		@endif
	}
	

	
	function reseller_user_select(){
		if(company.value){
			try{
				reseller_users.destroy();
			}catch(e){}
			
			$.ajax({
				url: '/form_reseller_users/'+company.value,
				dataType:"json",
				success: function(data){
					reseller_users = new ej.dropdowns.DropDownList({
						dataSource: data,
						fields: {text: 'company', value: 'id'},
						placeholder: 'Company Name',
						ignoreAccent: true,
						allowFiltering: true,
						filterBarPlaceholder: 'Type Company Name',
						@if($document->reseller_user)
						value: {{ $document->reseller_user }},
						@endif
						@if(!empty($reseller_user_id))
						value: {{ $reseller_user_id }},
						@endif
				        filtering: function(e){
							if(e.text == ''){
								e.updateData(reseller_users.dataSource);
							}else{ 
								var query = new ej.data.Query().select(['id','company']);
								query = (e.text !== '') ? query.where('company', 'contains', e.text, true) : query;
								e.updateData(reseller_users.dataSource, query);
							}
				        },
				        change: function(e){
							input_required_check();	
				        },
				        created: function(e){
							$(".serviceaccountrow").removeClass('d-none');
							input_required_check();	
				        },
					});
					reseller_users.appendTo('#reseller_user');
				}
			});
		}
	}
	
	function calculate_document_total() {
		//console.log('calculate_document_total');
		var tax = 0;
		var monthly_tax = 0;
	
		var subtotal = 0;
		var monthly_subtotal = 0;
		var documentDate = new Date(doc_docdate.value);
		var taxchangeDate = new Date('2018-04-01');
		var vat_percentage = 0.15;
		if (documentDate < taxchangeDate) {
		var vat_percentage = 0.14;
		}

	
		$('.products').each(function(index) {
			var line_total = window["qty_input" + index].value * window["price_input" + index].value;
		
			
			
			window["line_total_input" + index].value = line_total;
			
			@if(!$remove_tax_fields)
			if($("#vat_enabled").val() == 1){
				window["line_total_tax_input" + index].value = line_total + (line_total * vat_percentage);	
			}else{
				window["line_total_tax_input" + index].value = line_total;	
			}
			@endif
		
		
		
			subtotal += line_total;
			
		
		
			
		
		});
	
	
		
		if($("#vat_enabled").val() == 1){
			
			var tax = parseFloat(subtotal) * vat_percentage;
			var monthly_tax = parseFloat(monthly_subtotal) * vat_percentage;
		}
		
	
		
		@if(!$remove_tax_fields)
		tax_input.value = tax; 
		tax_input.dataBind();
		@endif
		subtotal_input.value = subtotal;
		subtotal_input.dataBind();
		total_input.value = subtotal + tax;
		total_input.dataBind();
		monthly_total_input.value = monthly_subtotal + monthly_tax;
		monthly_total_input.dataBind();
		
		if(monthly_subtotal > 0){
			$('.monthlyrow').css("display", "flex");
		}else{
			$('.monthlyrow').hide();
		}
		
	
	}
	
	
	@if(!$is_supplier)
		$(document).off('click', '#add_delivery').on('click', '#add_delivery', function() {
		
			$.when(insert_line()).then(set_line_inputs(true,135,1));
			var rowCount = $('#lines tr').length;
		
			if(rowCount == 1){
				$('.remove').hide();
			}else{
				$('.remove').css("display", "flex");
			}
		});

	@endif
	
//// Change Events	

	$("#cloneButton").click(function() {
		
		if($("#account_id").val() == '')
		{
			toastNotify('Select account','info');
			return false;
		}
		$.when(insert_line()).then(set_line_inputs(true));
		var rowCount = $('#lines tr').length;
	
		if(rowCount == 1){
			$('.remove').hide();
		}else{
			$('.remove').css("display", "flex");
		}
		
	});
	
	$(document).off('click', '.remove').on('click', '.remove', function() {
    var rowIndex = $(this).closest('tr').index();
		$(this).parents('tr').first().remove();
		var rowCount = $('#lines tr').length;
	
		if(rowCount == 1){
			$('.remove').hide();
		}else{
			$('.remove').css("display", "flex");
		}
    
		$.when(change_row_index(rowIndex, rowCount)).then(calculate_document_total());
		return false;
	});
      
  function change_row_index(rowIndex, rowCount){
   
    if(rowIndex == rowCount || rowIndex == -1){
      return false;
    }
   
    var rowLoop = rowIndex;
    while(rowLoop < rowCount){
      var oldIndex = rowLoop + 1;
      window["line_total_input" + rowLoop] = window["line_total_input" + oldIndex];
      
	  @if(!$remove_tax_fields)
      window["line_total_tax_input" + rowLoop] =  window["line_total_tax_input" + oldIndex];
      @endif
    
      window["product_input" + rowLoop] =  window["product_input" + oldIndex];
      window["qty_input" + rowLoop] = window["qty_input" + oldIndex];
      window["price_input" + rowLoop] = window["price_input" + oldIndex];
      window["product_input" + rowLoop] = window["product_input" + oldIndex];
      rowLoop++;
    }
    return true;
    
  }
      
	$('#transactionForm').on('submit', function(e) {
		e.preventDefault();
		
		$.when(calculate_document_total()).then(formSubmit('transactionForm'));
	});

</script>
@endpush
@push('page-styles')
<style>

@if(!empty(request()->tab_load))
  .formio-component-submit{
    position: absolute;
    top: 10px;
    right: 30px;  
  }

#page-wrapper {
    padding-top: 50px !important;
}
@endif

#lines .product-input-group{
	border: none;
}
.product_description_div{
	border: none !important;
	
} 
	.monthlyrow{
		display: none;
	}
	
	#page-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
		height:auto;
	}
	
	#document-lines > th {
		font-size: 13px;
		color: red;
	}

	#document-lines > td {
		font-size: 13px;
	}
	
	#document-lines > thead {
		background-color: #fff !important;
	}

	#document-lines {
		background-color: #f5f5f5;
		width: 100%;
		margin-bottom: 15px;
		border: 1px solid #DDD;
	}
	
	
	#monthly-lines > th {
		font-size: 13px;
		color: red;
	}

	#monthly-lines > td {
		font-size: 13px;
	}
	
	#monthly-lines > thead {
		background-color: #fff !important;
	}

	#monthly-lines {
		background-color: #f5f5f5;
		width: 100%;
		margin-bottom: 15px;
		border: 1px solid #DDD;
	}
	#document_info strong, #document_info b, #document_branding strong, #document_branding b{
		font-weight: 600;
		font-size: 14px;
	}
	
	#monthly_totals strong, #monthly_totals b{
		font-weight: 600;
		font-size: 14px;
	}
	.e-numerictextbox{
		text-align:right;
	}

	.input-required{
	/*	border: 1px solid red !important;*/
	}
	.hidebtn{
		display:none !important;
	}
	.e-control.e-numerictextbox[readonly] {
    background-color: #f4f4f4 !important;
}
</style>
@endpush