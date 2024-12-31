@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
@endif

@section('content')

<script src="{{ '/assets/pako.min.js' }}"></script>
<html style="margin:0px;overflow:hidden;"><head>
	<script type="text/javascript">
		var DRAW_IFRAME_URL = 'https://embed.diagrams.net/?embed=1';
		var graph = null;
		var xml = null;

		function mxClientOnLoad(stylesheet)
		{
			xml = document.getElementById('mxfile').innerHTML;
			xml = decodeURIComponent(xml);

			// Removes all illegal control characters before parsing
			var checked = [];

			for (var i = 0; i < xml.length; i++)
			{
				var code = xml.charCodeAt(i);

				// Removes all control chars except TAB, LF and CR
				if (code >= 32 || code == 9 || code == 10 || code == 13)
			    {
			    	checked.push(xml.charAt(i));
			    }
			}

			xml = checked.join('');

			var div = document.createElement('div');
			div.style.width = '100%';
			div.style.height = '100%';
			div.style.position = 'relative';
			document.body.appendChild(div);
			graph = new mxGraph(div);

			graph.resetViewOnRootChange = false;
			graph.foldingEnabled = false;
			// NOTE: Tooltips require CSS
			graph.setTooltips(false);
			graph.setEnabled(false);

			// Loads the stylesheet
			if (stylesheet != null)
			{
				var xmlDoc = mxUtils.parseXml(stylesheet);
				var dec = new mxCodec(xmlDoc);
				dec.decode(xmlDoc.documentElement, graph.getStylesheet());
			}

			var xmlDoc = mxUtils.parseXml(xml);
			var codec = new mxCodec(xmlDoc);
			codec.decode(codec.document.documentElement, graph.getModel());
			graph.maxFitScale = 1;
			graph.fit();
			graph.center(true, false);

			window.addEventListener('resize', function()
			{
				graph.fit();
				graph.center(true, false);
			});
		}

		function edit(url)
		{
			var border = 0;
			var iframe = document.createElement('iframe');
			iframe.style.zIndex = '9999';
			iframe.style.position = 'absolute';
			iframe.style.top = border + 'px';
			iframe.style.left = border + 'px';

			if (border == 0)
			{
				iframe.setAttribute('frameborder', '0');
			}

			var resize = function()
			{
				iframe.setAttribute('width', document.body.clientWidth - 2 * border);
				iframe.setAttribute('height', document.body.clientHeight - 2 * border);
			};

			window.addEventListener('resize', resize);
			resize();

			var receive = function(evt)
			{
			    
						
				if (evt.data == 'ready')
				{
					iframe.contentWindow.postMessage(xml, '*');
					resize();
				}
				else
				{
					if (evt.data.length > 0)
					{
						// Update the graph
						var xmlDoc = mxUtils.parseXml(evt.data);
						var codec = new mxCodec(xmlDoc);
						codec.decode(codec.document.documentElement, graph.getModel());
						graph.fit();
						graph.center(true, false);

						var data = encodeURIComponent(evt.data);
						
					
						var idx = doc.indexOf('<div ' + 'id="mxfile"');
						var newdoc = doc.substring(0, idx) + '\n<div ' + 'id="mxfile" style="display:none;">' +
							data + '</d' + 'iv>' +
							'\n<script type="text/javascript">\nvar doc = document.documentElement.outerHTML;\n</' + 'script>' +
							'\n<script type="text/javascript" src="https://www.draw.io/embed.js"></' + 'script></body></html>';
                      
					}

				//	window.removeEventListener('resize', resize);
				//	window.removeEventListener('message', receive);
				//	document.body.removeChild(iframe);
				}
			};

			window.addEventListener('message', receive);
			iframe.setAttribute('src', DRAW_IFRAME_URL);
			document.body.appendChild(iframe);
		}

		function save(data)
		{
			try
			{     
				$.ajax({
					url: '/diagram_save',
					data: {xml: data, id: {{$id}} },
					type: 'post',
					success: function(result){
					    //console.log(result);
					    alert(result.message);
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
<body style="margin:10px;overflow:hidden;">


<div id="mxfile" style="display:none;">{!! $xml !!}</div>
<script type="text/javascript">
var doc = document.documentElement.outerHTML;
</script>
<script type="text/javascript" src="https://www.draw.io/embed.js"></script>
<script>
	$(document).ready(function(){
		edit();	
	});
</script>
<script>
	function stringToBytes(str)
{
    var arr = new Array(str.length);

    for (var i = 0; i < str.length; i++)
    {
        arr[i] = str.charCodeAt(i);
    }

    return arr;
};

function bytesToString(arr)
{
    var str = '';

    for (var i = 0; i < arr.length; i++)
    {
        str += String.fromCharCode(arr[i]);
    }

    return str;
};

function encode(data)
{
    if (document.getElementById('encodeCheckbox').checked)
    {
        try
        {
            data = encodeURIComponent(data);
        }
        catch (e)
        {
            //console.log(e);
            alert('encodeURIComponent failed: ' + e);

            return;
        }
    }

    if (document.getElementById('deflateCheckbox').checked && data.length > 0)
    {
		try
        {
        	data = bytesToString(pako.deflateRaw(data));
        }
        catch (e)
        {
            //console.log(e);
            alert('deflateRaw failed: ' + e);

            return;
        }
    }

    if (document.getElementById('base64Checkbox').checked)
    {
    	try
    	{
        	data = btoa(data);
        }
        catch (e)
        {
            //console.log(e);
            alert('atob failed: ' + e);

            return;
        }
    }

	if (data.length > 0)
	{
    	document.getElementById('textarea').value = data;
    }
};

function removeLinebreaks(data)
{
    document.getElementById('textarea').value = data.replace(/(\r\n|\n|\r)/gm, '');
};

function decode(data)
{
    try
    {
        var node = parseXml(data).documentElement;

        if (node != null && node.nodeName == 'mxfile')
        {
            var diagrams = node.getElementsByTagName('diagram');

            if (diagrams.length > 0)
            {
                data = getTextContent(diagrams[0]);
            }
        }
    }
    catch (e)
    {
        // ignore
    }


    try
    {
        data = atob(data);
    }
    catch (e)
    {
        //console.log(e);
        alert('atob failed: ' + e);

        return;
    }

 
    try
    {
        data = bytesToString(pako.inflateRaw(data));
    }
    catch (e)
    {
        //console.log(e);
        alert('inflateRaw failed: ' + e);

        return;
    }

  try
    {
        data = decodeURIComponent(data);
    }
    catch (e)
    {
        //console.log(e);
        alert('decodeURIComponent failed: ' + e);

        return;
    }
    

return data;
};

function parseXml(xml)
{
    if (window.DOMParser)
    {
        var parser = new DOMParser();

        return parser.parseFromString(xml, 'text/xml');
    }
    else
    {
        var result = createXmlDocument();

        result.async = 'false';
        result.loadXML(xml);

        return result;
    }
};

function createXmlDocument()
{
    var doc = null;

    if (document.implementation && document.implementation.createDocument)
    {
        doc = document.implementation.createDocument('', '', null);
    }
    else if (window.ActiveXObject)
    {
        doc = new ActiveXObject('Microsoft.XMLDOM');
    }

    return doc;
};

function decodeFromUri()
{
  try
  {
    document.getElementById('textarea').value = decodeURIComponent(document.getElementById('textarea').value)
  }
	catch (e)
	{
    //console.log(e);
    alert('decodeURIComponent failed: ' + e);
  }
};

function getTextContent(node)
{
    return (node != null) ? node[(node.textContent === undefined) ? 'text' : 'textContent'] : '';
};

function normalizeXml()
{
  try
  {
    var str = document.getElementById('textarea').value;
    str = str.replace(/>\s*/g, '>');  // Replace "> " with ">"
    str = str.replace(/\s*</g, '<');  // Replace "< " with "<"
    document.getElementById('textarea').value = str;
  }
  catch (e)
  {
    alert(e.message);
  }
};

function formatXml()
{
  try
  {
    var xmlDoc = new DOMParser().parseFromString(document.getElementById('textarea').value, 'application/xml');
    var xsltDoc = new DOMParser().parseFromString([
        // describes how we want to modify the XML - indent everything
        '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">',
        '  <xsl:strip-space elements="*"/>',
        '  <xsl:template match="para[content-style][not(text())]">', // change to just text() to strip space in text nodes
        '    <xsl:value-of select="normalize-space(.)"/>',
        '  </xsl:template>',
        '  <xsl:template match="node()|@*">',
        '    <xsl:copy><xsl:apply-templates select="node()|@*"/></xsl:copy>',
        '  </xsl:template>',
        '  <xsl:output indent="yes"/>',
        '</xsl:stylesheet>'
    ].join('\n'), 'application/xml');

    var xsltProcessor = new XSLTProcessor();
    xsltProcessor.importStylesheet(xsltDoc);
    var resultDoc = xsltProcessor.transformToDocument(xmlDoc);
    var resultXml = new XMLSerializer().serializeToString(resultDoc);

    document.getElementById('textarea').value = resultXml;
  }
  catch (e)
  {
    alert(e.message);
  }
};

function formatJson(indent)
{
  try
  {
    var str = document.getElementById('textarea').value;
    document.getElementById('textarea').value = JSON.stringify(JSON.parse(str), null, indent);
  }
  catch (e)
  {
    alert(e.message);
  }
};

function jsVar()
{
  try
  {
    var str = document.getElementById('textarea').value;
    var lines = str.split('\n');
    var result = [];

    for (var i = 0; i < lines.length; i++)
    {
      if (i < lines.length - 1 || lines[i].length > 0)
      {
        result.push('\'' + lines[i].replace(/\\/g, '\\\\').replace(/\'/g, '\\\'') + '\\n\'');
      }
    }

    document.getElementById('textarea').value = result.join(' +\n');
  }
  catch (e)
  {
    alert(e.message);
  }
};
	
</script>
</body></html> 
@endsection