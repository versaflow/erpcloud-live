@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )
@php
$form_uuid = gen_uuid();
@endphp
@if(!request()->ajax())
	
@endif

@section('styles') 
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<link href="{{ '/assets/formio/formio.full.min.css' }}" rel="stylesheet">

@endsection
@section('scripts') 

@endsection
@section('content')

<div id="form_toolbar{{$module_id}}" style="height:40px;"></div> 

<div id="container" style="background-color:transparent" class="m-2">
<div id="{!! $form_uuid !!}" class="formio_form p-0 h-100" style="display:none"></div>
</div>

<div id="form_toolbar{{$module_id}}_title">
<h6 class="mb-0 d-inline pl-4">{!! $form_title !!}</h6>
</div>
@if($form_description > '')
<div id="form_toolbar{{$module_id}}_description">
<span id="form_description_tooltip{{$module_id}}" class="grid-tooltip ml-1 far fa-question-circle"></span>
</div>
@endif
<div id="form_toolbar{{$module_id}}_result">
</div>
<div id="form_toolbar{{$module_id}}_submit">
<button id="form_toolbar{{$module_id}}_submit_btn" class="sidebarbtn sidebarformbtn e-btn k-button mr-2 float-right e-primary">Submit</button>
<button id="form_toolbar{{$module_id}}_close_btn" class="sidebarbtn e-btn k-button mr-2 float-right">Close</button>
<button id="form_toolbar{{$module_id}}_min_btn" class="sidebarbtn e-btn k-button mr-2 float-right">Minimize</button>
</div>

@endsection

@push('page-scripts') 
<script>

//admin toolbar
@if( $form_description > '')
    new ej.popups.Tooltip({
    enableHtmlParse: true,
    cssClass: 'description-tooltip',
    content: '{!! str_replace("'","",$form_description) !!}',
    position:'RightBottom',
    },'#form_description_tooltip{{$module_id}}');
@endif
   
    $("#form_toolbar{{$module_id}}_close_btn").click(function(){
          window['close_sidebar_callback'] = true;
          sidebarformcontainer.hide();
    });
    
    $("#form_toolbar{{$module_id}}_min_btn").click(function(){
         $("#showrightsidebar").removeClass('d-none');
          window['close_sidebar_callback'] = true;
          sidebarformcontainer.hide();
    });
        
    $("#form_toolbar{{$module_id}}_submit_btn").click(function(){
      
         
            var formio_uuid = '{!! $form_uuid !!}';
            formio_submit(formio_uuid);
                   
    });
   
    window['form_toolbar{{$module_id}}'] = new ej.navigations.Toolbar({
        items: [
            { template:'#form_toolbar{{$module_id}}_title', align: 'left'},
            @if($form_description > '')
            { template:'#form_toolbar{{$module_id}}_description', align: 'left'},
            @endif
            { template:'#form_toolbar{{$module_id}}_result', align: 'right'},
            { template:'#form_toolbar{{$module_id}}_submit', align: 'right'},
            
        ]
    });
    window['form_toolbar{{$module_id}}'].appendTo('#form_toolbar{{$module_id}}');
    
    
$(".sidebarbtn").removeAttr("disabled");




$(document).off('click', '.tab-container a').on('click', '.tab-container a', function(e){
  setTimeout(function(){
  $('textarea').each(function(i, el) {
      if( $('#'+$(el).attr('id')) &&  $('#'+$(el).attr('id'))[0] &&  $('#'+$(el).attr('id'))[0].widget){
       $('#'+$(el).attr('id'))[0].widget.componentInstance.redraw();
      }
  });
  },200);
});

