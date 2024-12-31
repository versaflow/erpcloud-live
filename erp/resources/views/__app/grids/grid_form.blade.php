@section('module_sidebar')
<aside id="sidebarform_container{{$grid_id}}" class="sidebarformcontainer appsidebar d-none" style="overflow-x: hidden;">
 
    <div id="form_toolbar{{$grid_id}}" style="height:40px;"></div> 
    
   
    
    <div id="form_toolbar_{{$grid_id}}title">
    <h6 class="mb-0 d-inline" id="sidebarform{{$grid_id}}title"></h6>
    </div>
   
    
    <div id="form_toolbar_{{$grid_id}}submit">
    <button id="form_toolbar_{{$grid_id}}submit_btn" class="sidebarbtn sidebarform{{$grid_id}}btn e-btn mb-0 btn btn-sm me-2 bg-primary">Submit</button>
    <button id="form_toolbar_{{$grid_id}}close_btn" class="sidebarbtn e-btn mb-0 btn btn-sm me-2 ">Close</button>
    <button id="form_toolbar_{{$grid_id}}min_btn" class="sidebarbtn e-btn mb-0 btn btn-sm me-2 bg-light text-dark">Minimize</button>
   
    </div> 
    
    <div class="card-body p-0" >
        <div id="sidebarform{{$grid_id}}">
        </div>
    </div>
</aside>
@stop

@push('page-scripts')
<script>
$(document).on('click','#showrightsidebar{{$grid_id}}', function(e){
    $("#showrightsidebar{{$grid_id}}").addClass('d-none');
    sidebarform_container{{$grid_id}}.show();
});


 var sidebarform_container{{$grid_id}} = new ej.navigations.Sidebar({
    animate:true,
    enableDock: false,
    enableGestures: false,
    closeOnDocumentClick: false,
    showBackdrop: true,
    target: 'body',
    type: 'Over',
    position: 'Right',
    isOpen: false,
    width:'45%',
    created: function(args){
        sidebarform{{$grid_id}}_toolbar_setup{{$grid_id}}();
        window['close_sidebar_callback{{$module_id}}'] = false;
    },
    close: function(args){
        //  //console.log('CLOSE SIDEBAR');
       // //console.log(args);
        ////console.log(args);
        ////console.log($(document.activeElement));
        if(window['close_sidebar_callback{{$module_id}}']){
            args.cancel = false;
        }else if(!args.isInteracted && args.event == null){
            args.cancel = true;
           
        }else if(sidebarform_container{{$grid_id}}.isOpen && args && args.event == null){
            args.cancel = true;
            if ($(document.activeElement).attr('id') == 'sidebarform{{$grid_id}}closebtn') {
                args.cancel = false; 
                $("#sidebarform{{$grid_id}}").html("");
               
            }
          
        }else if(args.isInteracted && sidebarform_container{{$grid_id}}.isOpen && args && args.event && args.event.target){
           
            if ($(args.event.target).parents('.sidebarformcontainer').length) {
                args.cancel = true; 
            }else if ($(args.event.target).parents('.choices__list').length) {
                args.cancel = true; 
            }else if ($(args.event.target).parents('.ck').length) {
                args.cancel = true; 
            }else if ($(args.event.target).parents('.choices__item').length) {
                args.cancel = true; 
            }else if ($(args.event.target).hasClass('choices__item')) {
                args.cancel = true; 
            }else if ($(args.event.target).hasClass('e-list-item')) {
                args.cancel = true; 
            }else if ($(args.event.target).is('input, textarea, select, option, li')) {
                args.cancel = true; 
            }else if ($(args.event.target).parents('.formio_form').length) {
                args.cancel = true; 
            }else{
                args.cancel = false; 
                $("#sidebarform{{$grid_id}}").html("");
                
            }
        }
        
        
        if(args.cancel){
            try{
                for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                    var ed_id = tinymce.editors[i].id;
                    tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                }
            }catch(e){}    
        }
        
        if(args.cancel == false){
            $("#sidebarform_container{{$grid_id}}").addClass('d-none');
            window['close_sidebar_callback{{$module_id}}'] = false;    
        }
    },
    open: function(args){
        $("#sidebarform_container{{$grid_id}}").removeClass('d-none');
        var zIndex = getSidebarsZindex();
        zIndex++;
        
        sidebarform_container{{$grid_id}}.zIndex = zIndex;
        sidebarform_container{{$grid_id}}.dataBind();
        sidebarform_container{{$grid_id}}.width = (isMobile()) ? '100%' : '45%';
        $("#sidebarform{{$grid_id}}submitbtn").removeAttr("disabled");  
       
    }
});
sidebarform_container{{$grid_id}}.appendTo('#sidebarform_container{{$grid_id}}');
        
