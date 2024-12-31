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
<div class="mx-4 mt-4">

<div class="row mb-2">
<div class="col-md-2">
<label style="font-weight:bold" for="form_role_id"> Role </label>
</div>
<div class="col">
<input name="form_role_id" id="form_role_id" >
</div>
</div>

<div class="row mb-2">
<div class="col-md-2">
<label style="font-weight:bold" for="copy_role_id"> Copy form to Role </label>
</div>
<div class="col">
<input name="copy_role_id" id="copy_role_id" >
</div>
</div>

<div class="row mb-2">
<div class="col-md-2">
<label style="font-weight:bold" for="is_view"> View </label>
</div>
<div class="col">
<input name="is_view" id="is_view" type="checkbox" value="1" >
</div>
</div>

<div class="row mb-2">
<div class="col-md-2">
<label style="font-weight:bold" for="is_add"> Add </label>
</div>
<div class="col">
<input name="is_add" id="is_add" type="checkbox" value="1" >
</div>
</div>

<div class="row mb-2">
<div class="col-md-2">
<label style="font-weight:bold" for="is_edit"> Edit </label>
</div>
<div class="col">
<input name="is_edit" id="is_edit" type="checkbox" value="1" >
</div>
</div>

<div class="row mb-2">
<div class="col-md-2">
<label style="font-weight:bold" for="is_delete"> Delete </label>
</div>
<div class="col">
<input name="is_delete" id="is_delete" type="checkbox" value="1" >
</div>
</div>
</div>
<div id="{!! $form_uuid !!}" class="formio_builder p-4"></div>
</div>
@endsection

@push('page-scripts') 
<script>

@if(!empty($is_add))
var checkbox = { label: 'Add', checked: true };
@else
var checkbox = { label: 'Add' };
@endif
form_add = new ej.buttons.Switch(checkbox,"#is_add");

@if(!empty($is_edit))
var checkbox = { label: 'Edit', checked: true };
@else
var checkbox = { label: 'Edit' };
@endif
form_edit = new ej.buttons.Switch(checkbox,"#is_edit");

@if(!empty($is_view))
var checkbox = { label: 'View', checked: true };
@else
var checkbox = { label: 'View' };
@endif
form_view = new ej.buttons.Switch(checkbox,"#is_view");

@if(!empty($is_delete))
var checkbox = { label: 'Delete', checked: true };
@else
var checkbox = { label: 'Delete' };
@endif
form_delete = new ej.buttons.Switch(checkbox,"#is_delete");

window['form_schema{!! $form_uuid !!}'] = false;
@if(!empty($form_json))
var form_json = {!! $form_json !!};
@else
var form_json = null;
@endif


// hide active fields

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
    @if(!empty($available_fields))
    custom: {
      title: 'Fields',
      weight: 10,
      components: {!! json_encode($available_fields) !!}
    }
    @endif
        
  }  
}).then(function(formbuilder){

  window['builder_instance'] = formbuilder;
  

  window['form_schema{!! $form_uuid !!}'] = formbuilder.schema;
  
  
  formbuilder.on("removeComponent", function(event,arg1,arg2,arg3,arg4){
    
    $('span[data-key="created_at"').hide();
    update_formio_predefined_fields(formbuilder);
  });
  formbuilder.on("addComponent", function(event,arg1,arg2,arg3,arg4){

    //$('span[data-key="created_at"').hide();
    update_formio_predefined_fields(formbuilder);
  
  });
  
  formbuilder.on("updateComponent", function(event){
    
  });
  formbuilder.on("change", function(event){
    ////console.log('change');
    ////console.log(event);
    ////console.log(window['changed_fields{!! $form_uuid !!}']);
    
    window['form_schema{!! $form_uuid !!}'] = formbuilder.schema;
  });
  return formbuilder;
});


function update_formio_predefined_fields(formbuilder){
  /*
    post_data = {form_json: JSON.stringify(formbuilder.schema)};
    //console.log(post_data);
    $.ajax({
        url: '/formio_builder_ajax_fields/{{$id}}',
        data: post_data,
        type:'post',
        dataType: 'json',
        success:function(data){
          //console.log(data);
          //console.log(data.component_order);
          //console.log(data.components);
          //console.log(window['builder_instance']);
             
            window['builder_instance'].updateBuilderGroup("custom", {
            title: "Fields",
            key: "customfields",
            weight: 10,
            subgroups: [],
            componentOrder: data.component_order,
            components: data.components
            });
            
          
            
            window['builder_instance'].redraw();
            
    
        }
    });
    */
}