// testing url
var submit_url = '#';
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
    //////console.log(prefilledDataObject);
    form.submission = {
       data: prefilledDataObject
    };
  }
  
  @if(!empty($name_field))
    setTimeout(function(){
      var component = form.getComponent("{{$name_field}}");
      component.focus();
     
    },1100);
  @else
    setTimeout(function(){$("#{!! $form_uuid !!} input").first().focus();},1100);
  @endif
  
  
  
  
  
  setTimeout(function(){$("#{!! $form_uuid !!}").show();},500);
  // Prevent the submission from going to the form.io server.
  form.nosubmit = true;
  // https://help.form.io/developers/form-renderer#form-events
    
  form.on('submit', function(submission) {
    if(submit_url == "#"){
      
      form.setAlert('danger', 'Invalid submit url');
      return false;  
    }
    
    
   
    $(".sidebarbtn").attr("disabled","disabled");
  
    //////console.log('submit');
    //////console.log(submission);
    
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
      //////console.log(response);
      form.emit('submitDone', submission)
      //////console.log(response.json());
    })
    */
    $.ajax({
        method: "post",
        url: submit_url,
        data: JSON.stringify(submission.data),
        contentType: 'application/json',
        processData: false,
        beforeSend: function(e) {
           
                spinner_ref = "#{!! $form_uuid !!}";
            
            showSpinner(spinner_ref);
            window['sidebar_form_saving'] = true;
            window['close_sidebar_callback'] = true;
          sidebarformcontainer.hide();
        },
        success: function(res) {
            
            window['sidebar_form_saving'] = false;
            hideSpinner(spinner_ref);
            if(res && res.status && res.status != 'success'){
                sidebarformcontainer.show();
                $(".sidebarbtn").removeAttr("disabled");  
            }
            processAjaxResponse(res,form,submission);
            
            if(res.status == "success"){
             
            
              
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            window['sidebar_form_saving'] = false;
          
            sidebarformcontainer.show();
            $(".sidebarbtn").removeAttr("disabled");
            hideSpinner(spinner_ref);
           
            setTimeout(function(){form.setAlert('danger', 'Unexpected Error');},500);
            //form.showAlert();
        },
    });
  });
  
  form.on('change', function(event, flags, modified) {
   //////console.log('change event');
   //////console.log(event);
   //////console.log(flags);
   //////console.log(modified);
    // Called for every component.
    //////console.log('change');
    //////console.log(event);
    //////console.log(flags);
    //////console.log(modified);
    @foreach($form_change_events as $key => $value)
    if(modified === true && event.changed && event.changed.component && event.changed.component.key == '{{$key}}'){
    
      if(event.changed.component.type == 'select'){
        form.emit('{{$value}}');
        blur_event = false;
      }else{
        blur_event = '{{$value}}';
        form.emit(blur_event);
      }
    }
    @endforeach
  });
  
  form.on('blur', function() {
   ////console.log('blur event');
    // Called for every component.
    if(blur_event){
      //form.emit(blur_event);
      blur_event = false;
    }
  });
  
  
  
 
  window['formio_{!! $form_uuid !!}'] = form;
});
 
 
  
  

function formio_submit(form_uuid){

  window['formio_'+form_uuid].submit();
}

// submit on enter key
$(document).ready(function() {
    $('#{!! $form_uuid !!} input').keyup(function(event) {
        if (event.which === 13)
        {
            event.preventDefault();
            window['formio_{!! $form_uuid !!}'].submit();
        }
    });
});


