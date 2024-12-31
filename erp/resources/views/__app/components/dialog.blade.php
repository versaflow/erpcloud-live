@section('dialog')
<div id="dialog">
</div>
<div id="toast">
</div>
@endsection
@push('page-scripts')

@if(Session::has('message'))
@php
$msg = str_replace(PHP_EOL,'<br>',Session::get("message"));
@endphp
<script>
$(function() {
toastNotify('{!! $msg !!}','{!! Session::get("status", "info") !!}');
});
</script>
@endif
<!-- custom code start -->
<script>
    function maximize(id) {
       
    var dlg = $("#"+id)[0]['ej2_instances'][0];
    var minimizeIcon = dlg.element.querySelector(".e-dlg-header-content .sf-icon-Restore");
   
    dlg.element.classList.remove('dialog-minimized');
    dlg.element.querySelector('.e-dlg-content').classList.remove('hide-content');
  
    minimizeIcon.setAttribute('title', 'Minimize');
    
    $(minimizeIcon).removeClass('sf-icon-Restore');
    $(minimizeIcon).addClass('sf-icon-Minimize');
    $(minimizeIcon).attr('onClick', 'minimize("'+id+'");');
    
    
    dlg.position = dlg.dialogOldPositions;
    dlg.height = window['diaLogOldheight'];
    dlg.width = window['diaLogOldwidth'];
   
   // $(dlg.element).closest('.e-dlg-container').find('.e-dlg-overlay').css('display','block');
   // $(dlg.element).closest('.e-dlg-container').css('z-index', dlg.containerZindex);
    dlg.dataBind();

    $(dlg.element).closest('.e-dlg-container').css('z-index',1000);
    //setTinyMce();
    
  }

  function minimize(id) {
    for (var i = tinyMCE.editors.length - 1; i > -1; i--) {
      var ed_id = tinymce.editors[i].id;
      tinyMCE.execCommand("mceRemoveEditor", true, ed_id);
    }
     /*
     todo rearrange minimized modals
     */
    var dlg = $("#"+id)[0]['ej2_instances'][0];
    var minimizeIcon = dlg.element.querySelector(".e-dlg-header-content .sf-icon-Minimize");
   
    
    
    dlg.dialogOldPositions = { X: dlg.position.X, Y: dlg.position.Y }
    window['diaLogOldheight'] = dlg.height;
    window['diaLogOldwidth'] = dlg.width;
    dlg.element.classList.add('dialog-minimized');
    dlg.element.classList.remove('dialog-maximized');
    dlg.element.querySelector('.e-dlg-content').classList.add('hide-content');
   
    
    var num_minimized_dialogs = $('.dialog-minimized').length;
    if(num_minimized_dialogs == 1){
      
     
      dlg.position = { X: 'left', Y: 'bottom' };
    }else{
      var modal_position = $(dlg.element).index('.dialog-minimized');
      //console.log('modal_position:'+modal_position);
      //console.log('num_minimized_dialogs:'+num_minimized_dialogs);
      var num_minimized_dialogs = modal_position;
      var left_pos = parseInt(num_minimized_dialogs * 340);
      dlg.position = { X: left_pos, Y: 'bottom' };
    }
    dlg.height = 'auto';
    dlg.width = '340px';
    dlg.isModal = false;
    //dlg.containerZindex = $(dlg.element).closest('.e-dlg-container').css('z-index');
   // $(dlg.element).closest('.e-dlg-container').find('.e-dlg-overlay').css('display','none');
  //  $(dlg.element).closest('.e-dlg-container').css('z-index',1);
    dlg.dataBind();

    $(dlg.element).css("position", "");
    $(minimizeIcon).addClass('sf-icon-Restore');
    $(minimizeIcon).removeClass('sf-icon-Minimize');
    minimizeIcon.setAttribute('title', 'Restore');
    $(minimizeIcon).attr('onClick', 'maximize("'+id+'");');
      
    
  }
    
</script>

@endpush
@push('page-styles')

<style> 
  .e-dialog .e-dlg-header .title{
    display:none;
  }
  .e-dialog.dialog-minimized .e-dlg-header .title{
     display: inline;
    color: #fff;
    font-size: 16px !important;
  }

  .e-dialog .e-dlg-header {
    width: auto;
  }

  .e-dialog .e-dlg-header .e-icons.sf-icon-Maximize::before,
  .e-dialog .e-dlg-header .e-icons.sf-icon-Minimize::before,
  .e-dialog .e-dlg-header .e-icons.sf-icon-Restore::before {
      position: relative;
  }

  .e-dialog .e-dlg-header .e-icons.sf-icon-Minimize,
  .e-dialog .e-dlg-header .e-icons.sf-icon-Maximize,
  .e-dialog .e-dlg-header .e-icons.sf-icon-Restore {
      color: #fff;
      font-size: 14px;
      width: 30px;
      height: 30px;
      line-height: 30px;
      float: right;
      position: relative;
      text-align: center;
      cursor: pointer;
  }

  .e-dialog .e-dlg-header .e-icons.sf-icon-Minimize:hover, .e-dialog .e-dlg-header .e-icons.sf-icon-Maximize:hover,
  .e-dialog .e-dlg-header .e-icons.sf-icon-Restore:hover {
      background-color: #e0e0e0;
      border-color: transparent;
      box-shadow: 0 0 0 transparent;
      border-radius: 50%;
  }

  .e-dialog .e-dlg-header .e-icons.sf-icon-Minimize,
  .e-dialog .e-dlg-header .e-icons.sf-icon-Restore {
      padding-left: 5px;
      padding-right: 5px;
  }
  
  .e-dialog .e-dlg-header {
      position: relative;
      top: 1px;
  }
  
  .e-dialog .e-dlg-content.hide-content, .e-dialog .e-footer-content.hide-content {
      display: none;
  }
  
  .e-dialog .e-dlg-header span.title {
      width: 60px;
      display: inline-block;
  }

