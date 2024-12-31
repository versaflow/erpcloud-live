<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=windows-1252"/>
 	<title>{{ $webform_title  }}</title>

	<style type="text/css">
		@page { size: 21.59cm 27.94cm; margin-left: 2cm; margin-right: 2cm; margin-top: 1.27cm; margin-bottom: 0.48cm }
		p { margin-bottom: 0.25cm; direction: ltr; line-height: 115%; text-align: left; orphans: 2; widows: 2; background: transparent }
		p.western { so-language: en-ZA }
		p.cjk { font-family: ; so-language: en-ZA }
		a:link { color: #0563c1; text-decoration: underline }
	</style> <link rel="stylesheet" href="{{ public_path().'/assets/pdfs/style.css' }}" media="all" />
</head>
<body lang="en-US" link="#0563c1" vlink="#800000" dir="ltr">
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
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">{{ date('Y-m-d') }}<br/>
Cloud Telecoms<br/>
</font><br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">								</font></p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">To whom it may concern:</font></p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt"><b>Application to port
number(s) to Vox Telecom.</b></font></p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">I hereby submit {{$account->company}} application to port the below listed number(s) to Vox
Telecom.</font></p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<table width="690" cellpadding="7" cellspacing="0">
	<col width="70"/>

	<col width="71"/>

	<col width="71"/>

	<col width="71"/>

	<col width="90"/>

	<col width="118"/>

	<col width="98"/>

	<tr valign="top">
		<td width="70" height="68" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Number
			Range - Start to end (Geo or Non Geo)</b></font></font></p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="margin-bottom: 0cm; orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Block
			Size</b></font></font></p>
			<p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b><font color="#7f7f7f">(amount
			of numbers)</font></b></font></font></p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Linkded
			Geo Number Range - Start to end</b></font></font></p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="margin-bottom: 0cm; orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Block
			Size</b></font></font></p>
			<p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b><font color="#7f7f7f">(amount
			of numbers)</font></b></font></font></p>
		</td>
		<td width="90" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Type
			of line <font color="#7f7f7f">(as per supplier bill)</font></b></font></font></p>
		</td>
		<td width="118" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="margin-bottom: 0cm; orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Name
			of existing Network Operator:</b></font></font></p>
			<p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font color="#7f7f7f"><font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>(Examples:
			Telkom, Liquid, etc.)</b></font></font></font></p>
		</td>
		<td width="98" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Account
			Number at Current Network Operator </b></font></font>
			</p>
		</td>
	</tr>
	<tr valign="top">
		<td width="70" height="9" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font color="#7f7f7f"><font face="Calibri, serif"><font size="2" style="font-size: 10pt">{{ $row['number_to_port'] }}</font></font></font></p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="90" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font color="#7f7f7f"><font face="Calibri, serif"><font size="2" style="font-size: 10pt">{{ $row['account_type'] }}</font></font></font></p>
		</td>
		<td width="118" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font color="#333366"><font face="Arial, serif"><b><span style="background: #ffffff">{{ $row['service_provider'] }}</span></b></font></font></p>
		</td>
		<td width="98" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font color="#7f7f7f"><font face="Calibri, serif"><font size="2" style="font-size: 10pt">{{ $row['account_number'] }}</font></font></font></p>
		</td>
	</tr>
	<tr valign="top">
		<td width="70" height="9" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="90" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="118" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="98" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
	</tr>
	<tr valign="top">
		<td width="70" height="8" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="71" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="90" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="118" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
		<td width="98" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
	</tr>
</table>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<table width="690" cellpadding="7" cellspacing="0">
	<col width="429"/>

	<col width="118"/>

	<col width="98"/>

	<tr valign="top">
		<td width="429" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 11pt"><b>Are
			any of the above numbers going to be provisioned on operator
			connect?</b></font></font></p>
		</td>
		<td width="118" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 11pt">Yes
			</font></font>
			</p>
		</td>
		<td width="98" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 11pt">No
			 x</font></font></p>
		</td>
	</tr>
</table>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">Preferred date for porting:   
{!! date('Y-m-d',strtotime($row['created_at'])) !!}<br/>
Company Registration No:     </font>
</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">Caller line Identity to be
displayed: </font><font color="#7f7f7f"><font size="2" style="font-size: 10pt">{{ $row['number_to_port'] }}</font></font></p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">I confirm that  Cloud Telecoms will be responsible for notifying our current
Network Operator of our intention to port existing number/s to Vox
Telecom.  </font>
</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">I further acknowledge that
Value-Added Services (like ADSL data, Call Barring, Call Forwarding,
Hunting Facilities, Supreme Call, etc.) cannot be ported and
functionality will be lost once porting has taken place. </font>
</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">The number porting process
does not result in automatic cancellation of any services with your
current Network Operator. It is the customers responsibility to
cancel services with the current operator and manage any queries that
may arise. </font>
</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%"><a name="_Hlk507481628"></a>
<font color="#ff0000"><font size="2" style="font-size: 10pt"><b>Please
note: </b></font></font>
</p>
<ol>
	<li><p style="margin-bottom: 0.28cm; line-height: 115%"><font color="#ff0000"><font size="2" style="font-size: 10pt"><span lang="en-GB"><b>If
	only one number or a partial range of numbers within a number block
	is ported, the remaining numbers within that specific block will be
	discontinued by the current supplier. </b></span></font></font>
	</p>
	<li><p style="margin-bottom: 0.28cm; line-height: 115%"><font color="#ff0000"><font size="2" style="font-size: 10pt"><span lang="en-GB"><b>In
	the case of A</b></span></font></font><font color="#ff0000"><font size="2" style="font-size: 10pt"><b>DSL,
	data services and functionality will be lost once the ADSL number(s)
	have been ported to Vox Telecom.</b></font></font></p>
	<li><p style="margin-bottom: 0.28cm; line-height: 115%"><font color="#ff0000"><font size="2" style="font-size: 10pt"><b>Please
	supply details of any specialised routing that you may have on the
	VOX PBX or hosted PBX.</b></font></font></p>
	<li><p style="margin-bottom: 0.28cm; line-height: 115%"><font color="#ff0000"><font size="2" style="font-size: 10pt"><b>Should
	the customer wish to port an 0800 number (toll free number) please
	ensure the service is quoted. Product Code: V-TOLL-FREE.</b></font></font></p>
</ol>
<p lang="en-ZA" class="western" style="margin-bottom: 0.28cm; line-height: 108%">
<br/>
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%; page-break-before: always">
<br/>

</p>
<table width="633" cellpadding="7" cellspacing="0">
	<col width="514"/>

	<col width="89"/>

	<tr valign="top">
		<td width="514" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="margin-bottom: 0cm; orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Section
			below to be completed only if porting Telkom ISDN number/s. </b></font></font>
			</p>
			<p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Specify
			what must happen after the number(s) have been ported to
			Vox.<br/>
Please select one option only.</b></font></font></p>
		</td>
		<td width="89" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p lang="en-ZA" class="western" align="left" style="orphans: 2; widows: 2">
			<font face="Calibri, serif"><font size="2" style="font-size: 10pt"><b>Tick
			Applicable Block</b></font></font></p>
		</td>
	</tr>
	<tr>
		<td width="514" height="16" style="border: 1px solid #000000; padding: 0cm 0.19cm">
			<ol><li><p align="justify" style="orphans: 2; widows: 2"><font face="Calibri, serif"><font size="2" style="font-size: 10pt">Cancel
				ISDN PRI/BRI line(s) and the balance of the number range.</font></font></p>
			</ol>
		</td>
		<td width="89" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p class="western" align="center" style="margin-top: 0.21cm; orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
	</tr>
	<tr>
		<td width="514" height="16" style="border: 1px solid #000000; padding: 0cm 0.19cm">
			<ol start="2"><li><p align="justify" style="orphans: 2; widows: 2">
				<font face="Calibri, serif"><font size="2" style="font-size: 10pt">Keep
				the PRI/BRI service as well as permissible number blocks.</font></font></p>
			</ol>
		</td>
		<td width="89" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p class="western" align="center" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
	</tr>
	<tr>
		<td width="514" height="15" style="border: 1px solid #000000; padding: 0cm 0.19cm">
			<ol start="3"><li><p align="justify" style="orphans: 2; widows: 2">
				<font face="Calibri, serif"><font size="2" style="font-size: 10pt">Allocate
				a new block of numbers to the ISDN PRI/BRI line(s).</font></font></p>
			</ol>
		</td>
		<td width="89" style="border: 1px solid #000000; padding: 0cm 0.19cm"><p class="western" align="center" style="orphans: 2; widows: 2">
			<br/>

			</p>
		</td>
	</tr>
</table>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<font size="2" style="font-size: 10pt">I hereby confirm that I,
Cloud Telecoms  am duly authorised to sign this document
on behalf of {{$account->company}} as well as having understood and
acknowledging</font><font size="2" style="font-size: 10pt"> </font><font size="2" style="font-size: 10pt">the
above.</font></p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
<br/>

</p>
<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">


<img src="{{ uploads_url(577).'signature.png' }}" border="0" style="max-width:200px; max-height:100px"/>;

<br/>
Signature
</p>

<p lang="en-ZA" class="western" style="margin-bottom: 0cm; line-height: 115%">
{!! date('Y-m-d',strtotime($row['created_at'])) !!}
<br/>
Date
</p>

</main>
</body>
</html>