function processAjaxResponse(data, form, submission) {
    ////console.log('processAjaxResponse');
    ////console.log(data);
    ////console.log(form);
    ////console.log(submission);
    try { 
        window['update_menu_manager_datasource']();
    }catch (e) {}
  
    try {
    if(data.callback_function){
    window[data.callback_function]();
    }
    }catch (e) {}
    if (data.provision_id) {
        try {
            
          
            dialog.hide();
            viewDialog('provison', data.provision_url, 'Provision', "70%");
        }
        catch (e) {
            
            console.error(e);
        }
    }
  
    if (data.print) {
        try {
            printPDF(data.print);
                ////console.log('dialogclose3');
            dialog.hide();
        }
        catch (e) {}
        return false;
    }
    
    if(data && data.status && data.message){
        if(data.status == 'success' || data.status == 'info' || data.status == 'warning' || data.status == 'error'){
            toastNotify(data.message, data.status);   
        }
    }
                     //console.log('ajax1');
    //console.log(data);
    if(data && data.status && data.status == 'success'){
      
      
      //close sidebar
      ////console.log(data);
      try {
        window['close_sidebar_callback'] = true;
  
        sidebarformcontainer.hide();
      }catch (e) {
        ////console.log(e);
      }
                     //console.log('ajax2');
      //console.log(data);
      
      if(data.master_module_id && data.row_id){
          
          //refresh grid
          var globalprops = getGlobalProperties('grid_'+data.master_module_id);
          $(globalprops).each(function(i, el) {
      
              ////console.log(el);
              if (el != 'grid_height' && el != 'grid_default' && el != 'grid_module_id' && el != 'grid_config_id' && el.toLowerCase().indexOf("grid_layout_id") === -1) {
                  try {
                     //console.log('0refreshrow');
                    window[el].gridOptions.refreshRow(data.row_id, data.new_record);
                  }catch (e) {
                      //console.log('0refreshrow err');
                      //console.log(e);
                  }
              }
          });
      }else if(data.module_id && data.row_id){
          
          //refresh grid
          var globalprops = getGlobalProperties('grid_'+data.module_id);
          $(globalprops).each(function(i, el) {
      
              ////console.log(el);
              if (el != 'grid_height' && el != 'grid_default' && el != 'grid_module_id' && el != 'grid_config_id' && el.toLowerCase().indexOf("grid_layout_id") === -1) {
                  try {
                     //console.log('1refreshrow');
                    window[el].gridOptions.refreshRow(data.row_id, data.new_record);
                  }catch (e) {
                      //console.log('1refreshrow err');
                      //console.log(e);
                  }
              }
          });
      }else{
          //refresh grid
          var globalprops = getGlobalProperties('grid_');
          $(globalprops).each(function(i, el) {
      
              ////console.log(el);
              if (el != 'grid_height' && el != 'grid_default' && el != 'grid_module_id' && el != 'grid_config_id' && el.toLowerCase().indexOf("grid_layout_id") === -1) {
                  try {
                      //console.log('1refreshgrid');
                      window[el].gridOptions.refresh();
                      
                  }catch (e) {
                      //console.log('1refreshgrid err');
                      //console.log(e);
                  }
              }
          });
      }
      
      ////console.log('submitDone');
      //show alert      
      setTimeout(function(){form.setAlert(data.status, data.message);},500);
      //form.showAlert();
      form.emit('submitDone', submission);
      
      
      
      if (data.reload) {
          setTimeout(function() {
              window.open(data.reload, "_self");
          }, 1000);
      }

      if (data.new_tab) {
          setTimeout(function() {
              window.open(data.new_tab, "_blank");
          }, 1000);
      }

      if(data.refresh_instant == 1) {
        location.reload();
      }
      if (data.refresh) {
          setTimeout(function() {
              location.reload();
          }, 1000);
      }
      if(data.reload_grid_views){
          reload_grid_views();
      }
      
      if(data.layout_create){
          layout_create(data.row_id);
      }
      if(data.reload_grid_config){
        try {
        var rgc = data.reload_grid_config;
        window[rgc]();
        }catch (e) {}
     
      }
      if(data.reload_conditional_styles){
          reload_conditional_styles(data.module_id);
      }  
      if (data.view_dialog) {
          viewDialog(1 , data.view_dialog, data.dialog_title, '90%',  '90%');
      } 
      if (data.form_dialog) {
          formDialog(1 , data.view_dialog, data.dialog_title, '90%',  '90%');
      } 
      
    
    }else{
     
      ////console.log(data);
      if (data.status == 'msgPopup') {
          msgPopup(data.title, data.content);
      }else if (data.status == 'sidebarform') {
          var modal_title = '';
          if (data.modal_title > '') {
              modal_title = data.modal_title;
          }
          sidebarform('viewDialog', data.url, modal_title, '','80%' );
      }else if (data.status == 'viewDialog') {
          var modal_title = '';
          if (data.modal_title > '') {
              modal_title = data.modal_title;
          }
          viewDialog('viewDialog', data.url, modal_title, '80%', '50%', '');
      } else if (data.status == 'querybuilder') {
          formDialog('querybuilder',  data.url, 'Query Builder', '60%');
      }else if (data.status == 'formDialog') {
          var modal_title = '';
          if (data.modal_title > '') {
              modal_title = data.modal_title;
          }
          formDialog('formDialog', data.url, modal_title, '50%', '50%', '', 'Submit');
      }
      else if (data.status == 'transactionDialog') {
          transactionDialog('edittrx', data.message, 'Edit Transaction', '80%', '100%');
      }
      else if (data.status == 'reload') {

          window.open(data.message, "_self");
      }
      else if (data.status == 'emailerror') {
        setTimeout(function(){form.setAlert('danger', data.message);},500);
        //form.showAlert();
      }
      else if (data.status == 'error') {
        ////console.log('error');
        ////console.log(data);
        setTimeout(function(){form.setAlert('danger', data.message);},500);
        //form.showAlert();
      }
      else if (data.status == 'warning') {
        ////console.log('warning');
        ////console.log(data);
        setTimeout(function(){form.setAlert('danger', data.message);},500);
        //form.showAlert();
      }
      else if (data.status == 'refresh_instant') {
         location.reload();
      }
      else {
        ////console.log('break');
        ////console.log(data);
    
        setTimeout(function(){form.setAlert(data.status, data.message);},500);
        //form.showAlert();
      }
    }
}

/*
var context_items = [
    {
        id: "context_gridtab_edit",
        text: "Edit Menu",
        iconCss: "fas fa-list",
        url: 'sf_menu_manager/{{$module_id}}/gridtab',
        data_target: 'view_modal',
    },
];
var menuOptions = {
    target: '#tabs_container .e-tab-header',
    items: context_items,
    beforeItemRender: contextmenurender
};

// Initialize ContextMenu control
new ej.navigations.ContextMenu(menuOptions, '#gridtab_context');
*/
</script>


@endpush

@push('page-styles') 
<style>
#form_toolbar{{$module_id}}, #form_toolbar{{$module_id}} .e-toolbar-items{
background: #d4d4d4;
}

.input-text-right input{
text-align:right;    
}
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


/* tooltip icons */
[data-tooltip]:before {
    opacity: 1;
    position: static;
}
[data-tooltip]:after,[data-tooltip]:hover:after{
    visibility: hidden;
}
[data-tooltip]:before {
    visibility: visible;
}
@foreach($module_fields as $field)
@if($field['label'] == 'Name')
.formio-component-{{ $field['field'] }}{
    background-color:#cecece;
    font-weight: 500;  
    padding: 6px;
    border-radius:5px;
    
}
@endif
@endforeach
.formio-component .ace_editor{
height: 400px !important;
min-height: 400px;
}
.ace_editor, .ace_editor div{
    font-family:monospace  !important;
}
</style>
@endpush