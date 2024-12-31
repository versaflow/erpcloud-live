@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )
@php
$form_uuid = gen_uuid();
@endphp
@if(!request()->ajax())
	
@endif

@section('styles') 

<link href="{{ '/assets/formio/formio.full.min.css' }}" rel="stylesheet">
@endsection
@section('scripts') 

@endsection
@section('content')
<div id="container" style="background-color:transparent">
<div id="{!! $form_uuid !!}" class="formio_form p-0"></div>
</div>
@if(check_access(1))
<a id="form_edit_link" class="btn btn-primary btn-sm float-right" style="display:none" href="/{{$module_fields_url}}?module_id={{$module_id}}"> Edit Form </button>
<a id="form_builder_link" class="btn btn-primary btn-sm float-right" style="display:none" href="/formio_builder/{{$module_id}}"> Form Builder </button>
@endif

@endsection

@push('page-scripts') 
<script>

// testing url
var submit_url = '/formio_submit';
@if(!empty($menu_route))
var submit_url = '/{{ $menu_route }}/save';
@endif


blur_event = false;

@if(is_array($form_data) && count($form_data) > 0)
prefilledDataObject = {!! json_encode($form_data) !!};
@else 
prefilledDataObject = null;
@endif

Formio.icons = 'fontawesome';

//Formio.use(semantic);
//Formio.Templates.framework = "semantic";


Formio.Components.addComponent('colorpicker', ColorPickerComponent);
Formio.createForm(
document.getElementById('{!! $form_uuid !!}'), 
{!! $form_json !!}, 
{
  submitMessage: "",
  @if($form_readonly)
  readOnly: true
  @else
  readOnly: false
  @endif
}
).then(function(form) {
  if(prefilledDataObject){
    ////console.log(prefilledDataObject);
    form.submission = {
       data: prefilledDataObject
    };
  }
  // Prevent the submission from going to the form.io server.
  form.nosubmit = true;
    
  form.on('error', function(errors) {
    ////console.log('form_errors');
    ////console.log(errors);
  });
  form.on('submit', function(submission) {
    //console.log('submit');
    //console.log(submission);
    
    window['form_enter_key'] = false;
    /*
    return Formio.fetch('/formio_submit', {
      body: JSON.stringify(submission),
      headers: {
        'content-type': 'application/json'
      },
      method: 'POST',
      mode: 'cors',
    })
    .then(function(response) {
      ////console.log(response);
      form.emit('submitDone', submission)
      ////console.log(response.json());
    })
    */
    $.ajax({
        method: "post",
        url: submit_url,
        data: JSON.stringify(submission.data),
        contentType: 'application/json',
        processData: false,
        beforeSend: function(e) {
          showSpinner();
        },
        success: function(res) {
            
            ////console.log(res);
            hideSpinner();
            form.emit('submitDone', submission);
          
            if(res.status == 'error'){
            form.setAlert('danger', res.message);
            }else{
            form.setAlert(res.status, res.message);
            }
            
            processAjaxSuccess(res);
            ////console.log(res);
            if(res.status != 'warning' && res.status != 'error'){
              //closeActivePopup();  
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinner();
            form.emit('submitDone', submission);
            form.setAlert('danger', 'Unexpected Error');
            form.showAlert();
        },
    });
  });
  
  form.on('change', function(event, flags, modified) {
    // Called for every component.
    ////console.log(event);
    ////console.log(flags);
    ////console.log(modified);
    @foreach($form_change_events as $key => $value)
    if(modified === true && event.changed && event.changed.component && event.changed.component.key == '{{$key}}'){
    
      if(event.changed.component.type == 'select'){
        form.emit('{{$value}}');
        blur_event = false;
      }else{
        blur_event = '{{$value}}';
      }
    }
    @endforeach
  });
  
  form.on('blur', function() {
    // Called for every component.
    if(blur_event){
      form.emit(blur_event);
      blur_event = false;
    }
  });
  
  window['formio_{!! $form_uuid !!}'] = form;
});


function formio_submit(form_uuid){

  window['formio_'+form_uuid].submit();
}
$(document).on('keyup keypress', function(e) {
  var keyCode = e.keyCode || e.which;
  if (keyCode === 13) { 
    
    window['form_enter_key'] = true;
    e.preventDefault();
    
    e.stopPropagation();
    return false;
  }
});
</script>


@endpush

@push('page-styles') 
<style>

.ui.grid>[class*="six wide"].column {
    width: 50%!important;
}
#formio .fa, #formio .far, #formio .fas {
    font-family: "FontAwesome"  !important;
}
.help-block{
  display: none !important;  
}

.form-group.formio-hidden{
      margin-bottom: 0 !important;
}
@if($form_readonly)
.formio-component-submit{
  display:none;
}
@endif

.ui.selection.dropdown {
    padding: 0.6em 2.1em 0.2em 1em;
    min-height: 0;

}
.choices__list--dropdown .choices__item{
    padding: .3em 1em;
}

.ui.form input:not([type]), .ui.form input[type=date], .ui.form input[type=datetime-local], .ui.form input[type=email], .ui.form input[type=file], .ui.form input[type=number], .ui.form input[type=password], .ui.form input[type=search], .ui.form input[type=tel], .ui.form input[type=text], .ui.form input[type=time], .ui.form input[type=url] {
  
    padding: .3em 1em;
}

.ui.checkbox input[type=checkbox], .ui.checkbox input[type=radio] {

    top: -3px;
    left: 17px;
}

.choices__list--multiple .choices__item[data-deletable] {
    padding-right: 5px;
    display: inline-flex;
}
.ui.input.fluid .choices{
width: 100%;  
}
.ui.form .field .ui.input input, .ui.form .fields .field .ui.input input {
    width: 100% !important;
}
.ui.fluid.input {
    display: -webkit-box !important;
}
.ui.label>.icon {
    width: auto;
    margin: 0;
}

.ui.labeled.input>.label:not(.corner) {
   padding-bottom:0.5833em;
}
.formio-component-file .column strong{
  font-weight:normal;
}
.formio-component-file .column{
  font-size:12px;
}
</style>
@endpush