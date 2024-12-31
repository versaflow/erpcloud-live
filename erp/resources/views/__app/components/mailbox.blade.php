@extends( '__app.layouts.mailbox_app' )

@if(!request()->ajax())
    
	
@endif

@section('styles')

    <link href='{{ public_path()."assets/mailbox/styles/styles.css" }}' rel='stylesheet' />
    <link href='{{ public_path()."assets/mailbox/styles/Outlook Icons/style.css" }}' rel='stylesheet' />
    <link href='{{ public_path()."assets/mailbox/styles/css/outlook.css" }}' rel='stylesheet' />
    <style>
        #content{height:100%}
        .e-toolbar .e-toolbar-items .e-toolbar-item .e-icons {
        min-width: 0;
        }
        .sidebar{
            top:0px;
        }
    </style>
@endsection
@section('scripts')

<script>(function (w, d, s, l, i) {
    w[l] = w[l] || []; w[l].push({
        'gtm.start':
            new Date().getTime(), event: 'gtm.js'
    }); var f = d.getElementsByTagName(s)[0],
        j = d.createElement(s), dl = l != 'dataLayer' ? '&l=' + l : ''; j.async = true; j.src =
            'https://www.googletagmanager.com/gtm.js?id=' + i + dl; f.parentNode.insertBefore(j, f);
})(window, document, 'script', 'dataLayer', 'GTM-WLQL39J');</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/js-signals/1.0.0/js-signals.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crossroads/0.12.0/crossroads.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/hasher/1.2.0/hasher.min.js"></script>
	
    <script type="text/javascript">
		if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) {
			document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/bluebird/3.3.5/bluebird.min.js"><\/script>');
		}
	</script>
     <script>
         
    var userName = "{{ $username }}";
    var userMail = "{{ $usermail }}";
   
    var folderData = {!! json_encode($folders) !!};
   
    var messageDataSourceNew = {!! json_encode($messages) !!};
     </script>
    <script src="{{ '/assets/mailbox/js/datasource.js' }}" type="text/javascript"></script>
    <script src="{{ '/assets/mailbox/js/readingpane.js' }}" type="text/javascript"></script>
    <script src="{{ '/assets/mailbox/js/newmail.js' }}" type="text/javascript"></script> 
    <script src="{{ '/assets/mailbox/js/home.js' }}" type="text/javascript"></script>
    <script src="{{ '/assets/mailbox/js/index.js' }}" type="text/javascript"></script>
@endsection
@section('content')
    <div style="height:100%">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WLQL39J" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
    <div hidden id="sync-analytics" data-queue="EJ2 - Showcase - JavaScript ES5 - WebMail"></div>
    <div class='outlook-container'>
        <div class='header navbar' style="display:none">
            <div class='container-fluid' style="font-size: 0; white-space: nowrap; line-height: normal">
                <div style="display: table-cell">
                    <div class='home-btn'>
                        <span class='ej-icon-Bento' style='font-size: 18px;'></span>
                    </div>
                    <div style='margin-left: 15px;display: inline-block'>
                        <h1 class='home-btn-text'>Webmail</h1>
                    </div>
                </div>
                <div style="display: table-cell; width: 100%; text-align: center">
                </div>
                <div id="notification-div" class="header-right-pane">  
                    <div style="pointer-events: none;">
                        <button id='btnNotification' style="min-width: 52px"></button>
                    </div>
                </div>
                <div id="settings-div" class="header-right-pane">  
                    <div style="pointer-events: none;">
                        <button id='btnSettings' style="min-width: 52px"></button>
                    </div>
                </div>
                <div id="about-div" class="header-right-pane">  
                    <div style="pointer-events: none;">
                        <button id='btnAbout' style="min-width: 52px"></button>
                    </div>
                </div>
                <div id="profile-div" class="header-right-pane"> 
                    <div style="right: 0px;position: absolute;top: 3px;pointer-events: none;margin-right: 5px;">
                        <div class='logo logo-style1 profile-pic'></div>
                    </div>  
                    <div style="margin-right: 44px; pointer-events: none;">
                        <button id='btnLoginName' class="btn-profile" style="min-width: 88px;font-weight: normal"></button>
                    </div> 
                </div>
            </div>
        </div>
        <div id="content-area" style="height: 100%">
        </div>
    </div>
</div>
@endsection