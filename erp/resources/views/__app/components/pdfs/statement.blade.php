<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Statement {{ $account->company  }}</title>
    <link rel="stylesheet"  href="{{ public_path().'/assets/pdfs/style.css' }}" />
    <link rel="stylesheet" href="{{ public_path().'/assets/pdfs/style.css' }}" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
        @if($logo)
          @if($is_view)
          <img src="{{ $logo }}" id="logoimg">
          @else
          <img src="{{ $logo_path }}" id="logoimg">
          @endif
        @endif
      </div>
      <div id="company">
        <h2 class="name">{{ $reseller->company }}</h2>
        <div>{!! nl2br($reseller->address) !!}</div>
        <div>{{ $reseller->phone }}</div>
        <div><a href="mailto:{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}">{{ ($reseller->accounts_email) ? $reseller->accounts_email : $reseller->email }}</a></div>
      </div>
      </div>
    </header>
    <main>
      <div id="details" class="clearfix">
        <div id="client">
         
          <h2 class="name">{{ $account->company }}</h2>
          <div class="address">{{ $account->contact }}</div>
          <div class="address">{!! nl2br($account->address) !!}</div>
          <div class="email"><a href="mailto:{{ $account->email }}">{{ $account->email }}</a></div>
          @if(!$include_reversals)
          <div class="exclude_notice" style="color: red; font-size: 16px;">Invoices that were credited are excluded.</div>
          @endif
        </div>
        <div id="invoice">
          <h1>Statement</h1>
          <div class="date">Date: {{ date('Y/m/d') }}</div>
        </div>
      </div>
      <table cellspacing="0" cellpadding="0" class="table table-sm table-bordered table-striped">
        <thead>
          <tr>
            <th class="desc2 p-2">Date</th>
            <th class="desc2 thdesc p-2">Description</th>
            @if($account->type=='reseller')
            <th class="desc2 p-2">Account</th>
            @endif
            <th class="desc2 thref p-2">Reference</th>
            <th class="amount p-2">Debit</th>
            <th class="amount p-2">Credit</th>
            <th class="total p-2">Balance</th>
          </tr>
        </thead>
        <tbody>
          @php 
          $balance = $opening_balance;  
          $doclines_len = count($doclines);
          @endphp
          @foreach($doclines as $i => $line)
         
            @if($i == 0)
              @if(!$full_statement)
                <tr>
                  <td class="desc px-2"></td> 
                  <td class="desc px-2">OPENING BALANCE</td>  
                  <td class="desc px-2"></td>
                   @if($account->type=='reseller')
                  <td class="desc p-2"></td>
                  @endif
                  <td class="desc px-2"></td>
                  <td class="desc px-2"></td>
                  <td class="total px-2">{{ $currency_symbol }}{{  currency($opening_balance,$currency_decimals) }}</td>
                </tr>
              @endif
            @endif
            
            
          
            @php
              $balance += $line->total;
            @endphp
            
            @if($line->reference == 'Bad Debt Written Off' && $i == $doclines_len-1)
            
            @php
              $balance -= $line->total;
              $aging['balance'] -= $line->total;
            @endphp
            @endif
            
            <tr>
            <td class="desc px-2">{{ date('Y-m-d',strtotime($line->docdate)) }}</td>
            <td class="desc px-2">{{ $line->doctype_label.' #' }} @if($line->doc_no) {{$line->doc_no }} @else {{ $line->id }} @endif</td>
            @if($account->type=='reseller')
            <td class="desc p-2">{{ $line->reseller_user }}</td>
            @endif
            <td class="desc px-2">{{ $line->reference }} @if($line->doctype == 'Tax Invoice') @endif</td>
            
              @if($line->doctype == 'Payment' || str_contains($line->doctype,'Receipt'))
              <td></td>
              <td class="qty px-2">{{ get_currency_symbol($line->document_currency) }}{{  currency(abs($line->total),$currency_decimals) }}</td>
              @elseif($line->total < 0)
              <td></td>
              <td class="qty px-2">{{ get_currency_symbol($line->document_currency) }}{{  currency(abs($line->total),$currency_decimals) }}</td>
              @else
              <td class="qty px-2">{{ get_currency_symbol($line->document_currency) }}{{  currency(abs($line->total),$currency_decimals) }}</td>
              <td></td>
              @endif
           
            <td class="total px-2">{{ get_currency_symbol($line->document_currency) }}{{  currency($balance,$currency_decimals) }}</td>
          </tr>
          @php
            $i++;
          @endphp
          @endforeach
       </tbody>
      </table>
      <br>
        <table cellspacing="0" cellpadding="0" class="table table-sm table-bordered">
          <thead>
            <tr>
              <th class="total">Balance</th>
            </tr>
          </thead>
          <tbody>  
            <tr>
            
              <td class="total">{{ $currency_symbol }}{{ currency($closing_balance,$currency_decimals) }}</td>
             
            </tr>
            @if($aging['balance'] != 0)
            <tr>
                @if($aging['balance'] > 0)
                <td style="color:red;font-size: 15px;">Outstanding</td>
                @else
                <td style="color:green;font-size: 15px;">Available</td>
                @endif
            </tr>
          
            @endif
          </tbody>
        
        </table>
        
        @if(!empty($payfast_link))
         {!! $payfast_link !!}
        @endif
      @if(!$is_supplier && !empty($bank_details))
     
      <div id="notices" class="mt-4 px-3">
      <div><h5>BANK DETAILS:</h5></div>
      <div class="notice">{!! nl2br($bank_details) !!}</div>
      </div>
      @endif
        
    
    </main>
  </body>
</html>