$(document).off('click', '#save_form{!! $form_uuid !!}').on('click', '#save_form{!! $form_uuid !!}', function() {  
 
  formio_builder_save(true);
	
});

form_role_id = new ej.dropdowns.DropDownList({ 
    mode: 'CheckBox',
    placeholder: "Role",
    dataSource: {!! json_encode($roles) !!},
    fields: {text: 'name', value: 'id'},
    @if($role_id)
    value: {{ $role_id }},
    @endif
    showClearButton: true,
    enabled: true,
},'#form_role_id');

copy_role_id = new ej.dropdowns.DropDownList({ 
    mode: 'CheckBox',
    placeholder: "Copy form to Role",
    dataSource: {!! json_encode($roles) !!},
    fields: {text: 'name', value: 'id'},
    enabled: true,
},'#copy_role_id');


function formio_builder_save(){
    var form_json = window['form_schema{!! $form_uuid !!}'];
    //console.log(form_json);
    //if(Object.keys(window['changed_fields{!! $form_uuid !!}']).length > 0){
      ////console.log('keys changed');
    //  var post_data = {dbkey_updates: JSON.stringify(window['changed_fields{!! $form_uuid !!}']), id: {{ $id }}, form_json: JSON.stringify(form_json) };
    //}
  
    var post_data = {
    id: '{{ $id }}',
    module_id: '{{ $module_id }}',
    form_json: JSON.stringify(form_json),
    role_id: form_role_id.value,
    copy_role_id: copy_role_id.value,
    is_add: (form_add.checked) ? 1 : 0,
    is_edit: (form_edit.checked) ? 1 : 0,
    is_view: (form_view.checked) ? 1 : 0,
    is_delete: (form_delete.checked) ? 1 : 0,
    };  

   
    //console.log(post_data);
   
    $.ajax({
        url: '/formio_save',
        data: post_data,
        type:'post',
        dataType: 'json',
        beforeSend: function(e) {
          showSpinner();
        },
        success:function(data){
             try {
            get_sidebar_data();
            }catch (e) {
                //console.log('get_sidebar_data error');
                //console.log(e);
            }
           //console.log(data);
            hideSpinner();
            
            if(data.status == 'success'){ 
             
              
              window['close_sidebar_callback'] = true;
             
              @if(!empty(request()->redirect_to) && !empty(session('form_builder_redirect')))
                setTimeout(function(){
                  //var modal_id = makeid(5);
                  //sidebarform(modal_id,"{{ session('form_builder_redirect') }}");
                },500);  
              @endif
              
              
            }else{
              if(data.status == 'error'){
              //  data.status == 'danger';
              }
               
            }
             toastNotify(data.message, data.status);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinner();
           
           
        },
    });
}



</script>
@endpush

@push('page-styles') 
<style>
.input-text-right input{
text-align:right;    
}

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

.ui.formbuilder input:not([type]), .ui.formbuilder input[type=date], .ui.formbuilder input[type=datetime-local], .ui.formbuilder input[type=email], .ui.formbuilder input[type=file], .ui.formbuilder input[type=number], .ui.formbuilder input[type=password], .ui.formbuilder input[type=search], .ui.formbuilder input[type=tel], .ui.formbuilder input[type=text], .ui.formbuilder input[type=time], .ui.formbuilder input[type=url] {
  
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
.ql-editor {
    min-height: 150px;
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
.formio-choices.form-group {
    margin-bottom: 0 !important;
}

.formio-component-multiple .choices__input.choices__input--cloned{
display:none;  
}

.ui.disabled.input, .ui.input:not(.disabled) input[disabled] {
  
    background-color: #ccc;
}

.ui.selection.dropdown[disabled] {
  
    background-color: #ccc;
}
.formio-component-file .ui.grid strong{
  display:none;  
}
.formio-component-file .item{
  border:none !important;  
}

.formio_form button[type=submit]{
display:none !important;
}

.formio_form label {
    font-weight: bold;
}
.formio-select-autocomplete-input{
display:none;  
}

.form-control.selection.dropdown {
    min-height: 32px;
    line-height: 16px;
}
.ui.label{
    min-height: 32px;
}
.formio-hidden{
margin:0px !important;  
}
  
.form-group .form-control{
border-color: #cccccc !important;
border-radius:5px !important;
min-height:32px;
}

.formio-component-textarea .ql-toolbar.ql-snow {
    border-top-right-radius: 5px;
    border-top-left-radius: 5px;
}
.formio-component-textarea .ql-container.ql-snow {
    border-bottom-right-radius: 5px;
    border-bottom-left-radius: 5px;
}
</style>
@endpush