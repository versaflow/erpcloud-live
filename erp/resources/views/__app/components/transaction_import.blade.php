@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
	
@endif

@section('content')

<div id="page-wrapper" class="container mx-auto">

<form id="transactionForm" action="/{{ $menu_route }}/save" class="form-horizontal" >
	<input type="hidden" id="id" name="id" value="{{ $document->id }}" >

	<input type="hidden" id="status" name="status" value="{{ $document->status }}"> 
	<input type="hidden" id="reversal_id" name="reversal_id" value="{{ $document->reversal_id }}">
	<input type="hidden" id="account_id" name="account_id" value='{{ $account_id }}' >
	<input type="hidden" id="account_type" value='{{ $account_type }}' >
	<input type="hidden" id="vat_enabled" value="{{ $vat_enabled }}">
	<input type="hidden" id="exchange_rate" name="exchange_rate" value="{{ $exchange_rate }}">

	<input type="hidden" id="supplier_import" name="supplier_import" value="1">
	<input type="hidden" id="supplier_invoice_id" name="supplier_invoice_id"  value="{{ $document->supplier_invoice_id }}">
	
	<div class="row">
		<div class="col-md-5" id="document_info">
			<div class="row mt-3">
				<div class='col col-md-4 align-self-center'>
					<strong>Date</strong>
				</div>
				<div class='col col-md-8'>
					<input type="text" id="docdate" name="docdate" required="true">
				</div>
			</div>
			
			<div class="row">
				<div class='col col-md-4 align-self-center'>
					<strong>Company</strong>
				</div>
				<div class='col col-md-8'>
					<input id="company" />
				</div>
			</div>
			
			<div class="row serviceaccountrow">
				<div class='col col-md-4 align-self-center'>
					<strong>Partner Customer</strong>
				</div>
				<div class='col col-md-8'>
					<input id="reseller_user" name="reseller_user" />
				</div>
			</div>
			
			<div class="row">
				<div class='col col-md-4 align-self-center'>
					<strong>Doc Type</strong>
				</div>
				<div class='col col-md-8'>
					<input id="doctype" name="doctype" class='form-control'>
				</div>
			</div>
			
			<div class="row">
				<div class='col col-md-4 align-self-center'>
					<strong>Reference</strong>
				</div>
				<div class='col col-md-8'>
					<input type="text" id="reference" name="reference" value='{{ $document->reference }}' class="form-control">
				</div>
			</div>		
			
		
			
			<div class="row">
				<div class='col col-md-4 align-self-center'>
					<strong>Import Shimpment</strong>
				</div>
				<div class='col col-md-8'>
					<select id="import_shipment_id" name="import_shipment_id" > 
				
					@if(!empty($import_shipments) && count($import_shipments) > 0)
					@foreach($import_shipments as $import_shipment)
					<option value="{{$import_shipment->id}}">{{$import_shipment->shipment_invoice_number}}</option>
					@endforeach
					@endif
					</select>
				</div>
			</div>
			
			<div class="row">
				<div class='col col-md-4 align-self-center'>
					<strong>Shipping Company</strong>
				</div>
				<div class='col col-md-8'>
					<select id="shipping_company_id" name="shipping_company_id" > 
				
					@if(!empty($shipping_companies) && count($shipping_companies) > 0)
					@foreach($shipping_companies as $shipping_company)
					<option value="{{$shipping_company->id}}">{{$shipping_company->company}}</option>
					@endforeach
					@endif
					</select>
				</div>
			</div>
			
		</div>
		<div class="col-md-4 offset-md-3 text-right" id="document_branding">
			<div class="col">
				@if($logo)
					<img style="" src="{{ $logo }}" class="img-fluid"/>
				@else
					<h2>{{ $partner_company }}</h2>
				@endif
			</div>
			<div class="col mt-3">
				<h6 id="doctitle">
				{{ ($document->doctype) ? $document->doctype."# ".$document->id : $doctype }}
				</h6>
			</div>
		</div>
	</div>

	<div class="row mt-1">
		<div class="table-responsive">
			<table id="document-lines" class="table table-bordered table-striped">
			<thead>
				<tr>
					<th style="width: 38%">Product</th>
					<th class="text-right" style="width: 8%">Frequency</th>
					<th class="text-right">Price</th>
					<th class="text-right">Price Incl</th>
					<th class="text-right" style="width: 8%">Qty</th>
					<th class="text-right">Total</th>
					<th class="text-right line_tax">Total Incl</th>
					<th></th>
				</tr>
			</thead>
			<tbody id="lines">
			@foreach ($document_lines as $i => $line)
			<tr class="clone">
				<td>
					<div class="e-input-group product-input-group">
					<input type="text" name="product_category_id[]" value="{{ $line->product_category_id }}"  class="categories form-control">
					</div>
					<div class="e-input-group product-input-group">
					<input type="text" name="product_id[]" value="{{ $line->product_id }}"  class="products form-control">
					</div>
					<div class="website_link mt-1"></div>
					<div class="e-input-group product_description_div">
					<textarea name='description[]' class="form-control description">{{ $line->description }}</textarea>
					</div>
				
				</td>
			
				<td>
					<input type="text" class="frequency form-control text-right" value="{{ $line->frequency }}" >
				</td>
				<td>
					<input type="hidden" name="full_price[]" value="{{ currency($line->full_price) }}" class="full_price form-control">
					<input type="text" name="price[]" value="{{ currency($line->price) }}" class="price form-control">
				</td>
				<td>
					@if($vat_enabled)
					<input type="text" value="{{ currency($line->price*1.15) }}" class="price_incl form-control">
					@else
					<input type="text" value="{{ currency($line->price) }}" class="price_incl form-control">
					@endif
				</td>
				<td>
					<input type="text" name="qty[]" value="{{ $line->qty }}" class="qty form-control text-right">
				</td>
				<td class="total">
					<input type="text" value="{{ currency($line->qty * $line->price) }}" class="line_total form-control">
				</td>
				<td class="line_tax">
					<input type="text" class="line_total_tax form-control">
				</td>
				<td>
				<a href="javascript:void(0)" class="remove"><span title="Remove" class="btn btn-danger btn-sm  fa fa-minus" /></a>
				</td>
			</tr>
			@endforeach
			</tbody>
		</table>
		</div>
		<div class="col text-right">
			<a id="cloneButton"><span title="Add" class="btn btn-success btn-sm fa fa-plus" /></a>
		</div>
	</div>
	
	
	
	
	<div class="row mt-3" id="totals">
		<div class="col-md-4 offset-md-8 text-right" id="document_totals">
			@if(!$is_supplier)
			<div class="row  mt-1 text-right">
				<div class="col text-right">
				<button id="add_delivery" class="e-btn" type="button">Add Delivery</button>
				</div>
			</div>
			@if(check_access('1,31'))
			<div class="row mt-1 text-right">
				<div class="col text-right">
				<button id="add_discount" class="e-btn"  type="button">Add Discount</button>
				</div>
			</div>
			@endif
			@endif
		
			<div class="row mt-3">
				<div class='col col-md-8 align-self-center'>
					<strong>Subtotal USD</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="subtotal" name="subtotal" value="{{ currency($document->total - $document->tax) }}" class="form-control" >
				</div>
			</div>
		
			<div class="row">
				<div class='col col-md-8 align-self-center'>
					<strong>Tax USD</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="tax" name="tax" value="{{ currency($document->tax) }}" class="form-control" >
				</div>
			</div>
			
			@if($is_supplier)
			<div class="row">
				
				<div class='col col-md-8 align-self-center'>
					<strong>Shipping USD</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="shipping_usd" name="shipping_usd" value="{{ currency($document->shipping_usd) }}" class="form-control" >
				</div>
			</div>
			<div class="row">
				<div class='col col-md-8 align-self-center'>
					<strong>Import Tax USD</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="import_tax_usd" name="import_tax_usd" value="{{ currency($document->import_tax_usd) }}" class="form-control" >
				</div>
			</div>
			@endif
		
			<div class="row">
				<div class='col col-md-8 align-self-center'>
					<strong>Grand Total</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="total" name="total" value="{{ currency($document->total) }}" class="form-control" >
				</div>
			</div>
			
			<br>
			<br>
			<div class="row">
				<div class='col col-md-8 align-self-center'>
					<strong>Shipping (Rands)</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="shipping" name="shipping" value="{{ currency($document->shipping) }}" class="form-control" >
				</div>
			
			</div>
			<div class="row">
				<div class='col col-md-8 align-self-center'>
					<strong>Import Tax (Rands)</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="import_tax" name="import_tax" value="{{ currency($document->import_tax) }}" class="form-control" >
				</div>
			
			</div>
			<div class="row">
				<div class='col col-md-8 align-self-center'>
					<strong>Grand Total (Rands)</strong>
				</div>
				<div class='col col-md-4'>
				<input type="text" id="total_rands" name="total_rands"  value="{{ currency($document->total*$document->exchange_rate) }}" class="form-control" >
				</div>
			</div>
			
		
			
		</div>
	</div>
	
	
	<div class="row mt-3">
		<div class="col-md-5">
			<h5>Customer Notes</h5>
			<hr>
			<textarea name="notes" id="notes" rows="3" class="form-control">{{ $document->notes }}</textarea>
		</div>
		<div class="col-md-5 offset-md-2">
		<h5>Notices</h5>
			<hr>
			<p>{!! nl2br($document_footer) !!}</p>
		</div>
	</div>
	@if(!empty(request()->tab_load))
