$(document).ready(function() {
    var ele = document.getElementById('container');
});


function imgDialog(img_src){
    var popup_content = '<div style="text-align:center"><img src="'+img_src+'" class="img-fluid" /></div>';
    var popup_id ='img_dialog';
    
    $("#dialog").append('<div id="' + popup_id + '"></div>');
    dialog = new ej.popups.Dialog({
        // Enables the header
        enableHtmlSanitizer: false,
        allowDragging: true,
        enableResize: true,
        isModal: true, //Ahmed
        showCloseIcon: true,
        closeOnEscape: true,
        content: popup_content,
        width:'60%',
        animationSettings: { effect: 'None' },
        zIndex: 4000,
        beforeClose: function() {
            //   dialog.destroy();
            $('#' + popup_id).remove();
        }
    });
    dialog.appendTo('#' + popup_id);
    dialog.show();
}


function quickBuyDialog(id, url, title = '', width = '90%', height = 'auto') {
   
        if($("#tabs_container").length > 0){
            if(title == ''){
                title = 'New Tab';    
            }
            addGridTab(title,url);
            return false;    
        }
    
    var id = makeid(6);
   
    if (self != top) {
        window.parent.viewDialog(id, url, title, width, height, css_class);
        return false;
    }
    
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            showSpinnerWindow();
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            hideSpinnerWindow();
        if (popup_content.status) {
            
            if (popup_content.status == 'reload') {
                window.open(popup_content.message, "_self");
            }else{
                toastNotify(popup_content.message, popup_content.status);
            }
        }
        else {
            var popup_id = 'popup-' + id;
            if ($("#" + popup_id).length == 0) {

                $("#dialog").append('<div id="' + popup_id + '"></div>');
                // Initialize the Outer Dialog component
                dialog = new ej.popups.Dialog({
                    // Enables the header
                    enableHtmlSanitizer: false,
                    allowDragging: true,
                    enableResize: true,
                    isModal: true, //Ahmed
                    showCloseIcon: false,
                    closeOnEscape: true,
                    content: '',
                    animationSettings: { effect: 'None' },
                    width: width,
                    height: height,
                    zIndex: 4000,
                    created: function() {
                        $('#' + popup_id).find('.e-dlg-content').html(popup_content);
                      
                    },
                    
                    beforeClose: function() {
                        // dialog.destroy();
                        $('#popup-' + id).remove();
                    },
                    buttons: [{
                            buttonModel: { isPrimary: false, content: 'Cancel' },
                            click: function() {
                                this.hide();
                            }
                        },
                        {
                            buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2', content: 'Buy Now' },
                            click: function() {
                                $('#' + popup_id + '_dialog-content').find("form").submit();

                            }
                        }
                    ],
                });
                dialog.appendTo('#' + popup_id);
                dialog.show();
            }
        }
    },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinnerWindow();
        }
    });
}

function viewDialog(id, url, title = '', width = '90%', height = '90%', css_class = '',reload_on_close = false) {
   
  
    if(title!='Activate'){
  
     
    }
    
    var id = makeid(6);
  
       sidebar_leftview(id,url,title);
      return false; 
      
    if (self != top) {
        window.parent.viewDialog(id, url, title, width, height, css_class);
        return false;
    }
    
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            showSpinnerWindow();
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            hideSpinnerWindow();
        if (popup_content.status) {
            
            if (popup_content.status == 'reload') {
                window.open(popup_content.message, "_self");
            }else{
                toastNotify(popup_content.message, popup_content.status);
            }
        }
        else {
            var popup_id = 'popup-' + id;
            if ($("#" + popup_id).length == 0) {

                $("#dialog").append('<div id="' + popup_id + '"></div>');

                if (css_class == 'none') {
                    css_class = '';
                }
                else if (css_class == '') {
                    css_class = 'view-dialog';
                }
                else {
                    css_class = 'view-dialog ' + css_class;
                }
               
                // Initialize the Outer Dialog component
                dialog = new ej.popups.Dialog({
                    // Enables the header
                    enableHtmlSanitizer: false,
                    cssClass: css_class,
                    allowDragging: true,
                    enableResize: true,
                    isModal: true, //Ahmed
                   // header: "<span class='e-icons sf-icon-Maximize' id='max-btn' title='Maximize' onClick='maximize(\"" + popup_id + "\")'></span> <span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                    header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                    showCloseIcon: true,
                    closeOnEscape: true,
                    content: '',
                    animationSettings: { effect: 'None' },
                    width: width,
                    height: height,
                    zIndex: 4000,
                    created: function() {
                        $('#' + popup_id).find('.e-dlg-content').html(popup_content);
                      
                    },
                    beforeClose: function() {
                        try {
                        $('.e-colmenu').each(function(i, el) {
                        
                        if( $(el)[0]['ej2_instances'][0].isClosed == false){
                        $(el)[0]['ej2_instances'][0].close();
                        }
                        });
                        }
                        catch (e) {}
                       
                        
                        if(top.original_title){
                            top.document.title = top.original_title;
                        }
                        /*
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                        */
                        //   dialog.destroy();  
                        /*
                        try {
                        if(typeof kanbanObj !== 'undefined'){
                                kanbanObj.refresh();
                            }
                        }
                        
                        catch (e) {}
                        */
                        $('#popup-' + id).remove();
                       
                    }
                });
                dialog.appendTo('#' + popup_id);
                dialog.show();
            }
        }
    },
        error: function(jqXHR, textStatus, errorThrown) {
            hideSpinnerWindow();
        }
    });
}


