@extends((( request()->ajax()) ? '__app.layouts.ajax' : '__app.layouts.app' ))

@if(!request()->ajax())
	
	
@endif

@section('content')
<div id="container">
<div id="PdfViewer" style="height:500px;width:100%;">
</div>
<script>
    var pdfviewer = new ej.pdfviewer.PdfViewer({
   // documentPath: "test.pdf",
    serviceUrl: 'https://ej2services.syncfusion.com/production/web-services/api/pdfviewer',
    enableFormDesignerToolbar: true
    });
    ej.pdfviewer.PdfViewer.Inject(ej.pdfviewer.FormDesigner);
    pdfviewer.appendTo('#PdfViewer');
</script>
@endsection