@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )
@php
$form_uuid = gen_uuid();
@endphp
@if(!request()->ajax())
	
@endif

@section('styles') 
<link href="{{ '/assets/formio/formio.full.min.css' }}" rel="stylesheet">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
@endsection
@section('scripts') 
@endsection
@section('content')
<div id="container" style="background-color:#fff">
<div class="p-4">
<button id="save_form{!! $form_uuid !!}" class="btn btn-primary float-right"> Save </button>
</div>
<div id="{!! $form_uuid !!}" class="p-4"></div>
</div>
@endsection

@push('page-scripts') 
<script>

window['changed_fields{!! $form_uuid !!}'] = {};
window['original_keys{!! $form_uuid !!}'] = {};
window['form_schema{!! $form_uuid !!}'] = false;
var form_json = {!! $form_json !!};

//Formio.use(semantic);
//Formio.Templates.framework = "semantic";

Formio.Components.addComponent('colorpicker', ColorPickerComponent);

Formio.builder(document.getElementById('{!! $form_uuid !!}'), form_json, {
  editForm: {
  },
  builder: {
    basic: false,
    advanced: false,
    data: false,
    resource: false,
    premium: false,
        
  }  
}).then(function(form){
  form.on("updateComponent", function(event){
    //console.log('updateComponent');
    //console.log(event);
    if(!window['original_keys{!! $form_uuid !!}'][event.id]){
      window['original_keys{!! $form_uuid !!}'][event.id] = event.key;
    }
    if(window['original_keys{!! $form_uuid !!}'][event.id] != event.key){
      window['changed_fields{!! $form_uuid !!}'][window['original_keys{!! $form_uuid !!}'][event.id]] = event.key;
    }
  });
  form.on("change", function(event){
    //console.log('change');
    //console.log(event);
    //console.log(window['changed_fields{!! $form_uuid !!}']);
    
    window['form_schema{!! $form_uuid !!}'] = form.schema;
  });
});



$(document).on('click','#save_form{!! $form_uuid !!}', function(){
    var form_json = window['form_schema{!! $form_uuid !!}'];

    //console.log(Object.keys(window['changed_fields{!! $form_uuid !!}']).length);
    //console.log(window['changed_fields{!! $form_uuid !!}']);
    if(Object.keys(window['changed_fields{!! $form_uuid !!}']).length > 0){
      //console.log('keys changed');
      var post_data = {dbkey_updates: JSON.stringify(window['changed_fields{!! $form_uuid !!}']), id: {{ $id }}, form_json: JSON.stringify(form_json) };
    }else{
      var post_data = {id: {{ $id }}, form_json: JSON.stringify(form_json) };  
    }
    //console.log(post_data);
    $.ajax({
        url: '/formio_save',
        data: post_data,
        type:'post',
        dataType: 'json',
        success:function(data){
            toastNotify(data.message,data.status);
        }
    });
});
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
.collapse:not(.show) {
    display: block;
}
#builder .fa, #builder .far, #builder .fas {
    font-family: "FontAwesome"  !important;
}

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
.choices__list--multiple .choices__item[data-deletable] {
    padding-right: 5px;
    display: inline-flex;
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