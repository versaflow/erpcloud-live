@section('right_sidebar')
<aside id="sidebarformcontainer" class="sidebarformcontainer appsidebar d-none" style="overflow-x: hidden;">
 
   
    
   
    
    <div id="form_toolbar_title">
    <h6 class="mb-0 d-inline pl-0" id="sidebarformtitle"></h6>
    </div>
   
    
    <div id="form_toolbar_submit" class="btn-group">
 
    <button id="form_toolbar_close_btn" class="sidebarbtn e-btn mb-0 btn btn-sm me-2">Close</button>
    <button id="form_toolbar_min_btn" class="sidebarbtn  e-btn mb-0 btn btn-sm me-2 bg-light text-dark">Minimize</button>
    
    <button id="form_toolbar_submit_email_btn" class="dialogSubmitBtn sidebarbtn sidebarformbtn d-none e-btn mb-0 btn btn-sm me-2 bg-primary">Submit & Email</button>
    <button id="form_toolbar_submit_approve_btn" class="dialogSubmitBtn sidebarbtn sidebarformbtn d-none e-btn mb-0 btn btn-sm me-2 bg-primary">Submit for Approval</button>
    <button id="form_toolbar_submit_btn" class="dialogSubmitBtn sidebarbtn sidebarformbtn e-btn mb-0 btn btn-sm me-2 bg-primary">Submit</button>
    </div> 
    
    <div class="card-body p-0" > 
        <div id="form_toolbar" style="height:40px;"></div> 
        <div id="sidebarform">
        </div>
    </div>
</aside>
@stop

@section('left_sidebar')
<aside id="sidebar_leftformcontainer" class="sidebar_leftformcontainer appsidebar d-none" style="overflow-x: hidden;">
    <div class="card-header sticky-top p-0" style="background:#e9e9e9; height:40px;">
    <div class="row m-0 h-100">
    <div class="col align-self-center">
    <h5 id="sidebar_leftformtitle"></h5>
    </div>
    <div class="col-auto align-self-center">
    <button id="sidebar_leftformclosebtn" class="sidebar_leftbtn e-btn mb-0 btn btn-sm me-2">Close</button>
    <button id="form_toolbar_left_min_btn" class="sidebar_leftbtn e-btn mb-0 btn btn-sm me-2 bg-light text-dark">Minimize</button>
    <button id="sidebar_leftformsubmitbtn" class="dialogSubmitBtn sidebarbtn sidebarformbtn e-btn k-button me-2 float-right e-primary ">Submit</button>
 
    </div>
    </div>
    </div>
    <div class="card-body p-0" style="height: calc(100% - 40px) !important;">
        <div id="sidebar_leftform" style="height: 100%  !important;">
        </div>
    </div>
</aside>
@stop

@push('sidebar-scripts')

<script>

 var sidebarformcontainer = new ej.navigations.Sidebar({
    animate:true,
    enableDock: false, 
    enableGestures: false,
    closeOnDocumentClick: false,
    showBackdrop: true,
    target: 'body',
    type: 'Over',
    position: 'Right',
    isOpen: false,
    width:'50%',
    zIndex:2000,
    created: function(args){
        sidebarform_toolbar_setup();
        window['close_sidebar_callback'] = false;
    },
    close: function(args){
        
       // //console.log('CLOSE SIDEBAR');
       // //console.log(args);
        ////console.log($(document.activeElement));
        if(window['close_sidebar_callback']){
            args.cancel = false;
        }else if(!args.isInteracted && args.event == null){
            args.cancel = true;
           
        }else if(sidebarformcontainer.isOpen && args && args.event == null){
            args.cancel = true;
            if ($(document.activeElement).attr('id') == 'sidebarformclosebtn') {
                args.cancel = false; 
                $("#sidebarform").html("");
            }
          
        }else if(args.isInteracted && sidebarformcontainer.isOpen && args && args.event && args.event.target){
           
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
                $("#sidebarform").html("");
            }
        }
        if(args.cancel == false){
            $("#sidebarformcontainer").addClass('d-none');
            window['close_sidebar_callback'] = false;    
        }
        
        if(args.cancel == false){
            try{
                for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                    var ed_id = tinymce.editors[i].id;
                    tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                }
            }catch(e){}    
        }
        if(args.cancel == false){
           // $("#modal_content").html('');
            if ($('#modal_content form').length > 0) {
            // Remove the form
            $('#modal_content form').remove();
            }
        }
        
    },
    open: function(args){
        $("#sidebarformcontainer").removeClass('d-none');
        var zIndex = getSidebarsZindex();
        zIndex++;
        ////console.log('rightz',zIndex);
        sidebarformcontainer.zIndex = zIndex;
        sidebarformcontainer.dataBind();
        $("#sidebarformsubmitbtn").removeAttr("disabled");  
    }
});
sidebarformcontainer.appendTo('#sidebarformcontainer');
        