function quickDialog(id, url, title = '', width = '60%', height = 'auto', css_class = '', button_name = 'Save') {
    
     /*
      if(session_instance_id==11){
      
    if (url.indexOf("email_form") >= 0){
    window.open(url);
    return false;    
    }
    }
    */
    /*
        if($("#tabs_container").length > 0){
            if(title == ''){
                title = 'New Tab';    
            }
            addGridTab(title,url);
            return false;    
        }
    */
    var id = makeid(6);
  
    if (self != top) {
        window.parent.formDialog(id, url, title, width, height, css_class);
        return false;
    }
    if (typeof provision_title !== 'undefined' && provision_title.length > 0 && provision_title > '') {
        title = 'Activate ' + provision_title;
    }
  
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            if($(popup_content).find("#form_builder_link").length > 0){
                var dialog_buttons = [
                    {
                        buttonModel: { isPrimary: false, content: 'Edit Form', cssClass: 'left-dlg-btn e-info e-outline e-small' },
                        click: function() {
                            viewDialog('form_edit'+id , $(popup_content).find("#form_edit_link").attr("href"), 'Form Builder', '96%',  '96%');
                        }
                    },
                    {
                        buttonModel: { isPrimary: false, content: 'Form Builder', cssClass: 'left-dlg-btn e-info e-outline e-small' },
                        click: function() {
                            viewDialog('form_builder'+id , $(popup_content).find("#form_builder_link").attr("href"), 'Form Builder', '96%',  '96%');
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, content: 'Cancel', cssClass: 'e-info e-small' },
                        click: function() {
                            this.hide();
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2 e-small', content: button_name },
                        click: function() {
                            if( $('#' + popup_id + '_dialog-content').find(".formio_form").length > 0){
                                var formio = $('#' + popup_id + '_dialog-content').find(".formio_form");
                                var formio_uuid = $('#' + popup_id + '_dialog-content').find(".formio_form").eq(0).attr('id');
                                formio_submit(formio_uuid);
                            }else{
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        }
                    }
                ]; 
            }else{    
                var dialog_buttons = [
                    {
                        buttonModel: { isPrimary: true, content: 'Cancel', cssClass: 'e-info e-small' },
                        click: function() {
                            this.hide();
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2 e-small', content: button_name },
                        click: function() {
                            if( $('#' + popup_id + '_dialog-content').find(".formio_form").length > 0){
                                var formio = $('#' + popup_id + '_dialog-content').find(".formio_form");
                                var formio_uuid = $('#' + popup_id + '_dialog-content').find(".formio_form").eq(0).attr('id');
                                formio_submit(formio_uuid);
                            }else{
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        }
                    }
                ];    
            }
            
            
        if (popup_content.status) {
            if (popup_content.status == 'reload') {
                window.open(popup_content.message, "_self");
            }else{
                toastNotify(popup_content.message, popup_content.status);
            }
        }
        else {
            var popup_id = 'popup-' + id;
            if ($("#" + popup_id).length == 0) {
                $("#dialog").append('<div id="' + popup_id + '"></div>');
                if(css_class > '')
                var css_class = css_class +' form-dlg';
                else
                var css_class = 'form-dlg';
                // Initialize the Outer Dialog component
                
                window['form_enter_key'] = false;
                dialog = new ej.popups.Dialog({
                    // Enables the header
                    enableResize: true,
                    enableHtmlSanitizer: false,
                    cssClass: css_class,
                    allowDragging: true,
                    enableResize: true,
                    isModal: true, //Ahmed
                   // header: title,
                    header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                  
                   // header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                    showCloseIcon: true,
                    closeOnEscape: false,
                    content: '',
                    animationSettings: { effect: 'None' },
                    width: width,
                    height: height,
                    zIndex: 4000,
                    buttons: dialog_buttons,
                    created: function() {
                       
                        $('#' + popup_id).find('.e-dlg-content').html(popup_content);
                        
                        if (button_name == 'Save' && ($('#' + popup_id).find("#emailaddress").length > 0 || $('#' + popup_id).find("#sendNewsletter").length > 0)) {
                            $('#' + popup_id + ' .dialogSubmitBtn').text('Send');
                        }
                       
                    },
                    open: function(args){
                        args.preventFocus = true;
                    },
                    beforeClose: function(args) {
                       
                 
                        var kanbanreports = getGlobalProperties('reportsgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                        
                        var kanbanguides = getGlobalProperties('guidesgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                        
                        var kanbantasks = getGlobalProperties('tasksgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                       
                        try {
                            if (wizardTab)
                                wizardTab.destroy();
                        }
                        catch (e) {}

                        try {

                            $('.stepbox').each(function() {
                                $(this).remove();
                            });

                        }
                        catch (e) {}
                        /*
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                        */

                        // dialog.destroy();
                        $('#popup-' + id).remove();
                        
                    },
                });
                dialog.appendTo('#' + popup_id);
                dialog.show();
            }
        }
    }});
}

function popupForm(id, url, title = '', width = '60%', height = 'auto', css_class = '', button_name = 'Save') {
    
     /*
      if(session_instance_id==11){
      
    if (url.indexOf("email_form") >= 0){
    window.open(url);
    return false;    
    }
    }
    */
    /*
        if($("#tabs_container").length > 0){
            if(title == ''){
                title = 'New Tab';    
            }
            addGridTab(title,url);
            return false;    
        }
    */
    var width = '60%';
    var id = makeid(6);
      sidebarformleft(id, url, title, '',width);
    return false;
    if (self != top) {
        window.parent.formDialog(id, url, title, width, height, css_class);
        return false;
    }
    if (typeof provision_title !== 'undefined' && provision_title.length > 0 && provision_title > '') {
        title = 'Activate ' + provision_title;
    }
  
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            if($(popup_content).find("#form_builder_link").length > 0){
                var dialog_buttons = [
                    {
                        buttonModel: { isPrimary: false, content: 'Edit Form', cssClass: 'left-dlg-btn e-info e-outline e-small' },
                        click: function() {
                            viewDialog('form_edit'+id , $(popup_content).find("#form_edit_link").attr("href"), 'Form Builder', '96%',  '96%');
                        }
                    },
                    {
                        buttonModel: { isPrimary: false, content: 'Form Builder', cssClass: 'left-dlg-btn e-info e-outline e-small' },
                        click: function() {
                            viewDialog('form_builder'+id , $(popup_content).find("#form_builder_link").attr("href"), 'Form Builder', '96%',  '96%');
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, content: 'Cancel', cssClass: 'e-info e-small' },
                        click: function() {
                            this.hide();
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2 e-small', content: button_name },
                        click: function() {
                            if( $('#' + popup_id + '_dialog-content').find(".formio_form").length > 0){
                                var formio = $('#' + popup_id + '_dialog-content').find(".formio_form");
                                var formio_uuid = $('#' + popup_id + '_dialog-content').find(".formio_form").eq(0).attr('id');
                                formio_submit(formio_uuid);
                            }else{
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        }
                    }
                ]; 
            }else{    
                var dialog_buttons = [
                    {
                        buttonModel: { isPrimary: true, content: 'Cancel', cssClass: 'e-info e-small' },
                        click: function() {
                            this.hide();
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2 e-small', content: button_name },
                        click: function() {
                            if( $('#' + popup_id + '_dialog-content').find(".formio_form").length > 0){
                                var formio = $('#' + popup_id + '_dialog-content').find(".formio_form");
                                var formio_uuid = $('#' + popup_id + '_dialog-content').find(".formio_form").eq(0).attr('id');
                                formio_submit(formio_uuid);
                            }else{
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        }
                    }
                ];    
            }
            
            
        if (popup_content.status) {
            if (popup_content.status == 'reload') {
                window.open(popup_content.message, "_self");
            }else{
                toastNotify(popup_content.message, popup_content.status);
            }
        }
        else {
            var popup_id = 'popup-' + id;
            if ($("#" + popup_id).length == 0) {
                $("#dialog").append('<div id="' + popup_id + '"></div>');
                if(css_class > '')
                var css_class = css_class +' form-dlg';
                else
                var css_class = 'form-dlg';
                // Initialize the Outer Dialog component
                
                window['form_enter_key'] = false;
                dialog = new ej.popups.Dialog({
                    // Enables the header
                    enableResize: true,
                    enableHtmlSanitizer: false,
                    cssClass: css_class,
                    allowDragging: true,
                    enableResize: true,
                    isModal: true, //Ahmed
                   // header: title,
                    header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                  
                   // header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                    showCloseIcon: true,
                    closeOnEscape: false,
                    content: '',
                    animationSettings: { effect: 'None' },
                    width: width,
                    height: height,
                    //zIndex: 4000,
                    buttons: dialog_buttons,
                    created: function() {
                       
                        $('#' + popup_id).find('.e-dlg-content').html(popup_content);
                        
                        if (button_name == 'Save' && ($('#' + popup_id).find("#emailaddress").length > 0 || $('#' + popup_id).find("#sendNewsletter").length > 0)) {
                            $('#' + popup_id + ' .dialogSubmitBtn').text('Send');
                        }
                       
                    },
                    open: function(args){
                        args.preventFocus = true;
                    },
                    beforeClose: function(args) {
                       
                 
                        var kanbanreports = getGlobalProperties('reportsgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                        
                        var kanbanguides = getGlobalProperties('guidesgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                        
                        var kanbantasks = getGlobalProperties('tasksgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                       
                        try {
                            if (wizardTab)
                                wizardTab.destroy();
                        }
                        catch (e) {}

                        try {

                            $('.stepbox').each(function() {
                                $(this).remove();
                            });

                        }
                        catch (e) {}
                        /*
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                        */

                        // dialog.destroy();
                        $('#popup-' + id).remove();
                        
                    },
                });
                dialog.appendTo('#' + popup_id);
                dialog.show();
            }
        }
    }});
}

function formDialog(id, url, title = '', width = '60%', height = 'auto', css_class = '', button_name = 'Save') {
   
     /*
      if(session_instance_id==11){
      
    if (url.indexOf("email_form") >= 0){
    window.open(url);
    return false;    
    }
    }
    */
    /*
        if($("#tabs_container").length > 0){
            if(title == ''){
                title = 'New Tab';    
            }
            addGridTab(title,url);
            return false;    
        }
    */
    var id = makeid(6);
      sidebarform(id, url, title, '',width);
    return false;
    if (self != top) {
        window.parent.formDialog(id, url, title, width, height, css_class);
        return false;
    }
    if (typeof provision_title !== 'undefined' && provision_title.length > 0 && provision_title > '') {
        title = 'Activate ' + provision_title;
    }
  
    $.ajax({
        url: url,
        crossDomain:true,
        crossOrigin:true,
        beforeSend: function(request) {
            request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        },
        success: function(popup_content) {
            if($(popup_content).find("#form_builder_link").length > 0){
                var dialog_buttons = [
                    {
                        buttonModel: { isPrimary: false, content: 'Edit Form', cssClass: 'left-dlg-btn e-info e-outline e-small' },
                        click: function() {
                            viewDialog('form_edit'+id , $(popup_content).find("#form_edit_link").attr("href"), 'Form Builder', '96%',  '96%');
                        }
                    },
                    {
                        buttonModel: { isPrimary: false, content: 'Form Builder', cssClass: 'left-dlg-btn e-info e-outline e-small' },
                        click: function() {
                            viewDialog('form_builder'+id , $(popup_content).find("#form_builder_link").attr("href"), 'Form Builder', '96%',  '96%');
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, content: 'Cancel', cssClass: 'e-info e-small' },
                        click: function() {
                            this.hide();
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2 e-small', content: button_name },
                        click: function() {
                            if( $('#' + popup_id + '_dialog-content').find(".formio_form").length > 0){
                                var formio = $('#' + popup_id + '_dialog-content').find(".formio_form");
                                var formio_uuid = $('#' + popup_id + '_dialog-content').find(".formio_form").eq(0).attr('id');
                                formio_submit(formio_uuid);
                            }else{
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        }
                    }
                ]; 
            }else{    
                var dialog_buttons = [
                    {
                        buttonModel: { isPrimary: true, content: 'Cancel', cssClass: 'e-info e-small' },
                        click: function() {
                            this.hide();
                        }
                    },
                    {
                        buttonModel: { isPrimary: true, cssClass: 'e-info dialogSubmitBtn mr-2 e-small', content: button_name },
                        click: function() {
                            if( $('#' + popup_id + '_dialog-content').find(".formio_form").length > 0){
                                var formio = $('#' + popup_id + '_dialog-content').find(".formio_form");
                                var formio_uuid = $('#' + popup_id + '_dialog-content').find(".formio_form").eq(0).attr('id');
                                formio_submit(formio_uuid);
                            }else{
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        }
                    }
                ];    
            }
            
            
        if (popup_content.status) {
            if (popup_content.status == 'reload') {
                window.open(popup_content.message, "_self");
            }else{
                toastNotify(popup_content.message, popup_content.status);
            }
        }
        else {
            var popup_id = 'popup-' + id;
            if ($("#" + popup_id).length == 0) {
                $("#dialog").append('<div id="' + popup_id + '"></div>');
                if(css_class > '')
                var css_class = css_class +' form-dlg';
                else
                var css_class = 'form-dlg';
                // Initialize the Outer Dialog component
                
                window['form_enter_key'] = false;
                dialog = new ej.popups.Dialog({
                    // Enables the header
                    enableResize: true,
                    enableHtmlSanitizer: false,
                    cssClass: css_class,
                    allowDragging: true,
                    enableResize: true,
                    isModal: true, //Ahmed
                   // header: title,
                    header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                  
                   // header: " <span class='title'>"+title+"</span><span class='e-icons sf-icon-Minimize' id='min-btn' title='Minimize' onClick='minimize(\"" + popup_id + "\")'></span>",
                    showCloseIcon: true,
                    closeOnEscape: false,
                    content: '',
                    animationSettings: { effect: 'None' },
                    width: width,
                    height: height,
                    zIndex: 4000,
                    buttons: dialog_buttons,
                    created: function() {
                       
                        $('#' + popup_id).find('.e-dlg-content').html(popup_content);
                        
                        if (button_name == 'Save' && ($('#' + popup_id).find("#emailaddress").length > 0 || $('#' + popup_id).find("#sendNewsletter").length > 0)) {
                            $('#' + popup_id + ' .dialogSubmitBtn').text('Send');
                        }
                       
                    },
                    open: function(args){
                        args.preventFocus = true;
                    },
                    beforeClose: function(args) {
                       
                 
                        var kanbanreports = getGlobalProperties('reportsgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                        
                        var kanbanguides = getGlobalProperties('guidesgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                        
                        var kanbantasks = getGlobalProperties('tasksgrid');
                        $(kanbanreports).each(function(i, el) {
                            try {
                            if(window[el].isRendered){
                                window[el].refresh();
                            }
                            }catch (e) {}
                        });
                       
                        try {
                            if (wizardTab)
                                wizardTab.destroy();
                        }
                        catch (e) {}

                        try {

                            $('.stepbox').each(function() {
                                $(this).remove();
                            });

                        }
                        catch (e) {}
                        /*
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                        */

                        // dialog.destroy();
                        $('#popup-' + id).remove();
                        
                    },
                });
                dialog.appendTo('#' + popup_id);
                dialog.show();
            }
        }
    }});
}


function transactionDialog(id, url, title = '', width = '80%', height = 'auto') {
   
       
    
    var id = makeid(6);
    //if(session_is_dev){
    sidebarform(id, url, title, '',width);
    return false;
   // }
    if (self != top) {
        window.parent.transactionDialog(id, url, title, width, height, css_class);
        return false;
    }
    
    $.get(url, function(popup_content) {
        if (popup_content.status) {
           
            if (popup_content.status == 'reload') {
                window.open(popup_content.message, "_self");
            }else{
                toastNotify(popup_content.message, popup_content.status);
            }
        }
        else {
            var popup_id = 'popup-' + id;
            if ($("#" + popup_id).length == 0) {
                $("#dialog").append('<div id="' + popup_id + '"></div>');

                // Initialize the Outer Dialog component
                dialog = new ej.popups.Dialog({
                    // Enables the header
                    enableHtmlSanitizer: false,
                    allowDragging: true,
                    enableResize: true,
                    isModal: true, //Ahmed
                    //header: title,
                    showCloseIcon: true,
                    closeOnEscape: true,
                    content: '',
                    animationSettings: { effect: 'None' },
                    width: width,
                    height: height,
                    buttons: [{
                            buttonModel: { isPrimary: false, content: 'Cancel', iconCSS: 'fa ' },
                            click: function() {
                                this.hide();
                            },
                        },
                        /*
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn docEmailBtn', content: 'Email' },
                            click: function() {
                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?emailonly=1';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        */
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn docSaveBtn', content: 'Save' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?emaildocument=0';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        /*
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn docSaveEmailBtn', content: 'Save & Email' },
                            click: function() {
                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?emaildocument=1';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn docApproveBtn', content: 'Approve' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?approve=1&emaildocument=0';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                      
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn docApproveEmailBtn', content: 'Approve & Email' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?approve=1&emaildocument=1';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        */
                
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn creditDraftBtn', content: 'Draft' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?emaildocument=0';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        
                        /*
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn creditDraftEmailBtn', content: 'Draft  & Email' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?emaildocument=1';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn creditApproveBtn', content: 'Complete' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?approve=1&emaildocument=0';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn creditApproveEmailBtn', content: 'Complete & Email' },
                            click: function() {

                                var action_url = $('#' + popup_id + '_dialog-content').find("form").attr('action');
                                var action_url = action_url+'?approve=1&emaildocument=1';
                                $('#' + popup_id + '_dialog-content').find("form").attr('action',action_url);
                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                        */
                        {
                            buttonModel: { cssClass: 'e-primary hidebtn dialogSubmitBtn transactSubmitBtn', content: 'Complete' },
                            click: function() {

                                $('#' + popup_id + '_dialog-content').find("form").submit();
                            }
                        },
                    ],
                    created: function() {
                        
                        $('#' + popup_id).find('.e-dlg-content').html(popup_content);
                    },
                    beforeClose: function() {
                        /*
                        for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
                            var ed_id = tinymce.editors[i].id;
                            tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
                        }
                        */
                        // dialog.destroy();
                        $('#popup-' + id).remove();
                    },
                });
                dialog.appendTo('#' + popup_id);
                dialog.show();
            }
        }
    });
}

function gridAjax(url, data = null, type = 'get') {
    
    
    if(typeof grid_filters === "undefined"){
        grid_filters = null;
    }
    if(data == null && grid_filters != null){
        data = {grid_filters: grid_filters};
    }
  
    var grid_id = false;
    try{
    var element = $(".gridtabid:visible:first");
    var grid_id = $(element).attr('id');
    }catch(e){}
       
    $.ajax({
        url: url,
        data: data,
        type: type,
        beforeSend: function(e) {
            
            var globalprops = getGlobalProperties('selectedrow_');
            $(globalprops).each(function(i, el) {
            window['ajax'+el] = window[el]
            });
           
            if(grid_id){
                showSpinner('#'+grid_id);
            }else{
                showSpinner();
            }
        },
        success: function(data) {
            
            if(grid_id){
                hideSpinner('#'+grid_id);
            }else{
                hideSpinner();
            }
           
          
            try {
            if(data.callback_function){
            window[data.callback_function]();
            }
            }catch (e) {}
         
            if(!data.disable_grid_refresh){
                processAjaxSuccess(data, false);
            }else{
                toastNotify(data.message, data.status);
            }

        },
        error: function(jqXHR, textStatus, errorThrown) {
            if(grid_id){
                hideSpinner('#'+grid_id);
            }else{
                hideSpinner();
            }
            processAjaxError(jqXHR, textStatus, errorThrown);
        },
    });
}

function gridAjaxConfirm(url, confirm_text, data = null, type = 'get') {
    
    if(typeof grid_filters === "undefined"){
        grid_filters = null;
    }
    if(data == null && grid_filters != null){
        data = {grid_filters: grid_filters};
    }
    var confirmation = confirm(confirm_text);
    if (confirmation) {
        
        var grid_id = false;
        try{
        var element = $(".gridtabid:visible:first");
        var grid_id = $(element).attr('id');
        }catch(e){}
       // console.log('gridAjaxConfirm');
      //  console.log(url);
        $.ajax({
            url: url,
            data: data,
            type: type,
            beforeSend: function(e) {
                var globalprops = getGlobalProperties('selectedrow_');
                $(globalprops).each(function(i, el) {
                window['ajax'+el] = window[el]
                });
            
                
                if(grid_id){
                    showSpinner('#'+grid_id);
                }else{
                    showSpinner();
                }
            },
            success: function(data) {
               
        //console.log('success');
        //console.log(data);
               
           
                  try {
            if(data.callback_function){
            window[data.callback_function]();
            }
            }catch (e) {}
            
                if(grid_id){
                    hideSpinner('#'+grid_id);
                }else{
                    hideSpinner();
                }
                try {
                if(data.callback_function){
                window[data.callback_function]();
                }
                }catch (e) {}
                
                if(!data.disable_grid_refresh){
                 
                    processAjaxSuccess(data, false);
                }else{
                
                    toastNotify(data.message, data.status);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                
        //console.log('error');
        //console.log(jqXHR);
       // console.log(textStatus);
       // console.log(errorThrown);
                if(grid_id){
                    hideSpinner('#'+grid_id);
                }else{
                    hideSpinner();
                }
                processAjaxError(jqXHR, textStatus, errorThrown);
            },
        });
    }
}


function iframeFormSubmit(iframe_id, form_id) {

    var form = $('#' + iframe_id).contents().find('#' + form_id);
    var formData = new FormData(form[0]);

    $('input[type=file]').each(function() {
        if ($(this).val() > '') {
            var ins = $(this)[0].files.length;
            if (ins > 1) {
                for (var x = 0; x < ins; x++) {
                    formData.append($(this)[0].name + "[]", $(this)[0].files[x]);
                }
            }
            else {
                formData.append($(this)[0].name, $(this)[0].files[0]);
            }
        }
    });

    $.ajax({
        method: "post",
        url: form.attr('action'),
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function(e) {
            
            $(".sidebarbtn").attr("disabled","disabled");
           
            try {
                showSpinner();
                $('.dialogSubmitBtn').each(function(e) {
                    $(this).prop('disabled', true);
                });
            }
            catch (e) {}
        },
        success: function(data) {
            
          
                $(".sidebarbtn").removeAttr("disabled"); 
            try {
                hideSpinner();
            }
            catch (e) {}
            processAjaxSuccess(data);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            
          
                $(".sidebarbtn").removeAttr("disabled"); 
            try {
                hideSpinner();
            }
            catch (e) {}
            processAjaxError(jqXHR, textStatus, errorThrown);
        },
    });
}

function formSubmit(form_id, callback_function = false) {
    
    $('#' + form_id + ' :disabled').each(function(e) {
        $(this).removeAttr('disabled');
    })
    
    left_form = $('#'+form_id).closest('.e-sidebar').hasClass('sidebar_leftformcontainer');
    var form = $('#' + form_id);
    var formData = new FormData(form[0]);

    $('input[type=file]').each(function() {
        if ($(this).val() > '') {
            var ins = $(this)[0].files.length;
            if (ins > 1) {
                for (var x = 0; x < ins; x++) {
                    formData.append($(this)[0].name + "[]", $(this)[0].files[x]);
                }
            }
            else {
                formData.append($(this)[0].name, $(this)[0].files[0]);
            }
        }
    });

    if ($("#signature").length > 0) {
        var signature_name = $("#signature").attr('name');
        var signature = signaturePad.toDataURL();
        formData.append(signature_name, signature);
    }
    form_refresh_ref = '#' + form_id;
    if($("#"+form_id).closest('.sidebarformcontainer').length > 0){
       form_refresh_ref = "#"+$("#"+form_id).closest('.sidebarformcontainer').attr('id');
    }
    $.ajax({
        method: "post",
        url: form.attr('action'),
        data: formData,
        contentType: false,
        processData: false,
        beforeSend: function(e) {
            
  
          
            if($("#processing_div").length > 0){
                $("#processing_div").show();  
            }
            try {
                showSpinner(form_refresh_ref);
                $('.dialogSubmitBtn').each(function(e) {
                    $(this).prop('disabled', true);
                });
            }
            catch (e) {}
        },
        success: function(data) {
            
         
            if($("#processing_div").length > 0){
                $("#processing_div").hide();  
            }
            try {
                if(data.status == 'success'){
                    removeGridTab($('#' + form_id).closest('.gridtabid').attr('id'));
                }
            }
            catch (e) {}
            try {
                hideSpinner(form_refresh_ref);
            }
            catch (e) {}
            if(callback_function != false){
                callback_function(data);
            }
            try {
            if(data.callback_function){
                window[data.callback_function](data);
            }
            }catch (e) {}
            processAjaxSuccess(data);
            if(left_form){
                window['close_sidebar_left_callback'] = true;
                sidebar_leftformcontainer.hide();
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            
         
           
            if($("#processing_div").length > 0){
                $("#processing_div").hide();  
            }
            try {
                hideSpinner(form_refresh_ref);
            }
            catch (e) {}
            processAjaxError(jqXHR, textStatus, errorThrown);
        },
    });
}

function ajaxSend(url, args, success_msg = 'Updated', error_msg = '') {
    $.ajax({
        type: 'post',
        url: url,
        data: args,
        success: function(data) {
            toastNotify(success_msg, 'success');
        },
        error: function(jqXHR, textStatus, errorThrown) {
            if (error_msg == '')
                error_msg = textStatus;
            toastNotify(error_msg, 'error');
        }
    });
}

function toastNotify(msg = '', type = 'info', grid_reload = true, icon = false, timeout = 3000) {

 
    try{
    if (type == 'email_success') {
        type = 'success';
    }
    if (type == 'email_error') {
        type = 'warning';
    }
   
    
    if(type == 'error' || type == 'warning' ){
        timeout = 9000;
    }
    $("#toast").removeClass('e-toast-success');
    $("#toast").removeClass('e-toast-warning');
    $("#toast").removeClass('e-toast-danger');
    $("#toast").removeClass('e-toast-info');

    var toast = new ej.notifications.Toast({
        content: msg,
        position: { X: 'Right', Y: 'Bottom' },
        showCloseButton: true,
        timeOut: timeout,
        target: '#body_container',
        width: '500px',
    });

    if (type == 'success') {
        toast.cssClass = 'e-toast-success';
        toast.icon = 'e-success toast-icons';
        toast.title = 'Success';
    }
    else if (type == 'error') {
        toast.cssClass = 'e-toast-danger';
        toast.icon = 'e-error toast-icons';
        toast.title = 'Error';
    }
    else if (type == 'warning') {
        toast.cssClass = 'e-toast-warning';
        toast.icon = 'e-warning toast-icons';
        toast.title = 'Warning';
    }
    else if (type == 'info') {
        toast.cssClass = 'e-toast-info';
        toast.icon = 'e-info toast-icons';
        toast.title = 'Info';
    }

    if (icon) {
        toast.icon = icon;
    }

    toast.appendTo('#toast');
    toast.show();
    }catch(e){
        
    }

}

function getGlobalProperties(prefix) {
    var keyValues = [],
        global = window; // window for browser environments
    for (var prop in global) {
        if (prop.indexOf(prefix) == 0) // check the prefix
            keyValues.push(prop);
    }
    return keyValues;
}

function getMenuList() {
    var keyValues = [],
        global = window; // window for browser environments
    for (var prop in global) {
        if (prop.indexOf('treeObj') !== -1) // check the prefix
            keyValues.push(prop);
    }
    return keyValues;
}

function msgPopup(title,content){
  
    $("body").append('<div id="msg-pop"></div>');
        ej.base.enableRipple(true);
        
        // Initialization of Dialog
        var popup_dialog = new ej.popups.Dialog({
        // Dialog content
        content: content,
        cssClass: "msg-popup",
        // The Dialog shows within the target element
        //target: document.getElementById("container"),
        // Dialog width
        width: '30%',
        // Enables the header
        header: title,
        showCloseIcon: true,
        isModal: true,
        animationSettings: { effect: 'Zoom' },
        closeOnEscape: true,
        buttons: [
            {
                // Click the footer buttons to hide the Dialog
                'click': () => {
                    popup_dialog.hide();
                },
                // Accessing button component properties by buttonModel property
                buttonModel: {
                    //Enables the primary button
                    isPrimary: true,
                    content: 'OK'
                }
            },
        ],    
        beforeClose: function() {
            // dialog.destroy();
            $('#msg-pop').remove();
        },
    },'#msg-pop');
}
    
function processAjaxSuccess(data, close_modal = false) {
   //console.log('processAjaxSuccess',data);
   
   
    try { 
        window['update_menu_manager_datasource']();
    }catch (e) {}
    try { 
    window['refresh_document_accounts']();
    }catch (e) {}
     try { 
        refresh_account_accordion();
      }catch (e) {}
      
      if(data.status && data.status!='success' && data.status!='info'){
            try { 
          $("#sidebar_leftformclosebtn").show();
        }catch (e) {}
      }
    
    if((data && data.close_dialog && data.close_dialog == 1) || (data && data.status && data.status == 'success') || close_modal){
        try {
        window['close_sidebar_callback'] = true;
        sidebarformcontainer.hide();
        }catch (e) {}
    }
   
    if(data && data.close_left_dialog && data.close_left_dialog == 1){
        try {
        window['close_sidebar_left_callback'] = true;
        sidebar_leftformcontainer.hide();
       
        }catch (e) {
            
        }
    }
    var globalprops = getGlobalProperties('grid_');
    $(globalprops).each(function(i, el) {

        if (el != 'grid_height' && el != 'grid_default' && el != 'grid_module_id' && el != 'grid_config_id' && el.toLowerCase().indexOf("grid_layout_id") === -1) {
            try {
           
            window[el].gridOptions.refresh(data);
          
            }catch (e) {}
            
        }
    });
    
    var sidebarreports = getGlobalProperties('gridsidebarreports_');
    $(sidebarreports).each(function(i, el) {
    try {
    if(window[el].isRendered){
    window[el].refresh();
    }
    }catch (e) {}
    });
    
    try {
        get_sidebar_data();
    }catch (e) {
      
    }
    
    try {
    window.frames[0].frameElement.contentWindow.grid_refresh(); 
    }catch (e) {}
    if(data.reload_grid_views){
        reload_grid_views();
    }
    
    if(data.layout_create){
        layout_create(data.row_id);
    }
    
    try {
    if(data.reload_grid_config){
        var rgc = data.reload_grid_config;
      
        window[rgc]();
    }
    
    }
    catch (e) {}
    if(data.reload_conditional_styles){
        reload_conditional_styles(data.module_id);
    }
    
    try {
    var menulist = getMenuList();
    $(menulist).each(function(i, el) {
    window[el].refresh();
    })
    
    }
    catch (e) {}
    
   
    $('.dialogSubmitBtn').each(function(e) {
        $(this).prop('disabled', false);
    });
   
    if(data.check_grid_active){
        
        if($("#grid_default"+data.check_grid_active).length > 0 || $("#grid_ajax"+data.check_grid_active).length > 0){
           try {

                popup_el = dialog.getRootElement();
                
                
                if($(popup_el).hasClass('form-dlg')){
                   
                    dialog.hide();
                }
            }
            catch (e) {}
        }
    }
    
    if (data.status) {

        if (data.provision_id) {
            try {
                
              
                sidebar_leftformcontainer.hide();
                if(data.status == 'info'){
                viewDialog('provison', data.provision_url, 'Provision', "70%");
                }
            }
            catch (e) {
                
                console.error(e);
            }
        }
      
        if (data.print) {
            try {
                printPDF(data.print);
                  
                dialog.hide();
            }
            catch (e) {}
            return false;
        }

/*
        if (data.status != 'error' && data.status != 'warning' && data.message != 'Record Deleted.'  && data.message != 'Duplicated' && close_modal == true) {
            try {

                dialog.hide();
            }
            catch (e) {}
        }
*/      

        if (data.close_dialog) {
        try {
        
        closeActivePopup();
        }
        catch (e) {}
        }else if (data.status == 'success') {
        try {
            popup_el = dialog.getRootElement();
                
           
            if($(popup_el).hasClass('form-dlg')){
                
                   
                dialog.hide();
            }
          //  dialog.hide();
        }
        catch (e) {}
        }

        

        if (data.status == 'msgPopup') {
            msgPopup(data.title, data.content);
        }else if (data.status == 'sidebarform') {
            
            if (data.message > '') {
                toastNotify(data.message, 'success');
            }
            var modal_title = '';
            if (data.modal_title > '') {
                modal_title = data.modal_title;
            }
            sidebarform('viewDialog', data.url, modal_title, '','80%');
        }else if (data.status == 'viewDialog') {
            
            if (data.message > '') {
                toastNotify(data.message, 'success');
            }
            var modal_title = '';
            if (data.modal_title > '') {
                modal_title = data.modal_title;
            }
            viewDialog('viewDialog', data.url, modal_title, '80%', '50%', '');
        } else if (data.status == 'querybuilder') {
            if (data.message > '') {
                toastNotify(data.message, 'success');
            }
            formDialog('querybuilder',  data.url, 'Query Builder', '60%');
        }else if (data.status == 'formDialog') {
            if (data.message > '') {
                toastNotify(data.message, 'success');
            }
            
            var modal_title = '';
            if (data.modal_title > '') {
                modal_title = data.modal_title;
            }
            formDialog('formDialog', data.url, modal_title, '50%', '50%', '', 'Submit');
        }
        else if (data.status == 'transactionDialog') {
          //  console.log('transactionDialog');
            transactionDialog('edittrx', data.message, 'Edit Transaction', '80%', '100%');
        }
        else if (data.status == 'reload') {

            window.open(data.message, "_self");
        }
        else if (data.status == 'success') {
            toastNotify(data.message, 'success');
            if(data.refresh_instant == 1) {
            location.reload();
            }

        }
        else if (data.status == 'emailerror') {
            toastNotify(data.message, 'error');
        }
        else if (data.status == 'error') {
            toastNotify(data.message, 'error');
        }
        else if (data.status == 'refresh_instant') {
           location.reload();
        }
        else {
            toastNotify(data.message, data.status);
        }

        if (data.view_dialog) {
            viewDialog(1 , data.view_dialog, data.dialog_title, '90%',  '90%');
        } 
        if (data.form_dialog) {
            formDialog(1 , data.view_dialog, data.dialog_title, '90%',  '90%');
        } 
        
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

        if (data.refresh) {
            setTimeout(function() {
                location.reload();
            }, 1000);
        }
    }else {
        try {
           popup_el = dialog.getRootElement();
                
           
            if($(popup_el).hasClass('form-dlg')){
                
                   
                dialog.hide();
            }
       
        if (data.indexOf('err') != -1 || data.indexOf('error') != -1 || data.indexOf('Error') != -1)
            toastNotify(data, 'error');
        else
            toastNotify(data);
        }
        catch (e) {}
    }
    
}

function processAjaxError(jqXHR, textStatus, errorThrown) {
    

    try { 
    $("#sidebar_leftformclosebtn").show();
    }catch (e) {}
      
    var globalprops = getGlobalProperties('grid_');
    $(globalprops).each(function(i, el) {

        if (el != 'grid_height' && el != 'grid_default' && el != 'grid_module_id' && el != 'grid_config_id' && el.toLowerCase().indexOf("grid_layout_id") === -1) {
            try {
           
            //window[el].clearSelection();
            window[el].gridOptions.refresh();
            
            }catch (e) {}
            
        }
    });
    $('.dialogSubmitBtn').each(function(e) {
        $(this).prop('disabled', false);
    });

    toastNotify(textStatus, 'error');
}

function closeActivePopup() {
    try {
        $('.e-popup-open:last').parent().remove();
    }
    catch (e) {}
}

function ucwords(str) {
    return (str + '').replace(/^([a-z])|\s+([a-z])/g, function($1) {
        return $1.toUpperCase();
    });
}


function statement_email(account_id) {
    formDialog('statement' + account_id, '/email_form/statement_email/' + account_id, 'Send Statement', '70%');
}

function full_statement_email(account_id) {
    formDialog('statement' + account_id, '/email_form/full_statement_email/' + account_id, 'Send Statement', '70%');
}

function supplier_statement_email(supplier_id) {
    formDialog('statement' + supplier_id, '/email_form/supplier_statement_email/' + supplier_id, 'Send Statement', '70%');
}

function supplier_full_statement_email(supplier_id) {
    formDialog('statement' + supplier_id, '/email_form/supplier_full_statement_email/' + supplier_id, 'Send Statement', '70%');
}

function doc_email(document_id) {
    formDialog('docemail' + document_id, '/email_form/documents/' + document_id, 'Send Document', '70%');
}

function usddoc_email(document_id) {
    formDialog('docemail' + document_id, '/email_form/usd_documents/' + document_id, 'Send Document', '70%');
}


function enableDialogSubmit() {
    $('.dialogSubmitBtn').each(function(e) {
        $(this).prop('disabled', false);
    });
}

function disableDialogSubmit() {
    $('.dialogSubmitBtn').each(function(e) {
        $(this).prop('disabled', true);
    });
}

function printPDF(url) {
  var iframe = this._printIframe;
  if (!this._printIframe) {
    iframe = this._printIframe = document.createElement('iframe');
    document.body.appendChild(iframe);

    iframe.style.display = 'none';
    iframe.onload = function() {
      setTimeout(function() {
        iframe.focus();
        iframe.contentWindow.print();
      }, 1);
    };
  }

  iframe.src = url;
}

$(document).on('click', 'a[data-target="view_modal_small"]', (function(e) {
    
    window.open($(this).attr('href'), "_blank");
    return false;
    var modal_id = makeid(5);
   

   var close_dialog = $(this).attr('data-close-dialog');
 
   if (typeof close_dialog !== typeof undefined && close_dialog !== false) {
        try {
            $('.msg-popup:last').parent().remove();
            closeActivePopup();
        }
        catch (e) {}
    }
    e.preventDefault();
   
    if(window.location.pathname == $(this)[0].pathname){
         window.location.href = $(this).attr('href');
    }else{
        var title = $(this).attr('title');
        if($(this).find('.e-anchor-wrap').length > 0 && title==''){
            var title = $(this).find('.e-anchor-wrap').text();
        }
        viewDialog(modal_id, $(this).attr('href'), title, "60%");
    }
}));

$(document).on('click', 'a[data-target="leftview"]', (function(e) {
    
    var modal_id = makeid(5);
    e.preventDefault();
    var title = $(this).attr('title');
    if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
    }
    sidebar_leftview(modal_id, $(this).attr('href'), title, "70%");
}));

$(document).on('click', 'a[data-target="view_modal"]', (function(e) {
    
    console.log('view_modal 1');
    
    var modal_id = makeid(5);
  
  

   var close_dialog = $(this).attr('data-close-dialog');
 
   if (typeof close_dialog !== typeof undefined && close_dialog !== false) {
        try {
            $('.msg-popup:last').parent().remove();
            closeActivePopup();
        }
        catch (e) {}
    }
    e.preventDefault();

    if(window.location.pathname == $(this)[0].pathname){
      
         window.open($(this).attr('href'),'_blank');
    }else{
        var title = $(this).attr('title');
        if($(this).find('.e-anchor-wrap').length > 0 && title==''){
            var title = $(this).find('.e-anchor-wrap').text();
        }
   
        viewDialog(modal_id, $(this).attr('href'),title, "90%");
    }
}));

$(document).on('click', 'button[data-target="view_modal"]', (function(e) {
    console.log('view_modal 2');
   //  window.open($(this).attr('href'), "_blank");
   // return false;
    var modal_id = makeid(5);
   

   var close_dialog = $(this).attr('data-close-dialog');
 
   if (typeof close_dialog !== typeof undefined && close_dialog !== false) {
        try {
            $('.msg-popup:last').parent().remove();
            closeActivePopup();
        }
        catch (e) {}
    }
    e.preventDefault();
 
    if(window.location.pathname == $(this)[0].pathname){
       
         window.open($(this).attr('href'),'_blank');
    }else{
        
       
        var title = $(this).attr('title');
        if($(this).find('.e-anchor-wrap').length > 0 && title==''){
            var title = $(this).find('.e-anchor-wrap').text();
        }
        viewDialog(modal_id, $(this).attr('href'), title, "90%");
    }
}));

$(document).on('click', 'a[data-target="view_modal_large"]', (function(e) {
   //  window.open($(this).attr('href'), "_blank");
   // return false;
    var modal_id = makeid(5);
    

   var close_dialog = $(this).attr('data-close-dialog');
 
   if (typeof close_dialog !== typeof undefined && close_dialog !== false) {
        try {
            $('.msg-popup:last').parent().remove();
            closeActivePopup();
        }
        catch (e) {}
    }
    e.preventDefault();
 
    if(window.location.pathname == $(this)[0].pathname){
         window.location.href = $(this).attr('href');
    }else{
        var title = $(this).attr('title');
        if($(this).find('.e-anchor-wrap').length > 0 && title==''){
            var title = $(this).find('.e-anchor-wrap').text();
        }
        viewDialog(modal_id, $(this).attr('href'), title, "95%","90%");
    }
}));


$(document).on('click', 'a[data-target="view_modal_full"]', (function(e) {
   //  window.open($(this).attr('href'), "_blank");
  //  return false;
    var modal_id = makeid(5);
  
   var close_dialog = $(this).attr('data-close-dialog');

   
   if (typeof close_dialog !== typeof undefined && close_dialog !== false) {
        try {
            $('.msg-popup:last').parent().remove();
            closeActivePopup();
        }
        catch (e) {}
    }
    e.preventDefault();
  
    if(window.location.pathname == $(this)[0].pathname){
         window.location.href = $(this).attr('href');
    }else{
        var title = $(this).attr('title');
        if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
        }
        viewDialog(modal_id, $(this).attr('href'), title, "95%","90%");
    }
}));

$(document).on('click', 'a[data-target="ajaxconfirm"]', (function(e) {
    e.preventDefault();
    var confirm_text = $(this).attr('confirm-text');
    gridAjaxConfirm($(this).attr('href'), confirm_text);
}));

$(document).on('click', 'a[data-target="ajax"]', (function(e) {
   
    var modal_id = makeid(5);
    e.preventDefault();
    gridAjax($(this).attr('href'));
}));

$(document).on('click', 'a[data-target="javascript"]', (function(e) {
   var js_function = $(this).attr('js-target');
  
   window[js_function](e);
}));


$(document).on('click', 'a[data-target="sidebarformlarge"]', (function(e) {
    /*
    if(session_instance_id==11){
        var url = $(this).attr('href');
    if (url.indexOf("email_form") >= 0){
    window.open(url);
    return false;    
    }
    }
    */
    var modal_id = makeid(5);
    e.preventDefault();
    var title = $(this).attr('title');
    if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
    }
    sidebarform(modal_id, $(this).attr('href'), title,'', "80%");
}));

$(document).on('click', 'a[data-target="sidebarform"]', (function(e) {
    /*
    if(session_instance_id==11){
        var url = $(this).attr('href');
    if (url.indexOf("email_form") >= 0){
    window.open(url);
    return false;    
    }
    }
    */
    console.log($(this));
    console.log($(this).attr('title'));
    var modal_id = makeid(5);
    e.preventDefault();
    var title = $(this).attr('title');
    if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
    }
    sidebarform(modal_id, $(this).attr('href'), title,'', "60%");
}));

$(document).on('click', 'a[data-target="sidebarformleft"]', (function(e) {
    /*
    if(session_instance_id==11){
        var url = $(this).attr('href');
    if (url.indexOf("email_form") >= 0){
    window.open(url);
    return false;    
    }
    }
    */
    console.log($(this));
    console.log($(this).attr('title'));
    var modal_id = makeid(5);
    e.preventDefault();
    var title = $(this).attr('title');
    if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
    }
    sidebarformleft(modal_id, $(this).attr('href'), title,'', "60%");
}));

$(document).on('click', 'a[data-target="sidebarview"]', (function(e) {
    var modal_id = makeid(5);
    e.preventDefault();
    var title = $(this).attr('title');
    if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
    }
    sidebarview(modal_id, $(this).attr('href'), title,'', "70%");
}));

$(document).on('click', 'a[data-target="form_modal"]', (function(e) {
  
    
    console.log($(this));
    console.log($(this).attr('title'));
    var modal_id = makeid(5);
    e.preventDefault();
    var title = $(this).attr('title');
    if($(this).find('.e-anchor-wrap').length > 0 && title==''){
        var title = $(this).find('.e-anchor-wrap').text();
    }
    sidebarform(modal_id, $(this).attr('href'), title,'', "60%");
}));


$(document).on('click', 'a[data-target="quick_dlg"]', (function(e) {
    var modal_id = makeid(5);
    e.preventDefault();
    quickDialog(modal_id, $(this).attr('href'), '', "70%");
}));

$(document).on('click', 'a[data-target="form_modal_large"]', (function(e) {
    var modal_id = makeid(5);
    e.preventDefault();
    formDialog(modal_id, $(this).attr('href'), '', "70%");
}));

$(document).on('click', 'a[data-target="form_edit_modal"]', (function(e) {
    try {
        closeActivePopup();
    }
    catch (e) {}
    var modal_id = makeid(5);
    e.preventDefault();
    formDialog(modal_id, $(this).attr('href'), '', "70%");
}));

$(document).on('click', 'a[data-target="transaction_modal"]', (function(e) {
    e.preventDefault();
   
    transactionDialog('place_order', $(this).attr('modal_url'), 'Create Quote', '90%');
}));

$(document).on('click', 'a[data-target="transaction"]', (function(e) {
   
    e.preventDefault();
   
    transactionDialog('place_order', $(this).attr('modal_url'), 'Create Quote', '90%');
}));

$(document).on('click', 'a[data-target="popup_form"]', (function(e) {
    var modal_id = makeid(5);
    e.preventDefault();
    popupForm(modal_id, $(this).attr('href'), '', "100%", "100%");
}));

$(document).on('click', 'a[data-target="form_modal_full"]', (function(e) {
    var modal_id = makeid(5);
    e.preventDefault();
    formDialog(modal_id, $(this).attr('href'), '', "100%", "100%");
}));

function makeid(length) {
   var result           = '';
   var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
   var charactersLength = characters.length;
   for ( var i = 0; i < length; i++ ) {
      result += characters.charAt(Math.floor(Math.random() * charactersLength));
   }
   return result;
}



    function isMobile() {
        try{ document.createEvent("TouchEvent"); return true; }
        catch(e){ return false; }
    }