<div ref="component" class="field form-group has-feedback formio-component formio-component-button formio-component-submit float-right mr-2 form-group" >
<button lang="en" type="submit"  class="btn btn-primary ui button primary float-right mr-2" ref="button">
Submit
</button>
</div>
@endif
	</form>
</div>
@endsection
@push('page-scripts')

<script type="text/javascript">
//// Initial Load
	
	window.doctypes = {!! json_encode($doctypes) !!};
	window.accounts = {!! json_encode($accounts) !!};
	window.products = {!! json_encode($products) !!};
	window.categories = {!! json_encode($categories) !!};
	window.tld_prices = {!! json_encode($tld_prices) !!};
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
    
		
	
		company = new ej.dropdowns.DropDownList({
			dataSource: window.accounts,
			fields: {groupBy: 'type', text: 'company', value: 'id', type: 'type'},
			placeholder: 'Company Name',
			ignoreAccent: true,
			allowFiltering: true,
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
					if($("#account_id").val() > ''){
					
						$('.products').each(function(index) {
							window["product_input" + index].value = null;
							window["product_input" + index].refresh();
						});
					}
					
				
					$("#account_id").val(company.value);	
						load_products();
				
						doc_doctype.enabled = true;
				
					
					
					
					if(itemData.type == 'customer'){
						doc_doctype.dataSource = window.doctypes;
					}
					
					if(itemData.type == 'lead'){
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
						$(".serviceaccountrow").hide();
					}
					
					doc_reference.enabled = true;
					
				}else{
					doc_doctype.enabled = false;
					doc_reference.enabled = false;
				}
				input_required_check();
			},
			created: function(e){
				
				var itemData = company.dataSource.filter(obj => {
				return obj.id === company.value
				})[0];
				
				if(company.value && itemData){
					if(itemData.type == 'reseller'){
						reseller_user_select();
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
	
	    doc_docdate = new ej.calendars.DatePicker({
	    	format: 'yyyy-MM-dd',
			value: '{{ ($document->docdate) ? $document->docdate : date("Y-m-d") }}',
			@if(session('role_id') > 10)
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
				value: '{{ ($document->commitment_date) ? $document->commitment_date : date("Y-m-d") }}',
				@if($is_supplier == 0)
				min: '{{ ($document->commitment_date) ? $document->commitment_date : date("Y-m-d") }}',
				@endif
		    });
		    doc_commitment_date.appendTo('#commitment_date');
		@endif
			
		doc_doctype = new ej.dropdowns.DropDownList({
			placeholder: 'Select document type',
			dataSource: window.doctypes,
			value: '{{ ($document->doctype) ? $document->doctype : $doctype }}',
			
			@if($document->doctype && $document->doctype != 'Credit Note Draft' && $document->doctype != 'Credit Note Draft')
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
		
		
		import_shipment_id = new ej.dropdowns.DropDownList({
			placeholder: 'Select Import Shipment',
			@if($document->import_shipment_id)
			value: '{{ $document->import_shipment_id }}',
			@endif
		
		});
		import_shipment_id.appendTo('#import_shipment_id');
		
		
		doc_doctype = new ej.dropdowns.DropDownList({
			placeholder: 'Select document type',
			dataSource: window.doctypes,
			value: '{{ ($document->doctype) ? $document->doctype : $doctype }}',
			@if($document->doctype && $document->doctype != 'Credit Note Draft' && $document->doctype != 'Credit Note Draft')
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
		    format: '$ ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->total  - $document->tax) }}',
		});
		
	    subtotal_input.appendTo('#subtotal');
		
		tax_input = new ej.inputs.NumericTextBox({
		    format: '$ ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->tax ) }}',
		});
		
	    tax_input.appendTo('#tax');
	    
	    @if($is_supplier)
	    
	    
		shipping_usd = new ej.inputs.NumericTextBox({
		    format: '$ ###########.00',
			showSpinButton: false,
		    decimals: 2,
		    enabled: false,
		});
		
	    shipping_usd.appendTo('#shipping_usd');
	   
		import_tax_usd = new ej.inputs.NumericTextBox({
		    format: '$ ###########.00',
			showSpinButton: false,
		    decimals: 2,
		    enabled: false,
		});
		
	    import_tax_usd.appendTo('#import_tax_usd');
	    
		shipping = new ej.inputs.NumericTextBox({
		    format: 'R ###########.00',
			showSpinButton: false,
		    decimals: 2,
		    enabled: false,
		    created: function(){
		    	if(shipping.value > ''){
		    		shipping_usd.value = parseFloat(shipping.value/{{$exchange_rate}}).toFixed(2);
		    	}else{
		    		shipping_usd.value = 0;
		    	}
		    },
		    change: function(){
		    	if(shipping.value > ''){
		    		shipping_usd.value = parseFloat(shipping.value/{{$exchange_rate}}).toFixed(2);
		    	}else{
		    		shipping_usd.value = 0;
		    	}
		    	calculate_document_total();
		    }
		});
		
	    shipping.appendTo('#shipping');
	   
		import_tax = new ej.inputs.NumericTextBox({
		    format: 'R ###########.00',
			showSpinButton: false,
		    decimals: 2,
		    value: '{{ currency( $document->import_tax ) }}',
		    enabled: false,
		    created: function(){
		    	if(import_tax.value > ''){
		    	import_tax_usd.value = parseFloat(import_tax.value/{{$exchange_rate}}).toFixed(2);
		    	}else{
		    		import_tax_usd.value = 0;
		    	}
		    },
		    change: function(){
		    	if(import_tax.value > ''){
		    	import_tax_usd.value = parseFloat(import_tax.value/{{$exchange_rate}}).toFixed(2);
		    	}else{
		    		import_tax_usd.value = 0;
		    	}
		    	calculate_document_total();
		    }
		});
		
	    import_tax.appendTo('#import_tax');
	    @endif
		
		total_input = new ej.inputs.NumericTextBox({
		    format: '$ ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->total ) }}',
		});
		
	    total_input.appendTo('#total');
		total_rands_input = new ej.inputs.NumericTextBox({
		    format: 'R ###########.00',
			showSpinButton: false,
		    readonly: true,
		    enabled: false,
		    decimals: 2,
		    value: '{{ currency( $document->total * $document->exchange_rate ) }}',
		});
		
	    total_rands_input.appendTo('#total_rands');
		
	    
		var rowCount = $('#lines tr').length;
	
		if(rowCount == 1){
			$('.remove').hide();
		}else{
			$('.remove').css("display", "flex");
		}
		
	
		@if(!$document->id)
		$("#company").focus();
		@endif
		
		$.when(save_line_template()).then(set_line_inputs()).then(calculate_document_total());
	});

//// Event Functions
	function save_line_template(){
		line_template = $("#lines tr:last").clone();
	}
	
	function insert_line(){
	
		var cloned = line_template.clone();
		$(cloned).insertAfter("#lines tr:last");
	}


	function set_line_inputs(new_line = false, product_id = false, qty = false, frequency = false, price = false){
		
		var line_count = $("#lines tr").length;
		
	
		$('.frequency').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && frequency){
					input_val = frequency;
				}
				window["frequency_input" + index] = new ej.inputs.TextBox({
					value: input_val,
					readonly: true,
					enabled: false,
				});
				
			    window["frequency_input" + index].appendTo(this);
			}
		});
		
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
				    	
				    	calculate_document_total();
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
				    format: '$ ###########.00',
					showSpinButton: false,
				    decimals: 2,
				    value: input_val,
					enabled: false,
				    @if(session('role_level') == 'Admin' || ($db_table == 'crm_supplier_documents' && session('role_level') == 'Admin'))
					readonly: false,
				    @else
					readonly: true,
					@endif
				    change: function(){
				    	
				    	if(window['price_input' + index].value == null){
				    		window['price_incl_input' + index].value = null;
				    	}else{
							if($("#vat_enabled").val() == 1){
								window['price_incl_input' + index].value  = window['price_input' + index].value + (window['price_input' + index].value * 0.15);	
							}else{
								window['price_incl_input' + index].value  = window['price_input' + index].value;	
							}
				    	}
				    	calculate_document_total();
				    }
				});
				
			    window['price_input' + index].appendTo(this);
			}
		});
		
		$('.price_incl').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					input_val = price;
				}
				window["price_incl_input" + index] = new ej.inputs.NumericTextBox({
				    format: '$ ###########.00',
					showSpinButton: false,
				    decimals: 2,
				    value: input_val,
					enabled: false,
				    @if(check_access('1,31'))
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
		
		$('.line_total').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					input_val = price/1.15;
				}
				window["line_total_input" + index] = new ej.inputs.NumericTextBox({
				    format: '$ ###########.00',
					showSpinButton: false,
				    decimals: 2,
				    value: input_val,
					enabled: false,
				});
				
			    window["line_total_input" + index].appendTo(this);
			}
		});
		
		$('.line_total_tax').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				input_val = $(this).val();
				if(new_line && isLastElement && price){
					input_val = price;
				}
				window["line_total_tax_input" + index] = new ej.inputs.NumericTextBox({
				    format: '$ ###########.00',
					showSpinButton: false,
				    decimals: 2,
					enabled: false,
				    value: input_val,
				});
				
			    window["line_total_tax_input" + index].appendTo(this);
			}
		});
		
		$('.categories').each(function(index) {
			var isLastElement = (index == line_count -1);
			if(!new_line || (new_line && isLastElement)){
				var input_val = parseInt($(this).val());
				if(isNaN(input_val)){
					var input_val = 0;
				}
				//console.log('category');
				//console.log(input_val);
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
					
						if (window["product_input" + index].value > 0){
							// coverage map button
						
						
							if(window["category_input" + index].value == 0 || window["category_input" + index].value == '' || window["category_input" + index].value == null){
							
								window["category_input" + index].value = window["product_input" + index].itemData.category_id;
							}
						
						
						
							window["frequency_input" + index].value = window["product_input" + index].itemData.frequency;
						//	if(window['description_input' + index].value == null || window['description_input' + index].value == ''){
								window['description_input' + index].value = window["product_input" + index].itemData.description;
						//	}
						
							if(window['description_input' + index].value > '' || window["product_input" + index].value == 147 || window["product_input" + index].value == 532 || window["product_input" + index].value == 689){
								if (window["product_input" + index].value == 147){
									window['description_input' + index].enabled = true;
									$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}else if (window["product_input" + index].value == 689){
									window['description_input' + index].enabled = true;
									$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}else if (window["product_input" + index].value == 532){
									window['description_input' + index].enabled = true;
									$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}else{
									window['description_input' + index].enabled = false;
								//	$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}
							}else{
								$(this.inputElement).closest('.clone').find('.product_description_div').hide();
							}
							if(window["product_input" + index].value != 135){
							window["price_input" + index].value = window["product_input" + index].itemData.price;
							}
						
						}else{
							$(this.inputElement).closest('.clone').find('.product_description_div').hide();
							window['description_input' + index].enabled = false;
							window["frequency_input" + index].value = null;
							window['description_input' + index].value = null;
							window["qty_input" + index].value = 1;
							window["price_input" + index].value = null;
						}
						
						input_required_check();
					},
					created: function(){
					
							if(window["product_input" + index].value > 0){
								if(window["category_input" + index].value == 0 || window["category_input" + index].value == '' || window["category_input" + index].value == null){
								//	//console.log('set category val2');
								//	//console.log(window["product_input" + index].itemData);
								//	//console.log(window["product_input" + index].itemData.category_id);
									window["category_input" + index].value = window["product_input" + index].itemData.category_id;
								}
							}
							if(window['description_input' + index].value == null ){
							//	window['description_input' + index].value = window["product_input" + index].itemData.description;
							}
					
							if(window['description_input' + index].value > '' || window["product_input" + index].value == 147 || window["product_input" + index].value == 532 || window["product_input" + index].value == 689){
								if (window["product_input" + index].value == 147){
									window['description_input' + index].enabled = true;
									$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}else if (window["product_input" + index].value == 689){
									window['description_input' + index].enabled = true;
									$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}else if (window["product_input" + index].value == 532){
									window['description_input' + index].enabled = true;
									$(this.inputElement).closest('.clone').find('.product_description_div').show();
								}else{
									window['description_input' + index].enabled = false;
								//	$(this.inputElement).closest('.clone').find('.product_description_div').show();
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
		
	}
	
	function input_required_check(){
	
		if((company.value == ""  || company.value == null) || (doc_doctype.value == "" || doc_doctype.value == null)){
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
			
			var product_set = false;
			$('.products').each(function(index) {
				if(window["product_input" + index] && window["product_input" + index].value > ''){
					product_set = true;
					window["qty_input" + index].enabled = true;
					window["qty_input" + index].dataBind();
					window["price_input" + index].enabled = true;
					window["price_input" + index].dataBind();
					window["price_incl_input" + index].enabled = true;
					window["price_incl_input" + index].dataBind();
				}else{
					window["qty_input" + index].enabled = false;
					window["qty_input" + index].dataBind();
					window["price_input" + index].enabled = false;
					window["price_input" + index].dataBind();
					window["price_incl_input" + index].enabled = false;
					window["price_incl_input" + index].dataBind();
				}
			});
			if(product_set){
				shipping.enabled = false;
				import_tax.enabled = false;
			}else{
				shipping.enabled = false;
				import_tax.enabled = false;
			}
		}
	}
	
	function load_products(){
	
		var account_id = $("#account_id").val();
		
	
		var type = 'supplier';
	
		$.ajax({
			url: '/supplier_products/'+account_id+'/'+type,
			dataType:"json",
			success: function(data){
				
				window.products = data;
				$('.products').each(function(index) {
					window["product_input" + index].dataSource = window.products;
					window["product_input" + index].dataBind();
				});
			}
		});
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
				        created: function(e){
							$(".serviceaccountrow").css("display", "flex");
				        },
					});
					reseller_users.appendTo('#reseller_user');
				}
			});
		}
	}
	
	function calculate_document_total() {
	
		var tax = 0;
	
	
		var subtotal = 0;
		
		var documentDate = new Date(doc_docdate.value);
		var taxchangeDate = new Date('2018-04-01');
		var vat_percentage = 0.15;
		if (documentDate < taxchangeDate) {
		var vat_percentage = 0.14;
		}

	
		$('.products').each(function(index) {
			var line_total = window["qty_input" + index].value * window["price_input" + index].value;
			if(window["frequency_input" + index].value == 'monthly' || window["frequency_input" + index].value == 'annually'){
		
			$(".full_price").eq(index).val(window["product_input" + index].itemData.full_price);
			}else{
		
			}
			window["line_total_input" + index].value = line_total;
			if($("#vat_enabled").val() == 1){
				window["line_total_tax_input" + index].value = line_total + (line_total * vat_percentage);	
			}else{
				window["line_total_tax_input" + index].value = line_total;	
			}
			subtotal += line_total;
			
		
		
		
		});
	
	
		
		if($("#vat_enabled").val() == 1){
			
			var tax = parseFloat(subtotal) * vat_percentage;
		}
		
		tax_input.value = tax; 
		tax_input.dataBind();
		subtotal_input.value = subtotal;
		subtotal_input.dataBind();
		
		var grand_total = subtotal + tax;
	
		if(import_tax_usd.value && !isNaN(import_tax_usd.value)){
			grand_total = grand_total + parseFloat(import_tax_usd.value);
		}
		if(shipping_usd.value && !isNaN(shipping_usd.value)){
			grand_total = grand_total + parseFloat(shipping_usd.value);
		}
	
		total_rands_input.value = grand_total * parseFloat('{{ $document->exchange_rate }}');
		total_rands_input.dataBind();
		total_input.value = grand_total;
		total_input.dataBind();
		
		
		
	}
	
	
	@if(!$is_supplier)
		$(document).off('click', '#add_delivery').on('click', '#add_delivery', function() {
		
			$.when(insert_line()).then(set_line_inputs(true,135,1,'once off',0));
			var rowCount = $('#lines tr').length;
		
			if(rowCount == 1){
				$('.remove').hide();
			}else{
				$('.remove').css("display", "flex");
			}
		});
	@if(check_access('1,31'))
		$(document).off('click', '#add_discount').on('click', '#add_discount', function() {
			$.when(insert_line()).then(set_line_inputs(true,731,1,'once off',0));
		
			var rowCount = $('#lines tr').length;
		
			if(rowCount == 1){
				$('.remove').hide();
			}else{
				$('.remove').css("display", "flex");
			}
			
		});
	@endif
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
      window["line_total_tax_input" + rowLoop] =  window["line_total_tax_input" + oldIndex];
      window["frequency_input" + rowLoop] = window["frequency_input" + oldIndex];
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

	#page-wrapper{
		background-color: #fbfbfb;
		padding: 2%;
		margin-top:3%;
		margin-bottom:3%;
		box-shadow: 0 0 0.2cm rgba(0,0,0,0.3);
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
	
	

	#document_info strong, #document_info b, #document_branding strong, #document_branding b{
		font-weight: 600;
		font-size: 14px;
	}
	

	.e-numerictextbox{
		text-align:right;
	}
	.serviceaccountrow{
		display: none;
	}
	.input-required{
		border: 1px solid red !important;
	}
	.hidebtn{
		display:none !important;
	}
</style>
@endpush