function sidebarform_toolbar_setup(){
    $("#form_toolbar_close_btn").click(function(){
        
            window['close_sidebar_callback'] = true;
            sidebarformcontainer.hide();
      
       
    });
    
    $("#form_toolbar_min_btn").click(function(){
      ////console.log('form_toolbar_min_btn');
      if(!window['close_sidebar_callback']){
           setTimeout(function(){
         $("#showrightsidebar").removeClass('d-none');
          window['close_sidebar_callback'] = true;
          sidebarformcontainer.hide();
            },200)
      }
    });
    
    $("#form_toolbar_left_min_btn").click(function(){
      ////console.log('form_toolbar_min_btn');
      if(!window['close_sidebar_left_callback']){
           setTimeout(function(){
         $("#showleftsidebar").removeClass('d-none');
          window['close_sidebar_left_callback'] = true;
          sidebar_leftformcontainer.hide();
            },200)
      }
    });
        
     $("#form_toolbar_toggle_overlay_btn").click(function(){
         ////console.log('form_toolbar_toggle_overlay_btn');
        if(sidebarformcontainer.showBackdrop){
            sidebarformcontainer.showBackdrop = false; 
            $(".e-sidebar-overlay").addClass('d-none');
        }else{
            sidebarformcontainer.showBackdrop = true; 
            $(".e-sidebar-overlay").removeClass('d-none');
        }
        
        sidebarformcontainer.dataBind();
    });
    $("#form_toolbar_submit_btn").click(function(){
        
        ////console.log('form_toolbar_submit_btn');
        ////console.log($('#sidebarformcontainer').find('form'));
        ////console.log($('#sidebarformcontainer').find('.formio_form'));
        
        if($('#sidebarformcontainer').find('.formio_form').length > 0){
            //console.log('formio submit');
            var formio_uuid = $('#sidebarformcontainer').find('.formio_form').attr('id');
            formio_submit(formio_uuid);
        }else{
            //console.log('syncfusion submit');
            $('#sidebarformcontainer').find('form').submit();
        }
         
          
                   
    });
    
    $("#form_toolbar_submit_email_btn").click(function(){
        $('#sidebarformcontainer').find('form').find("#send_email_on_submit").val(1);
        $('#sidebarformcontainer').find('form').submit();
                   
    });
    $("#form_toolbar_submit_approve_btn").click(function(){
        $('#sidebarformcontainer').find('form').find("#send_approve_on_submit").val(1);
        $('#sidebarformcontainer').find('form').submit();
                   
    });
   
    window['form_toolbar'] = new ej.navigations.Toolbar({
        items: [
            { template:'#form_toolbar_title', align: 'left'},
         
            { template:'#form_toolbar_submit', align: 'right'},
            
        ]
    });
    window['form_toolbar'].appendTo('#form_toolbar');  
    
    $(".sidebarbtn").removeAttr("disabled");
}  
            
       
  function sidebarformleft(id, url, title = '', desc ='',width = '50%') {
   if (self != top) {
        window.parent.sidebarformleft(id, url, title,desc, width);
        return false;
    }

    $("#sidebar_leftformsubmitbtn").removeClass('d-none');
    if(window['sidebar_form_left_saving'] === true){
  
        return false;    
  
    }
    width = '50%';
    $('#sidebar_leftform').html('');
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            
            sidebar_leftformcontainer.show();
            showSpinner('#sidebar_leftformcontainer');
            
            $(".sidebarbtn").attr("disabled","disabled");
        },
        success: function(popup_content) {
            hideSpinner('#sidebar_leftformcontainer');
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                return false;
            }
            //if(title > ''){
                $('#sidebar_leftformtitle').text(title);    
            //}
          
            $("#sidebar_leftformcontainer").removeClass('sidebarview');
            $('#sidebar_leftform').html(popup_content);
            
            $(".sidebarbtn").removeAttr("disabled");
            sidebar_leftformcontainer.width = width;
            sidebar_leftformcontainer.show();
          
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinner('#sidebar_leftformcontainer');
            toastNotify('Error loading form','error');
            sidebar_leftformcontainer.hide();
        }
    });
    
}      
        
function sidebarform(id, url, title = '', desc ='',width = '50%') {
 
   if (self != top) {
        
        window.parent.sidebarform(id, url, title, desc, width);
        return false;
    }
    if(window['sidebar_form_saving'] === true){
        
  
        return false;  
    }
    if(window['close_sidebar_callback'] === true){
        
  
        return false;  
        
    }
    width = '50%';
    if(isMobile()){
    width= '100%';    
    }
    $('#sidebarform').html('');
  
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            window['close_sidebar_callback'] = true;
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            
            sidebarformcontainer.show();
            showSpinner('#sidebarformcontainer');
            
            $(".sidebarbtn").attr("disabled","disabled");
        },
        success: function(popup_content) {
   
            
            hideSpinner('#sidebarformcontainer');
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                sidebarformcontainer.hide();
                return false;
            }
            //if(title > ''){
                $('#sidebarformtitle').text(title);    
            //}
          
          
            $("#sidebarformcontainer").removeClass('sidebarview');
            $('#sidebarform').html(popup_content);
            
            $(".sidebarbtn").removeAttr("disabled");
            
            sidebarformcontainer.formUrl = url;
            sidebarformcontainer.width = width;
            sidebarformcontainer.show();
            
            window['close_sidebar_callback'] = false;
          
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinner('#sidebarformcontainer');
            toastNotify('Error loading form','error');
            sidebarformcontainer.hide();
            window['close_sidebar_callback'] = false;
        }
    });
    
}

