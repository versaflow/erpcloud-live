@php 
$currency_symbol = get_currency_symbol($payroll->document_currency);
@endphp

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<div style="padding:5%">

<table style="width:600px" class="table">
    <tr>
        <td><b>Company Name</b></td>
        <td>{{ $company->company }}</td>
    </tr>
    <tr>
        <td><b>Company Address</b></td>
        <td>{{ $company->address }}</td>
    </tr>
    <tr>
        <td><b>Pay Date</b></td>
        <td>{{ $payroll->payroll_end_date }}</td>
    </tr>
</table>
<br><br>

    
<table  style="width:400px"  class="table">
    <tr>
        <td><b>Contractor Name</b></td>
        <td>{{ $employee->name }}</td>
    </tr>
    @if(!empty($employee->id_number))
    <tr>
        <td><b>ID Number</b></td>
        <td>{{ $employee->id_number }}</td>
    </tr>
    @endif
    <tr>
        <td><b>Start Date</b></td>
        <td>{{ $employee->start_date }}</td>
    </tr>
</table>


<br><br>
<table  style="width:100%">
<tr>
<td>
    
<table  style="width:400px;" class="lines"  class="table">
    <thead>
        <th>Earnings</th>
        <th>Amount</th>
        <th>Details</th>
    </thead>
    <tr>
        <td><b>Basic Amount</b></td>
        <td>{{ $currency_symbol }} {{ $payroll->total }}</td>
    </tr>
    @foreach($payroll_details as $payroll_detail)
        @if($payroll_detail->total > 0)
        <tr>
            <td><b>{{ $payroll_detail->type }}</b></td>
            <td>{{ $currency_symbol }} {{ currency($payroll_detail->total) }}</td>
            <td>{{ $payroll_detail->details }}</td>
        </tr>
        @endif
    @endforeach
   
</table>
</td>
<td>
<table  style="width:400px" class="lines"  class="table">
    <thead>
        <th>Deductions</th>
        <th>Amount</th>
        <th>Details</th>
    </thead>
    @if( !empty($payroll->loan_amount_paid))
    <tr>
        <td><b>Loan amount paid</b></td>
        <td>{{ $currency_symbol }} {{ $payroll->loan_amount_paid }}</td>
    </tr>
    @endif
    
    @foreach($payroll_details as $payroll_detail)
        @if($payroll_detail->total < 0)
        <tr>
            <td><b>{{ $payroll_detail->type }}</b></td>
            <td>{{ $currency_symbol }} {{ abs(currency($payroll_detail->total)) }}</td>
            <td>{{ $payroll_detail->details }}</td>
        </tr>
        @endif
    @endforeach
   
</table>
<br>
<table  style="width:400px" class="lines"  class="table">
    <thead>
        <th></th>
        <th>NET Amount</th>
    </thead>
    <tr>
        <td><b></b></td>
        <td><b>{{ $currency_symbol }} {{ $payroll->month_total }}</b></td>
    </tr>
</table>

</td>
</tr>
</table>

</div>
<style >
   td{ vertical-align: top;}
   table.lines {
    border-collapse: collapse;
}
table.lines > thead{
    background-color: #eee;
}

table.lines, table.lines>th, table.lines>td {
    border: 1px solid black;
}
table.lines th {
    text-align: left;
    padding-top: 5px;
    padding-bottom: 5px;
}
</style>
