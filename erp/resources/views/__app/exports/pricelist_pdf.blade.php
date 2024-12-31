<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>{{ $admin->company }} Pricelist</title>
    <link href="/assets/libaries/bootstrap/bootstrap.min.css" rel="stylesheet" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/pdfs/style.css" media="all" />
  </head>
  <body>
    <header class="clearfix">
      <div id="logo">
          <img src="{{ $logo_src }}" id="logoimg">
      </div>
      <div id="company">
        <h2 class="name">{{ $admin->company }}</h2>
        @if($admin->vat_number)
        <div>VAT Number: {{ $admin->vat_number }}</div>
        @endif
        @if(!empty($admin->phone))
        <div>{{ $admin->phone }}</div>
        @endif
        <div><a href="mailto:{{ ($admin->notification_sales) ? $admin->notification_sales : $admin->email }}">{{ ($admin->notification_sales) ? $admin->notification_sales : $admin->email }}</a></div>
        <br>
        <div>{!! nl2br($admin->address) !!}</div>
      </div>
      </div>
    </header>
    <main id="pdf_container" class="m-1">
    
      <div style="text-align:right;"><b> Qty>=1 does not apply to resellers. </b></br></div>
 
    
@foreach($product_categories as $product_category)
    
     @if($currency == 'ZAR' && !empty($bundles) && count($bundles) > 0)
      @foreach($bundles as $category_id => $bundle_lines)
      
      @if($category_id == $product_category->id)
      
      <table class="pricing_table table table-sm table-bordered">
      <thead>
        <tr>
      <th colspan="3" style="font-weight:bold; text-align:left;"><h3 style="margin:0;padding:2px 5px;">{{ strtoupper($product_category->department.' - '.$product_category->name) }} BUNDLES</h3></th>
      </tr>
      <tr>
      <th style="font-weight:bold;text-align:left;width:30%;">Name</th>
      <th style="font-weight:bold;text-align:left;width:40%;">Details</th>
      <th style="font-weight:bold;text-align:right;width:30%;">Price Incl</th>
      </tr>
      </thead>
      <tbody>
     
      @foreach($bundle_lines as $bundle)
      <tr>
      <td style="text-align:left;"><b>{{ $bundle->name }}</b></td>
      <td style="text-align:left;">{!! $bundle->description !!}</td>
      <td style="text-align:right;">{{ $currency_symbol.' '.currency($bundle->total) }}</td>
      </tr>
      @endforeach
      </tbody>
      </table>
      
      @endif
      @endforeach
      @endif
      
    @if(!empty($pricelist_items[$product_category->id]) && count($pricelist_items[$product_category->id]) > 0)
   
      <table class="pricing_table table table-sm table-bordered">
      <thead>
      <tr>
      <th colspan="10" style="font-weight:bold; text-align:left;"><h3 style="margin:0;padding:2px 5px;">{{ strtoupper($product_category->department.' - '.$product_category->name) }}</h3></th>
      </tr>
      <tr>
      <th style="font-weight:bold;text-align:left;width:100px;">Image</th>
      <th style="font-weight:bold;text-align:left;width:100px;">Code</th>
      <th style="font-weight:bold;text-align:left;width:300px;">Contract</th>
      <th style="font-weight:bold;text-align:left;width:300px;">Description</th>
      <th style="font-weight:bold;text-align:right;width:100px;">Retail Price</th>
      @if($enable_discounts)
      <th style="font-weight:bold;text-align:right;width:100px;">Wholesale Price</th>
      <th style="font-weight:bold;text-align:right;width:100px;">Qty >= 6</th>
      <th style="font-weight:bold;text-align:right;width:100px;">Qty >= 12</th>
      <th style="font-weight:bold;text-align:right;width:100px;">Qty >= 24</th>
    
      @endif
      </tr>
      </thead>
      <tbody>
      @foreach($pricelist_items[$product_category->id] as $pricelist_item)
      <tr>
      <td style="text-align:center;">@if($pricelist_item->image > '')<img src="{!! url($pricelist_item->image) !!}" class="pricelist_img"/>@endif</td>
      <td style="text-align:left;">{{ $pricelist_item->code }}</td>
      <td style="text-align:left;">@if($pricelist_item->frequency == 'once off') Once off @else Monthly  @endif</td>
      <td style="text-align:left;"><b>{{ $pricelist_item->name }}</b><br>
      {!! strip_tags($pricelist_item->description) !!}</td>
      <td style="text-align:right;">{{ $currency_symbol.' '.currency($pricelist_item->price_tax) }}</td>
      @if($enable_discounts)
      <td style="text-align:right">{{ $currency_symbol.' '.currency($pricelist_item->reseller_price_tax) }}</td>
      <td style="text-align:right">{{ $currency_symbol.' '.currency($pricelist_item->price_tax_6) }}</td>
      <td style="text-align:right">{{ $currency_symbol.' '.currency($pricelist_item->price_tax_12) }}</td>
      <td style="text-align:right">{{ $currency_symbol.' '.currency($pricelist_item->price_tax_24) }}</td>
   
      @endif
      </tr>
      @endforeach
      </tbody>
      </table>
    @endif
  
@endforeach

    </main>
  </body>
<style>

body {
    position: relative;
    width: 100% !important;
}

.pricing_table{
  border-collapse: collapse;
  width:100%;
  margin-bottom:40px;
}
table, tr, td, th, tbody, thead, tfoot {
    page-break-inside: avoid !important;
}


.pricelist_img{
  max-height:200px; 
  width: 100px;
  max-width:100px;
}
</style>
</html>

