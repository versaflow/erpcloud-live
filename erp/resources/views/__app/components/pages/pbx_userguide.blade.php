<div id="main_content">
				
	<b style="color:black">USER GUIDE</b><br>
	<br>
	<b>Free usage till month-end</b><br>
	Just choose available phone numbers or add extensions and you will be billed for it at month-end.<br>
	Essentially, you are getting free usage of the service for the month until month-end.<br>
	<br>
	<b>How to set up your PBX system</b><br>
	Set up all the items under the ROUTING tab from 1 to 6, in order.<br>
	Only items 1 and 6 are required for a basic system setup.<br>
	More complex call routing will require the use of most of the items from 1 to 6.<br>
	Note that if prior items are altered, later item will need to be updated, that's why the order is important.<br>
	All fields are descriptive and self explanatory. <br>
	If you are unsure, don't hesitate to call us.<br>
	Don't edit the advanced options unless you are an expert user.<br>
	<br>
	<b>How to create a professional welcome message audio file</b><br>
	1. Use https://www.naturalreaders.com/online/ to speak the text for your welcome message.<br>
	2. Set up Audio Capture for Chrome in order to record the sound. Remember to set the output format to wav in options.<br>
	3. Upload to recordings on your pbx.<br>
	4. Choose the file in the IVR drop down list.<br>
	<br>
	<b>Inbound call center setup</b><br>
	1. Create users for each agent and link them to the agent user group.<br>
	2. Create the extensions for each agent and link the user using the user drop-down.<br>
	3. Create the agents and link them to the extension.<br>
	4. Create a Call Center Queue and assign your agents to tiers.
	<br><br>
	<b>Outbound call center setup</b><br>
	1. Create users for each agent and link them to the agent user group.<br>
	2. Create the extensions for each agent and link the user using the user drop-down.<br>
	3. Create the agents and link them to the extension.<br>
	4. Create a list and assign the agent to the list.<br>
	5. Create dispositions in order to log the calls.
	<br><br>
	<b style="color:black">FEATURE CODES [Requires DTMF Mode: Outband RFC2833]</b><br><br>
	@foreach($feature_codes as $category => $codes)
	<h4><b>{{ $category }}</b></h4>
	<table class="table table-striped table-bordered">
	<thead>
		<tr><td>Feature Code</td><td>Name</td><td>Detail</td></tr>
	</thead>
	<tbody>
	@foreach($codes as $c)
			<tr><td>{{$c->code}}</td><td>{{$c->name}}</td><td>{{$c->detail}}</td></tr>
	@endforeach
	</tbody>
	</table>
	@endforeach
</div>
<style>
	
	#main_content {
		display: inline-block;
		width: 100%;
		background: #ffffff;
		padding: 20px;
		text-align: left;
		color: #5f5f5f;
		font-size: 12px;
		font-family: arial;
		}

	.title, b {
		color: #952424;
		font-size: 15px;
		font-family: arial;
		font-weight: bold
		}


</style>