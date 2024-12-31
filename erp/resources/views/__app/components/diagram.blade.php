@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif

@section('content')


<html style="margin:0px;overflow:auto;"><head>
<script>
  // Edits an image with drawio class on double click
  @if(check_access('1,31'))
  
  @if($edit)
  function edit_diagram(){
    $("#drawio").click();
  }
  $(document).ready(function(){
    edit_diagram();
  });
  @endif
  
  document.addEventListener('click', function(evt)
  {
    
    var url = 'https://embed.diagrams.net/?embed=1&ui=atlas&spin=1&modified=unsavedChanges&proto=json';
    var source = evt.srcElement || evt.target;

    if (source.nodeName == 'IMG' && source.className == 'drawio')
    {
      if (source.drawIoWindow == null || source.drawIoWindow.closed)
      {
        // Implements protocol for loading and exporting with embedded XML
        var receive = function(evt)
        {
          if (evt.data.length > 0 && evt.source == source.drawIoWindow)
          {
            var msg = JSON.parse(evt.data);
            ////console.log(msg.event);
            ////console.log(msg);
            // Received if the editor is ready
            if (msg.event == 'init')
            {
              // Sends the data URI with embedded XML to editor
              source.drawIoWindow.postMessage(JSON.stringify({action: 'load', xmlpng: source.getAttribute('src')}), '*');
            }
            // Received if the user clicks save
            else if (msg.event == 'save')
            {
              // Sends a request to export the diagram as XML with embedded PNG
              source.drawIoWindow.postMessage(JSON.stringify({action: 'export', format: 'xmlpng', spinKey: 'saving'}), '*');
             
            }
            // Received if the export request was processed
            else if (msg.event == 'export')
            {
              // Updates the data URI of the image
              source.setAttribute('src', msg.data);
              
              save(msg.data);
            }

            // Received if the user clicks exit or after export
            if (msg.event == 'exit' || msg.event == 'export')
            {
              // Closes the editor
              window.removeEventListener('message', receive);
              source.drawIoWindow.close();
              source.drawIoWindow = null;
            }
          }
        };

        // Opens the editor
        window.addEventListener('message', receive);
        source.drawIoWindow = window.open(url);
        
      }
      else
      {
        // Shows existing editor window
        source.drawIoWindow.focus();
      }
    }
  });
  
@endif
	function save(data)
	{
		try
		{     
			$.ajax({
				url: '/diagram_save',
				data: {xml: data, id: {{$id}} },
				type: 'post',
				success: function(result){
				    ////console.log(result);
				   // alert(result.message);
				}
			});
		}
		catch (e)
		{
			//console.log('error', e);
			//console.log('html', data);
		}
	};
	</script>
</head>
<body style="margin:10px;overflow:auto;">
<div style="text-align:center">
<img id="drawio" class="drawio" style="cursor:default;" src="{!! $xml !!}" />
</div>
<script type="text/javascript" src="https://www.draw.io/embed.js"></script>
<script>
var iframe = document.createElement('iframe');
iframe.setAttribute('frameborder', '0');

var close = function()
{
    window.removeEventListener('message', receive);
    document.body.removeChild(iframe);
};
	
</script>
<style>
    iframe {
        border:0;
        position:fixed;
        top:0;
        left:0;
        right:0;
        bottom:0;
        width:100%;
        height:100%
    }
</style>
</body></html> 
@endsection