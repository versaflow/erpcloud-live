<table>
    <tbody>
    <tr>
        <td>
            <h2 class="name" style="font-weight:bold;">{{ $admin->company }}</h2><br>
            @if($admin->vat_number)
            VAT Number: {{ $admin->vat_number }}<br>
            @endif
            @if(!empty($admin->phone))
            {{ $admin->phone }}<br>
            @endif
            {{ ($admin->notification_sales) ? $admin->notification_sales : $admin->email }}<br>
            {!! nl2br($admin->address) !!}
        </td>
        <td><img src="{{ $logo_path }}" id="logoimg" height="50px"></td>
        <td> All prices include vat</td>
    </tr>
    </tbody>
</table>
  
@foreach($product_categories as $product_category)
    @if(!empty($pricelist_items[$product_category->id]) && count($pricelist_items[$product_category->id]) > 0)
    <table>
        <thead>
        <tr>
            <th colspan="8" style="font-weight:bold"><h1>{{ strtoupper($product_category->department.' - '.$product_category->name) }}</h1></th>
        </tr>
        </thead>
    </table>
    <table style="border-collapse: collapse;border: 1px solid;background-color:#ccc;">
        <thead>
        <tr>
            <th style="font-weight:bold;">Code</th>
            <th style="font-weight:bold;">Name</th>
            <th style="font-weight:bold;text-align:right;">Old Price</th>
            <th style="font-weight:bold;text-align:right;">New Price</th>
            @if($enable_discounts)
            <th style="font-weight:bold;text-align:right;">Wholesale Price</th>
            <th style="font-weight:bold;text-align:right;">Bulk Price > 6</th>
            <th style="font-weight:bold;text-align:right;">Bulk Price > 12</th>
            <th style="font-weight:bold;text-align:right;">Bulk Price > 24</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach($pricelist_items[$product_category->id] as $pricelist_item)
            <tr>
                <td>{{ $pricelist_item->code }}</td>
                <td>{{ $pricelist_item->name }}</td>
                <td style="text-align:right; @if(!empty($pricelist_item->price_color)) color: {{$pricelist_item->price_color}}; @endif">{{ $pricelist_item->old_price }}</td>
                <td style="text-align:right; @if(!empty($pricelist_item->price_color)) color: {{$pricelist_item->price_color}}; @endif">{{ $pricelist_item->price_tax }}</td>
                @if($enable_discounts)
                <td style="text-align:right">{{ $pricelist_item->reseller_price_tax }}</td>
                <td style="text-align:right">{{ $pricelist_item->price_tax_6 }}</td>
                <td style="text-align:right">{{ $pricelist_item->price_tax_12 }}</td>
                <td style="text-align:right">{{ $pricelist_item->price_tax_24 }}</td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif
@endforeach
<style>

body {
    position: relative;
    width: 90% !important;
}
table, th, td {
  border: 1px solid;
}
table {
  border-collapse: collapse;
}
</style>

