<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>IP Authorization Letter</title>
    <link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ public_path().'/assets/pdfs/style.css' }}" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div class="text-center">
        @if($logo)
       
          <img src="{{ $logo_path }}" id="logoimg" height="100px">
      
        @endif
      </div>
    </header>
    <main>
    <div class="container">
    <div class="row">
    
    <div class="col" >
        <h3><b>{{ $admin->company }}</b></h3>
    </div>
    <div class="col text-right text-end">
        <h6>Issue Date: {{ date('Y-m-d') }}<br>
        Email: {{ $helpdesk_email }}<br>
        WEB: https://cloudtelecoms.co.za</h6>
    </div>
    </div>
        
    <div class="row"  style="margin-top:40px">  
        <p>
        To whom it may concern,<br>
        <b>{{ $admin->company }}</b> hereby authorizes {{$loa_company}} to announce the following IP<br>
        blocks via Autonomous System Number {{ $iprange->loa_as_number }} (AS{{ $iprange->loa_as_number }}) operated by {{$loa_company}}:
        </p>
        
        <p>
        <b>{{ $iprange->ip_range }}</b>
        </p>
        
        <p>
        <b>{{ $admin->company }}</b> reserves the right to revoke this authorization in case of end of contract <br>
        or repeated abuse complaints received at any time and as a result {{$loa_company}} has to remove IP<br>
        address prefix(s) from BGP configuration upon <b>{{ $admin->company }}</b> request.
        </p>
        
        <p>
        Any concerned party who has questions or concerns regarding this Letter of Authorization may contact<br>
        <b>{{ $admin->company }}</b> by e-mail at {{ $helpdesk_email }}.
        </p>
        
        <p>
        This Letter of Authorization for use of Internet Resources is issued by:<br>
        Name: {{ $admin->contact }}<br>
        E-mail: {{ $helpdesk_email }}<br><br>
        Address:<br>
        {!! nl2br($admin->address) !!}<br><br>
        Registration No: {{ $admin->company_registration_number }}
        <p>
     
      </div>
    </main>
 
  </body>
</html>