function sidebarform{{$grid_id}}_toolbar_setup{{$grid_id}}(){
    $("#form_toolbar_{{$grid_id}}close_btn").click(function(){
         if(window['close_sidebar_callback{{$module_id}}'] === false){
            setTimeout(function(){
        $("#showrightsidebar{{$grid_id}}").addClass('d-none');
      //console.log('form_toolbar_{{$grid_id}}close_btn');
          window['close_sidebar_callback{{$module_id}}'] = true;
          sidebarformcontainer.width = '0%';
          sidebarform_container{{$grid_id}}.hide();
            },200)
        }
    });
    
    $("#form_toolbar_{{$grid_id}}min_btn").click(function(){
        if(window['close_sidebar_callback{{$module_id}}'] === false){
            setTimeout(function(){
                
            //console.log('form_toolbar_{{$grid_id}}min_btn');
            $("#showrightsidebar{{$grid_id}}").removeClass('d-none');
            window['close_sidebar_callback{{$module_id}}'] = true;
            sidebarform_container{{$grid_id}}.hide();
            },200)
        }
    });
    $("#form_toolbar_{{$grid_id}}toggle_overlay_btn").click(function(){
      //console.log(sidebarform_container{{$grid_id}});
        
    });
        
    $("#form_toolbar_{{$grid_id}}submit_btn").click(function(){
        
        //console.log('form_toolbar_{{$grid_id}}submit_btn');
        //console.log($('#sidebarform_container{{$grid_id}}').find('form'));
        //console.log($('#sidebarform_container{{$grid_id}}').find('.formio_form'));
        
        if($('#sidebarform_container{{$grid_id}}').find('.formio_form').length > 0){
            var formio_uuid = $('#sidebarform_container{{$grid_id}}').find('.formio_form').attr('id');
            formio_submit(formio_uuid);
        }else{
            $('#sidebarform_container{{$grid_id}}').find('form').submit();
        }
         
          
                   
    });
   
    window['form_toolbar{{$grid_id}}'] = new ej.navigations.Toolbar({
        items: [
            { template:'#form_toolbar_{{$grid_id}}title', align: 'left'},
         
            { template:'#form_toolbar_{{$grid_id}}submit', align: 'right'},
            
        ]
    });
    window['form_toolbar{{$grid_id}}'].appendTo('#form_toolbar{{$grid_id}}');  
    
    $(".sidebarbtn").removeAttr("disabled");
}     
            
       
        
        
function sidebarform{{$grid_id}}(id, url, title = '', desc ='',width = '60%') {
  
    if(window['sidebar_form_saving{{$module_id}}'] === true){
  
        return false;    
  
    }
    if(window['close_sidebar_callback{{$module_id}}'] === true){
  
        return false;    
  
    }
    
    $('#sidebarform{{$grid_id}}').html('');
    //console.log(title);
    //console.log(desc);
    width = '50%';
    
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            window['close_sidebar_callback{{$module_id}}'] = true;
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            
            sidebarform_container{{$grid_id}}.show();
            showSpinner('#sidebarform_container{{$grid_id}}');
            
            $(".sidebarbtn").attr("disabled","disabled");
        },
        success: function(popup_content) {
            hideSpinner('#sidebarform_container{{$grid_id}}');
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                return false;
            }
            //if(title > ''){
                $('#sidebarform{{$grid_id}}title').text(title);    
            //}
            //if(desc > ''){
                $('#sidebarform{{$grid_id}}desc').text(desc);    
            //}
          
            $("#sidebarform_container{{$grid_id}}").removeClass('sidebarview');
            $('#sidebarform{{$grid_id}}').html(popup_content);
            
            $(".sidebarbtn").removeAttr("disabled");
            sidebarform_container{{$grid_id}}.width = width;
            sidebarform_container{{$grid_id}}.show();
             window['close_sidebar_callback{{$module_id}}'] = false;
          
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinner('#sidebarform_container{{$grid_id}}');
            toastNotify('Error loading form','error');
            sidebarform_container{{$grid_id}}.hide();
             window['close_sidebar_callback{{$module_id}}'] = false;
        }
    });
    
}


</script>
@endpush

@push('page-styles') 

<style>
#form_toolbar{{$grid_id}}, #form_toolbar{{$grid_id}} .e-toolbar-items{
background: #d4d4d4;
}

</style>
@endpush