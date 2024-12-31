
<script src="{{ '/assets/libraries/moment/moment.js' }}"></script>  
<script src="{{ '/assets/libraries/busy-load/app.min.js' }}"></script>


<!--<script type="text/javascript" src="{{ public_path().'/assets/formio/formio.full.min.js' }}"></script>
<script type="text/javascript" src="{{ public_path().'/assets/formio/components/color.js' }}"></script>-->
<script src="{{ '/assets/libraries/smartwizard/dist/js/jquery.smartWizard.js' }}"></script>
<!--<script src="https://cdn.tiny.cloud/1/r393xiac7oc37ggv1pogvslt7pmbnzxivf5ee5mkkxj7dfu7/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>-->

<script src="{{ '/assets/libraries/ckeditor/build/ckeditor.js' }}"></script>


<!-- Syncfusion -->

@php
$sf_version = 20; 

@endphp
@if($sf_version == 26)
<script src="https://cdn.syncfusion.com/ej2/26.1.35/dist/ej2.min.js"></script>
@elseif($sf_version == 23)
<script src="https://cdn.syncfusion.com/ej2/23.1.36/dist/ej2.min.js"></script>
@else
<script src="https://cdn.syncfusion.com/ej2/20.4.49/dist/ej2.min.js"></script>
@endif
<script>

@if($sf_version == 26)
    var syncfusion_key = 'ORg4AjUWIQA/Gnt2U1hhQlJBfV5AQmBIYVp/TGpJfl96cVxMZVVBJAtUQF1hTX5VdEVjUX9fdH1XTmlb';
    ej.base.registerLicense(syncfusion_key);
@elseif($sf_version == 23)
    var syncfusion_key = 'Ngo9BigBOggjHTQxAR8/V1NHaF5cWWdCf1FpRmJGdld5fUVHYVZUTXxaS00DNHVRdkdnWXpeeXRcRmVdVEVwW0Y=';
    ej.base.registerLicense(syncfusion_key);
@else
    var syncfusion_key = 'Mgo+DSMBaFt/QHRqVVhkX1pFdEBBXHxAd1p/VWJYdVt5flBPcDwsT3RfQF5jSH9RdkJgXXxecnBRQQ==;Mgo+DSMBPh8sVXJ0S0J+XE9AdVRDX3xKf0x/TGpQb19xflBPallYVBYiSV9jS31Td0RhWXhddHdVRGZfVg==;ORg4AjUWIQA/Gnt2VVhkQlFaclxJXGFWfVJpTGpQdk5xdV9DaVZUTWY/P1ZhSXxQdkRiW39Zc3BWRmJUUUM=;OTI3MTM4QDMyMzAyZTM0MmUzMEtmVWJ2TDM1UVZpalkvN2xoRVVqcjd1TjRaMWh1TnhJMzdoMzVnLzlza1U9;OTI3MTM5QDMyMzAyZTM0MmUzMGlsWTZXSTArbm1LMSs4M25NVE5mMWlLd2pIeVVJakp0WDkvU09kUnhPK0k9;NRAiBiAaIQQuGjN/V0Z+WE9EaFtBVmJLYVB3WmpQdldgdVRMZVVbQX9PIiBoS35RdUViWH1ed3dRRWBfWEBx;OTI3MTQxQDMyMzAyZTM0MmUzMGp3QTF5VCtOZUJBL0RlK29CWS8yNDlJRytESGI1eGhyTUtDSS9SajRIQ289;OTI3MTQyQDMyMzAyZTM0MmUzMEFlMTlabjNkaHREYkVOZTFtSGxGa0JUZEFYRE5ZKytpZGU3NDdVa3BhL0U9;Mgo+DSMBMAY9C3t2VVhkQlFaclxJXGFWfVJpTGpQdk5xdV9DaVZUTWY/P1ZhSXxQdkRiW39Zc3BWRmRVUkM=;OTI3MTQ0QDMyMzAyZTM0MmUzMElFUnBvQVpLZ3VMQTFXT1RYY1AvV3d2YjUrV1lrWXVwMnZFT2FFRjVqWFU9;OTI3MTQ1QDMyMzAyZTM0MmUzMEk0eWc5Q1NDTy9RQjlvZ29Rb2FaMDFyWDFsdGpta0hlZyt3bUlPVHYydWs9;OTI3MTQ2QDMyMzAyZTM0MmUzMGp3QTF5VCtOZUJBL0RlK29CWS8yNDlJRytESGI1eGhyTUtDSS9SajRIQ289';
    ej.base.registerLicense(syncfusion_key);
@endif
</script>
<!-- https://ag-grid.com/archive/28.1.1/javascript-data-grid/ -->
<!-- AG Grid -->

<script src="https://cdn.jsdelivr.net/npm/ag-grid-enterprise@28.1.1/dist/ag-grid-enterprise.min.js"></script>

<script>
    agGrid.LicenseManager.setLicenseKey("CompanyName=Cloud Telecoms,LicensedApplication=Turnkey ERP,LicenseType=SingleApplication,LicensedConcurrentDeveloperCount=1,LicensedProductionInstancesCount=0,AssetReference=AG-019616,ExpiryDate=28_September_2022_[v2]_MTY2NDMxOTYwMDAwMA==f5533e7bc5fcf06f9637e1a0a6a4543b");
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.5/ace.min.js" integrity="sha512-4jIkBpqqFqGrEbYKdA9em7OdCGyPlaximONDoUQ18wjw84zv7sUAqWLVb7pRjP5YyrWk0MDGFfom1zPUd6K+ng==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
 <script src="{{ '/assets/libraries/signature_pad/signature_pad.min.js' }}"></script>