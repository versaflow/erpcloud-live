
{!! Form::open(array("url"=> "company_info_edit", "class"=>"form-horizontal" , "parsley-validate"=>"","novalidate"=>" ","id"=> "company_info")) !!}		
<div class="row mt-3">
    <div class="col">
        <div class="card">
            <div class="card-header">
               Company Info
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="vat_number">  Vat No </label>
                    </div>
                    <div class="col-md-9">
                        <input id="vat_number" name="vat_number" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="company_registration_number">  Co Reg No </label>
                    </div>
                    <div class="col-md-9">
                        <input id="company_registration_number" name="company_registration_number"/>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="address">  Address </label>
                    </div>
                    <div class="col-md-9">
                    <textarea id="address" name="address" rows="5" class='e-input'>{{$address}}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="bank_details">  Bank details </label>
                    </div>
                    <div class="col-md-9">
                    <textarea id="bank_details" name="bank_details" rows="5" class='e-input'>{{$bank_details}}</textarea>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 text-left align-self-center">
                    <label for="notes">  Notes </label>
                    </div>
                    <div class="col-md-9">
                    <textarea id="notes" name="notes" rows="5" class='e-input'>{{$notes}}</textarea>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>


{!! Form::close() !!}

<script type="text/javascript">
$(document).ready(function() {
    
    vat_number = new ej.inputs.TextBox({
		placeholder: "Vat No",
        value: "{{ $vat_number }}",
	});
    vat_number.appendTo('#vat_number');
    
    company_registration_number = new ej.inputs.TextBox({
		placeholder: "Co Reg No",
        value: "{{ $company_registration_number }}",
	});
    company_registration_number.appendTo('#company_registration_number');
    
    address = new ej.inputs.TextBox({
		floatLabelType: 'Auto',
    });
    address.appendTo("#address");
    
    bank_details = new ej.inputs.TextBox({
		floatLabelType: 'Auto',
    });
    bank_details.appendTo("#bank_details");
    
    notes = new ej.inputs.TextBox({
		floatLabelType: 'Auto',
    });
    notes.appendTo("#notes");
});
$('#company_info').on('submit', function(e) {
   
	e.preventDefault();
    formSubmit("company_info");
});
</script>