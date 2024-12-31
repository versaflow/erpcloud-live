@section('right_sidebar')
<aside id="sidebarformcontainer" class="sidebarformcontainer" style="overflow-x: hidden;">
 
    <div class="card-body p-0" >
        <div id="sidebarform">
        </div>
    </div>
</aside>
@stop

@section('left_sidebar')
<aside id="sidebar_leftformcontainer" class="sidebar_leftformcontainer" style="overflow-x: hidden;">
    <div class="card-header sticky-top p-0" style="background:#e9e9e9; height:40px;">
    <div class="row m-0 h-100">
    <div class="col align-self-center">
    <h3  id="sidebar_leftformtitle"></h3>
    </div>
    <div class="col-auto align-self-center">
    <button id="sidebar_leftformclosebtn" class="sidebar_leftbtn e-btn k-button mr-2 float-right">Close</button>
 
    </div>
    </div>
    </div>
    <div class="card-body p-0" style="height: calc(100% - 40px) !important;">
        <div id="sidebar_leftform" style="height: 100%  !important;">
        </div>
    </div>
</aside>
@stop

@section('sidebar-scripts')
@parent
<script>

 var sidebarformcontainer = new ej.navigations.Sidebar({
            animate:true,
            enableDock: false,
            closeOnDocumentClick: false,
            showBackdrop: true,
            target: 'body',
            type: 'Over',
            position: 'Right',
            isOpen: false,
            width:'50%',
            created: function(args){
   
                window['close_sidebar_callback'] = false;
            },
            close: function(args){
                ////console.log(args);
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
                        $("#sidebarformcontainer").removeClass('maxZindex');
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
                        $("#sidebarformcontainer").removeClass('maxZindex');
                    }
                }
                if(args.cancel == false){
                window['close_sidebar_callback'] = false;    
                }
                
                if(args.cancel){
                    try{
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                    }catch(e){}    
                }
                
            },
            open: function(args){
                $("#sidebarformsubmitbtn").removeAttr("disabled");  
                $("#sidebarformcontainer").addClass('maxZindex');
            }
        });
        sidebarformcontainer.appendTo('#sidebarformcontainer');
        
     
            
       
        
        
function sidebarform(id, url, title = '', desc ='',width = '60%') {
  
    if(window['sidebar_form_saving'] === true){
        //console.log('sidebar_form_saving');
        return false;    
  
    }
    
   // if($("#tabs_container").length > 0){
  //      if(title == ''){
 //           title = 'New Tab';    
  //      }
 //       addGridTab(title,url);
 //       return false;    
 //   }
   
        //console.log('sidebar_form ajax url');
        //console.log(url);
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
   
        //console.log('sidebar_form ajax loaded');
        //console.log(popup_content);
            if (popup_content.status) {
                if (popup_content.status == 'reload') {
                    window.open(popup_content.message, "_self");
                }else{
                    toastNotify(popup_content.message, popup_content.status);
                }
                return false;
            }
            //if(title > ''){
                $('#sidebarformtitle').text(title);    
            //}
            //if(desc > ''){
                $('#sidebarformdesc').text(desc);    
            //}
          
            $("#sidebarformcontainer").removeClass('sidebarview');
            $('#sidebarform').html(popup_content);
            sidebarformcontainer.width = width;
            sidebarformcontainer.show();
          
        }
    });
    
}

function sidebarformlarge(id, url, title = '', width = '80%') {
    if(window['sidebar_form_saving'] === true){
  
        return false;    
  
    }
    if($("#tabs_container").length > 0){
        if(title == ''){
            title = 'New Tab';    
        }
        addGridTab(title,url);
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

function sidebarview(id, url, title = '', width = '100%', position = 'Right') {
    if(window['sidebar_form_saving'] === true){
  
        return false;    
  
    }
    if($("#tabs_container").length > 0){
        if(title == ''){
            title = 'New Tab';    
        }
        addGridTab(title,url);
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
            closeOnDocumentClick: false,
            showBackdrop: true,
            target: 'body',
            type: 'Over',
            position: 'Left',
            isOpen: false,
            width:'60%',
            created: function(args){
                window['close_sidebar_left_callback'] = false;
            },
            close: function(args){
                
                ////console.log(args);
                ////console.log($(document.activeElement));
                if(window['close_sidebar_left_callback']){
                    args.cancel = false;
                }else if(!args.isInteracted && args.event == null){
                    args.cancel = true;
                   
                }else if(sidebar_leftformcontainer.isOpen && args && args.event == null){
                    args.cancel = true;
                    if ($(document.activeElement).attr('id') == 'sidebar_leftformclosebtn') {
                        args.cancel = false; 
                        $("#sidebar_leftform").html("");
                        $("#sidebar_leftformcontainer").removeClass('maxZindex');
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
                        $("#sidebar_leftform").html("");
                        $("#sidebar_leftformcontainer").removeClass('maxZindex');
                    }
                }
                if(args.cancel == false){
                window['close_sidebar_left_callback'] = false;    
                }
                
            },
            open: function(args){
                $("#sidebar_leftformsubmitbtn").removeAttr("disabled");  
                $("#sidebar_leftformcontainer").addClass('maxZindex');
            }
        });
        sidebar_leftformcontainer.appendTo('#sidebar_leftformcontainer');
        
        $("#sidebar_leftformclosebtn").click(function(){
            window['close_sidebar_left_callback'] = true;
            sidebar_leftformcontainer.hide();
        });
        




</script>
@stop