function sidebarformlarge(id, url, title = '', width = '80%') {
     if (self != top) {
        window.parent.sidebarformlarge(id, url, title, width);
        return false;
    }
    if(window['sidebar_form_saving'] === true){
  
        return false;    
  
    }
 
  
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                return false;
            }
            if(title > ''){
             //   $('#sidebarformtitle').text(title);    
            }
          
            $("#sidebarformcontainer").removeClass('sidebarview');
            $('#sidebarform').html(popup_content);
            sidebarformcontainer.width = width;
            sidebarformcontainer.show();
          
        }
    });
}
function sidebar_leftview(id, url, title = '', width = '50%', position = 'Right') {
 
    $("#sidebar_leftformsubmitbtn").addClass('d-none');
    
    $('#sidebar_leftformtitle').text(title);  
    var width = '50%';
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            
           // showSpinner();
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            //hideSpinner();
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                return false;
            }
            if(title > ''){
               // $('#sidebar_leftformtitle').text(title);    
            }
            $("#sidebar_leftformcontainer").addClass('sidebarview');
            $('#sidebar_leftform').html(popup_content);
            sidebar_leftformcontainer.width = width;
            sidebar_leftformcontainer.position = position;
            sidebar_leftformcontainer.show();
        }
    });
}

function sidebarview(id, url, title = '', width = '50%', position = 'Right') {
    if(window['sidebar_form_saving'] === true){
        return false; 
    }
    
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                return false;
            }
            if(title > ''){
               // $('#sidebarformtitle').text(title);    
            }
            $("#sidebarformcontainer").addClass('sidebarview');
            $('#sidebarform').html(popup_content);
            sidebarformcontainer.width = width;
            sidebarformcontainer.position = position;
            sidebarformcontainer.show();
        }
    });
}

 var sidebar_leftformcontainer = new ej.navigations.Sidebar({
            animate:true,
            enableDock: false,
            enableGestures: false,
            closeOnDocumentClick: false,
            showBackdrop: true,
            target: 'body',
            type: 'Over',
            position: 'Left',
            isOpen: false,
            width:'50%',
            created: function(args){
                window['close_sidebar_left_callback'] = false;
            },
            close: function(args){
                
               
                if(window['close_sidebar_left_callback']){
                    args.cancel = false;
                }else if(!args.isInteracted && args.event == null){
                    args.cancel = true;
                   
                }else if(sidebar_leftformcontainer.isOpen && args && args.event == null){
                    args.cancel = true;
                    if ($(document.activeElement).attr('id') == 'sidebar_leftformclosebtn') {
                        args.cancel = false; 
                        //$("#sidebar_leftform").html("");
                    }
                
                }else if(args.isInteracted && sidebar_leftformcontainer.isOpen && args && args.event && args.event.target){
                   
                    if ($(args.event.target).parents('.sidebar_leftformcontainer').length) {
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
                       // $("#sidebar_leftform").html("");
                    }
                }
                if(args.cancel == false){
                $("#sidebar_leftformcontainer").addClass('d-none');
                window['close_sidebar_left_callback'] = false;    
                }
               
        
                if(args.cancel == false){
                    try{
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                    }catch(e){}    
                }
                
            },
            open: function(args){
                
                $("#sidebar_leftformcontainer").removeClass('d-none');
                var zIndex = getSidebarsZindex();
                zIndex++;
                
                ////console.log('leftz',zIndex);
                
                sidebar_leftformcontainer.position = 'Left';
                sidebar_leftformcontainer.zIndex = zIndex;
                sidebar_leftformcontainer.dataBind();
                $("#sidebar_leftformsubmitbtn").removeAttr("disabled");  
            }
        });
        sidebar_leftformcontainer.appendTo('#sidebar_leftformcontainer');
        
        $("#sidebar_leftformclosebtn").click(function(){
            window['close_sidebar_left_callback'] = true;
            sidebar_leftformcontainer.hide();
        }); 
        $("#sidebar_leftformsubmitbtn").click(function(){
            
            if($('#sidebar_leftformcontainer').find('.formio_form').length > 0){
                var formio_uuid = $('#sidebar_leftformcontainer').find('.formio_form').attr('id');
                formio_submit(formio_uuid);
            }else{
                $('#sidebar_leftformcontainer').find('form').submit();
            }
        });
        




</script>

<script>
    function getSidebarsZindex(){
        var maxZ=0;
        $('.appsidebar').each(function(){
        if($(this).css('zIndex') > maxZ) maxZ = $(this).css('zIndex');
        })
        return maxZ;
    }

   
</script>
@endpush

@push('page-styles') 

<style>
#form_toolbar, #form_toolbar .e-toolbar-items{
background: #d4d4d4;
}

</style>
@endpush