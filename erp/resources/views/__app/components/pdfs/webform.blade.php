<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{ $webform_title  }}</title>
    <link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ public_path().'/assets/pdfs/style.css' }}" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div>
        <h2>{{ $webform_title  }}</h2>
      </div>
    </header>
    <main>
      <div class="clearfix mb-4">
        {!! nl2br($webform_text)  !!}
      </div>
       <table class="table">
        @foreach($form_config as $c)
        @php
          if(!$c->webform){
            continue;
          }
          if($c->field == 'account_id'){
            continue;
          }
          if($c->field == 'subscription_id'){
            continue;
          }
          if($c->field_type == 'file'){
            continue;
          }
          if($c->field == 'id'){
            continue;
          }
          $value = $row[$c->field];
          
          if($c->field_type == 'signature'){
            $file =  $value;
            if (!empty($file) && file_exists(uploads_path($module->id).$file)) {
            $value = '<img src="'.uploads_url($module->id).$file.'" border="0" style="max-width:200px; max-height:100px"/>';
            }
          }
        @endphp
        <tr>
        <td>{{$c->label}}</td>
        <td>{!! $value !!}</td>
        </tr>
        @endforeach
       </table>
    </main>
  </body>
</html>