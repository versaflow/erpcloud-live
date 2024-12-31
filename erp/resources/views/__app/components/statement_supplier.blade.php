<div class="container-fluid p-4">
<div class="k-widget k-button-group">
<a id="download_statement_btn" class="k-button" href="/supplier_statement_download/{{$account_id}}">Download Statement</a>
<a id="download_full_statement_btn" class="k-button" href="/supplier_statement_download/{{$account_id}}/1">Download Full Statement</a>
</div>
<div>
<br><div style="background: transparent url('. {{ public_path().'/assets/loading.gif' }} .');background-position: center;background-repeat: no-repeat;">
<object height="1250px" width="100%" type="application/pdf" data="{{ $file_url }}">
<param value="aaa.pdf" name="src"/>
<param value="transparent" name="wmode"/>
</object>
</div>
</div>
</div>