@font-face {
    font-family: 'Min-Max_FONT';
    src: url(data:application/x-font-ttf;charset=utf-8;base64,AAEAAAAKAIAAAwAgT1MvMj1tSfUAAAEoAAAAVmNtYXDnE+dkAAABlAAAADxnbHlmQCkX6AAAAdwAAADkaGVhZBK7D5EAAADQAAAANmhoZWEIVQQGAAAArAAAACRobXR4FAAAAAAAAYAAAAAUbG9jYQBaAJwAAAHQAAAADG1heHABEgAgAAABCAAAACBuYW1l8Rnd5AAAAsAAAAJhcG9zdDbKxecAAAUkAAAATwABAAAEAAAAAFwEAAAAAAAD+AABAAAAAAAAAAAAAAAAAAAABQABAAAAAQAAK4KTXV8PPPUACwQAAAAAANfSZU4AAAAA19JlTgAAAAAD+AP4AAAACAACAAAAAAAAAAEAAAAFABQAAwAAAAAAAgAAAAoACgAAAP8AAAAAAAAAAQQAAZAABQAAAokCzAAAAI8CiQLMAAAB6wAyAQgAAAIABQMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAUGZFZABA5wDnAwQAAAAAXAQAAAAAAAABAAAAAAAABAAAAAQAAAAEAAAABAAAAAQAAAAAAAACAAAAAwAAABQAAwABAAAAFAAEACgAAAAEAAQAAQAA5wP//wAA5wD//wAAAAEABAAAAAEAAgADAAQAAAAAAA4AKgBMAHIAAQAAAAADkwIyAAMAABMhNSFtAyb82gHOZAAAAAMAAAAAA/gD+AADAAcACwAAAREhESUVITUDIREhA5P82gMm/NplA/D8EALK/aMCXcllZfx1A/AAAQAAAAADkwOSAAsAABMJARcJATcJAScJAWwBTf6zRwFNAU1H/rMBTUf+s/6zA0v+tf61RwFL/rVHAUsBS0f+tQFLAAADAAAAAAP4A/gABQALABMAABMzIREhESURIxEhNQcjESE1MxEh0mQBlP2jAyZl/ghkygMmyvzaAsr9owJdyf2jAfhlZfzaygMmAAAAAAASAN4AAQAAAAAAAAABAAAAAQAAAAAAAQAMAAEAAQAAAAAAAgAHAA0AAQAAAAAAAwAMABQAAQAAAAAABAAMACAAAQAAAAAABQALACwAAQAAAAAABgAMADcAAQAAAAAACgAsAEMAAQAAAAAACwASAG8AAwABBAkAAAACAIEAAwABBAkAAQAYAIMAAwABBAkAAgAOAJsAAwABBAkAAwAYAKkAAwABBAkABAAYAMEAAwABBAkABQAWANkAAwABBAkABgAYAO8AAwABBAkACgBYAQcAAwABBAkACwAkAV8gTWluLU1heF9GT05UUmVndWxhck1pbi1NYXhfRk9OVE1pbi1NYXhfRk9OVFZlcnNpb24gMS4wTWluLU1heF9GT05URm9udCBnZW5lcmF0ZWQgdXNpbmcgU3luY2Z1c2lvbiBNZXRybyBTdHVkaW93d3cuc3luY2Z1c2lvbi5jb20AIABNAGkAbgAtAE0AYQB4AF8ARgBPAE4AVABSAGUAZwB1AGwAYQByAE0AaQBuAC0ATQBhAHgAXwBGAE8ATgBUAE0AaQBuAC0ATQBhAHgAXwBGAE8ATgBUAFYAZQByAHMAaQBvAG4AIAAxAC4AMABNAGkAbgAtAE0AYQB4AF8ARgBPAE4AVABGAG8AbgB0ACAAZwBlAG4AZQByAGEAdABlAGQAIAB1AHMAaQBuAGcAIABTAHkAbgBjAGYAdQBzAGkAbwBuACAATQBlAHQAcgBvACAAUwB0AHUAZABpAG8AdwB3AHcALgBzAHkAbgBjAGYAdQBzAGkAbwBuAC4AYwBvAG0AAAAAAgAAAAAAAAAKAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAFAQIBAwEEAQUBBgAITWluaW1pemUITWF4aW1pemUFQ2xvc2UHUmVzdG9yZQAAAA==) format('truetype');
    font-weight: normal;
    font-style: normal;
}

[class^="sf-icon-"], [class*=" sf-icon-"] {
    font-family: 'Min-Max_FONT' !important;
    speak: none;
    font-size: 55px;
    font-style: normal;
    font-weight: normal;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.sf-icon-Minimize:before {
    content: "\e700";
}

.sf-icon-Maximize:before {
    content: "\e701";
}

.sf-icon-Close:before {
    content: "\e702";
}

.sf-icon-Restore:before {
    content: "\e703";
}


  </style>

@endpush