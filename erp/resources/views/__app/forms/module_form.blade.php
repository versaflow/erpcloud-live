@extends(( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' )
@php
$form_uuid = gen_uuid();
@endphp
@if(!request()->ajax())
	
@endif


@section('content')

@if(is_dev() || is_superadmin())
<ul id="formfields_context_ul{{$module_id}}" class="m-0"></ul>
<ul id="formtabs_context_ul{{$module_id}}" class="m-0"></ul>
@endif
<div id="container" style="background-color:transparent" class="m-2">
<!-- Create Dropdown -->




<div id="{!! $form_uuid !!}" class="formio-form formio_form p-0 h-100" style="display:none"></div>
</div>
@endsection

@push('page-scripts') 
<script>

left_form{{$module_id}} = $('#{!! $form_uuid !!}').closest('.e-sidebar').hasClass('sidebar_leftformcontainer');

@if(is_superadmin() && $module_id==348)
////console.log($("#{!! $form_uuid !!}").closest('.sidebarformcontainer').find('.toggle_overlay_btn'));
$("#{!! $form_uuid !!}").closest('.sidebarformcontainer').find('.toggle_overlay_btn').removeClass('d-none');
@endif

        
    
var sidebarcontainer_ref{{$module_id}} = false;
if($("#{!! $form_uuid !!}").closest('.sidebarformcontainer').length > 0){
    sidebarcontainer_ref{{$module_id}} = $("#{!! $form_uuid !!}").closest('.sidebarformcontainer').attr('id');
    ////console.log(sidebarcontainer_ref{{$module_id}});
}

//admin toolbar
@if( $form_description > '')
    new ej.popups.Tooltip({
    enableHtmlParse: true,
    cssClass: 'description-tooltip',
    content: '{!! str_replace("'","",$form_description) !!}',
    position:'RightBottom',
    },'#form_description_tooltip{{$module_id}}');
@endif
   
  
    	$("#form_toolbar_submit_approve_btn").addClass('d-none');
	
		$("#form_toolbar_submit_email_btn").addClass('d-none');
		$("#form_toolbar_submit_btn").text('Submit');
    




$(document).off('click', '.tab-container a').on('click', '.tab-container a', function(e){
  setTimeout(function(){
  $('textarea').each(function(i, el) {
         //console.log($(el));
      if( $('#'+$(el).attr('id')) &&  $('#'+$(el).attr('id'))[0] &&  $('#'+$(el).attr('id'))[0].widget){
          //console.log($('#'+$(el).attr('id'))[0].widget);
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


Formio.setBaseUrl("{{ url('/') }}");
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
    @if(is_dev())
    /*
    form.everyComponent((component) => {
        if(component.component && component.component.editor && component.component.editor == "ckeditor"){
            //console.log("ckeditor");
            //console.log(component);
            component.editors.forEach(function(ckEditorInstance) {
            //console.log('ckeditor');
            //console.log(ckEditorInstance);
            // Get the CKEditor instance
            
            
            // Apply settings to remove double line breaks
            ckEditorInstance.on('instanceReady', function(ev) {
            ev.editor.dataProcessor.writer.setRules('p', {
            indent: false,
            breakBeforeOpen: false,
            breakAfterOpen: false,
            breakBeforeClose: false,
            breakAfterClose: false
            });
            });
            });
            
        }
    });
    */
  @endif 
  
  
  if(prefilledDataObject){
    //////console.log(prefilledDataObject);
    form.submission = {
       data: prefilledDataObject
    };
  }
  

    setTimeout(function(){$("#{!! $form_uuid !!} input").first().focus();},1100);
 
  
  
  
  
  
  setTimeout(function(){$("#{!! $form_uuid !!}").show();},500);
  @if(is_dev() || is_superadmin())
  setTimeout(create_form_context{{$module_id}},500);
  @endif
  // Prevent the submission from going to the form.io server.
  form.nosubmit = true;
  // https://help.form.io/developers/form-renderer#form-events
    
  form.on('submit', function(submission) {
      
     
    var globalprops = getGlobalProperties('selectedrow_');
    $(globalprops).each(function(i, el) {
       window['ajax'+el] = window[el]
    });
    
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
    
    @if($popup_form_field)
    //console.log(submit_url);
    //console.log(submission.data);
    submission.data.popup_form_field='{{$popup_form_field}}';
    submission.data.popup_form_module_id='{{$popup_form_module_id}}';
    //console.log(submission.data);
    @endif
    $.ajax({
        method: "post",
        url: submit_url,
        data: JSON.stringify(submission.data),
        contentType: 'application/json',
        processData: false,
        beforeSend: function(e) {
           
             //   spinner_ref = "#{!! $form_uuid !!}";
            
           
            showSpinner();
           
         
            if(left_form{{$module_id}}){   
                window['sidebar_form_left_saving'] = true;
                window['sidebar_form_left_saving{{$module_id}}'] = true;
                window['close_sidebar_left_callback'] = true;
                window['close_sidebar_left_callback{{$module_id}}'] = true;
                sidebar_leftformcontainer.hide();
            }else{
                window['sidebar_form_saving'] = true;
                window['sidebar_form_saving{{$module_id}}'] = true;
                window['close_sidebar_callback'] = true;
                window['close_sidebar_callback{{$module_id}}'] = true;
                if(sidebarcontainer_ref{{$module_id}}){
                    window[sidebarcontainer_ref{{$module_id}}].hide();
                }else{
                    sidebarformcontainer.hide();
                }
            }
        },
        success: function(res) {
            
            if(left_form{{$module_id}}){ 
                
                window['sidebar_form_left_saving'] = true;
                window['sidebar_form_left_saving{{$module_id}}'] = true;
            }else{
                window['sidebar_form_saving'] = false;
                window['sidebar_form_saving{{$module_id}}'] = false;
            }
            hideSpinner();
            if(res && res.status && res.status != 'success'){
                
               
                if(sidebarcontainer_ref{{$module_id}}){
                    window[sidebarcontainer_ref{{$module_id}}].show();
                }else{
                    sidebarformcontainer.show();
                }
     
                
                $(".sidebarbtn").removeAttr("disabled");  
            }
            processAjaxResponse(res,form,submission);
            
            if(res.status == "success"){
             
            
              
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
          if(left_form{{$module_id}}){ 
                
                window['sidebar_form_left_saving'] = true;
                window['sidebar_form_left_saving{{$module_id}}'] = true;
            }else{
                window['sidebar_form_saving'] = false;
                window['sidebar_form_saving{{$module_id}}'] = false;
            }
            if(sidebarcontainer_ref{{$module_id}}){
                window[sidebarcontainer_ref{{$module_id}}].show();
            }else{
                sidebarformcontainer.show();
            }
            $(".sidebarbtn").removeAttr("disabled");
            hideSpinner();
           
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
 

function update_formio_val{{$module_id}}(data){
    //console.log('update_formio_val{{$module_id}}');
    //console.log(data);
    //console.log("window['formio_{!! $form_uuid !!}'].getComponent("+data.formio_field+".setValue("+data.row_id+");");
window['formio_{!! $form_uuid !!}'].getComponent(data.formio_field).setValue(data.row_id);    
}
  
  

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
    //console.log('processAjaxResponse');
    //console.log(data);
    //console.log(form);
    //console.log(submission);
    
   
   
    //console.log('left_form{{$module_id}}',left_form{{$module_id}});
    
    try { 
        window['update_menu_manager_datasource']();
    }catch (e) {}
      try { 
  refresh_account_accordion();
      }catch (e) {}
    try {
    if(data.callback_function_data){
    window[data.callback_function_data](data);
    }
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
          if(left_form{{$module_id}}){
                window['close_sidebar_left_callback'] = true;
                window['close_sidebar_left_callback{{$module_id}}'] = true;
                sidebar_leftformcontainer.hide();
          }else{
                window['close_sidebar_callback'] = true;
                window['close_sidebar_callback{{$module_id}}'] = true;
                
                sidebarformcontainer.hide();
          }
      }catch (e) {
        ////console.log(e);
      }
                     //console.log('ajax2');
      //console.log(data);
      
      
      //refresh grid
      var globalprops = getGlobalProperties('grid_');
      $(globalprops).each(function(i, el) {
  
          if (el != 'grid_height' && el != 'grid_default' && el != 'grid_module_id' && el != 'grid_config_id' && el.toLowerCase().indexOf("grid_layout_id") === -1) {
              try {
                  if(window[el] && window[el].gridOptions){
                  //console.log('1refreshgrid success');
                  window[el].gridOptions.refresh(data);
                  }
              }catch (e) {
                  //console.log('1refreshgrid err');
                  //console.log(e);
              }
          }
      });
      
      
      //console.log('submitDone');
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
            
      //console.log('reload_grid_config1');
        var rgc = data.reload_grid_config;
      //console.log(data.reload_grid_config);
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


</script>

<script>
@if(is_dev() || is_superadmin())
/* FIELDS CONTEXT MENU */

function create_form_context{{$module_id}}(){
   
    field_labels{{$module_id}} = {!! json_encode($field_labels) !!};

    if(typeof formfields_context{{$module_id}} !== 'undefined'){
        formfields_context{{$module_id}}.destroy();
    }
    
    var context_items = [
        {id: "field_name", text: "",cssClass: "font-weight-bold"},
        {id: "sort_form", text: "List"},
        {id: "add_field", text: "Add"},
        {id: "edit_field", text: "Edit"},
        {id: "duplicate_field", text: "Duplicate"},
        {id: "delete_field", text: "Delete"},
        {id: "set_visible", text: "Visible", items:[
            {id: "set_visible_none", text: "None"},
            {id: "set_visible_both", text: "Both"},   
            {id: "set_visible_add", text: "Add"},   
            {id: "set_visible_edit", text: "Edit"}
        ]},
        @if($module_id == 385)
        {id: "edit_code", text: "Edit Code"},
        @endif
    ];
    var menuOptions = {
        target: '.formio-component',
        items: context_items,
        beforeItemRender:function(args){
            
            var el = args.element; 
                    if(args.item.cssClass > '') {
            var el = args.element;
            $(el).addClass(args.item.cssClass);
            }
        },
        beforeOpen: function(args,e){
            //console.log('beforeOpen',args,e);
            try{
                if(args.parentItem === null){
                form_field_id{{$module_id}} = false;
                form_field_name{{$module_id}} = false;
                form_field_tab{{$module_id}} = false;
                if($(args.event.target).hasClass('formio-component') == 1){
                    var el = $(args.event.target);
                }else{
                    var el = $(args.event.target).closest('.formio-component');
                }
              
                var classList = $(el).attr('class').split(/\s+/);
                $.each(classList, function(index, item) {
                    //console.log(item);
                    if (item.includes('data-id-')) {
                        form_field_id{{$module_id}} = item.replace('data-id-', "");
                    }
                  
                    if (item.includes('data-field-')) {
                        form_field_name{{$module_id}} = item.replace('data-field-', "");
                    }
                    if (item.includes('data-tab-')) {
                        form_field_tab{{$module_id}} = item.replace('data-tab-', "");
                    }
                });
         
                if(form_field_id{{$module_id}} === false){
                    args.cancel = true;    
                }
                @if($module_id == 385)
                //console.log(form_field_name{{$module_id}});
                if(form_field_name{{$module_id}} == 'function_name'){
                    formfields_context{{$module_id}}.enableItems(['Edit Code'], true);
                }else{
                    formfields_context{{$module_id}}.enableItems(['Edit Code'], false);    
                }
                @endif
                
                $.each(args.items, function(index, item) {
                    if(item.id == "field_name"){
                       item.text = field_labels{{$module_id}}[form_field_id{{$module_id}}];
                    }
                })
                }
                
            }catch (e) {
                //console.log('error',e);
                args.cancel = true;
            }
        },
        select: function(args,e ){
            // context actions
            //console.log('select',args);
            if(args.item.id == 'sort_form'){
                viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}"); 
                if(form_field_tab{{$module_id}}){
                    
                    viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}"+'&tab='+form_field_tab{{$module_id}});     
                }else{
                    viewDialog('collist',"{{ url($module_fields_url.'?module_id='.$module_id) }}"); 
                }
            }
            if(args.item.id == 'add_field'){
                var fields_url = "{{ url($module_fields_url.'/edit?module_id='.$module_id) }}";
                sidebarformleft('coladd',fields_url,"{{$fields_module_title}}","{{$fields_module_description}}");
            }
            if(args.item.id == 'edit_field'){   
                var url = "{{ url($module_fields_url.'/edit') }}"+'/'+form_field_id{{$module_id}};
               
                sidebarformleft('coladd',url,"{{$fields_module_title}}","{{$fields_module_description}}");   
            }
            if(args.item.id == 'duplicate_field'){
                gridAjaxConfirm('/{{ $module_fields_url }}/duplicate', 'Duplicate column?', {"id" : form_field_id{{$module_id}}}, 'post');
            }
            if(args.item.id == 'delete_field'){
                gridAjaxConfirm('/{{ $module_fields_url }}/delete', 'Delete Column?', {"id" : form_field_id{{$module_id}}}, 'post');
            }
            if(args.item.id == 'set_visible_none'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/none');
            }
            if(args.item.id == 'set_visible_both'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/both');
            }
            if(args.item.id == 'set_visible_add'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/add');
            }
            if(args.item.id == 'set_visible_edit'){
                gridAjax('/field_visible_setting/{{$module_id}}/'+ form_field_id{{$module_id}} + '/edit');
            }
            @if($module_id == 385)
            if(args.item.id == 'edit_code'){
                window.open('/{{get_menu_url_from_module_id(385) }}?id={{ $form_data["id"] }}','_blank');
            }
            @endif
            
        },
    };
    
    // Initialize ContextMenu control
    formfields_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#formfields_context_ul{{$module_id}}');
}


function create_tab_context{{$module_id}}(){
   
   
    
    var context_items = [
        {id: "rename_tab", text: "Rename Tab"},
    ];
    var menuOptions = {
        target: '#mod{{$module_id}}Tab .e-tab-wrap',
        items: context_items,
        beforeItemRender:function(args){
            
            var el = args.element; 
                    if(args.item.cssClass > '') {
            var el = args.element;
            $(el).addClass(args.item.cssClass);
            }
        },
        beforeOpen: function(args,e){
       
            ftc_tab = $(args.event.target).find('.e-tab-text').text();
                
        },
        select: function(args,e ){
           
            if(args.item.id == 'rename_tab'){
               
                sidebarform('tabrename','/tab_rename/{{$module_id}}/'+ftc_tab,"Rename "+ftc_tab+" tab");
            }
            
            
        },
    };
    
    // Initialize ContextMenu control
    formtabs_context{{$module_id}} = new ej.navigations.ContextMenu(menuOptions, '#formtabs_context_ul{{$module_id}}');
}

@endif

function reload_active_form{{$module_id}}(){
    //console.log('reload_active_form{{$module_id}}');
    //console.log(sidebarformcontainer);
    //console.log(sidebarformcontainer.isRendered);
    //console.log(sidebarformcontainer.isOpen);
    //console.log(sidebarformcontainer.formUrl);
    if(sidebarformcontainer.isRendered && sidebarformcontainer.isOpen && sidebarformcontainer.formUrl){
        //console.log('reload form');
    sidebarform('reload_active_form',sidebarformcontainer.formUrl,$('#form_toolbar_title').text());
    }
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

.form-group .field-wrapper .field-label{
margin-right: 0% !important;
}
.form-group.formio-component-textarea .field-wrapper .field-label{
margin-right: 0% !important;
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
.formio-component img {
    max-height: 60px;
    width: auto !important;
}
.formio-form .card {
    background: transparent;
    border: 1px solid #dee2e6;
}
.formio-form .card-body {
    background: #fff;
}
.formio-form .card-header {
    padding: 0;
    background: transparent;
    font-size:15px;
    border-bottom: 1px solid #dee2e6;
}
.formio-form .card-header-tabs {
    margin: 0;
}

.formio-form .nav-link {
color: #344767;
}

</style>
@endpush