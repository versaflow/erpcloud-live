@section('dashboard_toolbar')
<!--- toolbar start -->
<div id="dashboard_toolbar"></div>
<div id="dashboard_template_title">
<div class="col p-0 flex-row flex-nowrap d-flex align-items-center">
<h1 class="mb-0" style="font-size:0.9rem;font-weight:bold;">{{strtoupper($menu_name)}}</h1>
</div>
</div>


<div id="dashboard_template_menu" class="d-flex align-items-center justify-content-end ">
<!--<div class="k-widget k-button-group">
@foreach($links as $link)
<a href="{{ $link['url'] }}" class="k-button" target="_blank">{{ $link['name'] }}</a>
@endforeach
@if(!empty($iframes) && count($iframes) > 0)
@foreach($iframes as $module_iframe)
<a href="{{$module_iframe->url}}" class="k-button iframe_btn" target="_blank">{{ $module_iframe->name }}</a>
@endforeach
@endif
</div>-->
</div>

<!-- toolbar end -->
@endsection

@push('page-scripts')

<script>
    //toolbar
    window['dashboardtoolbar'] = new ej.navigations.Toolbar({
        items: [
            { template: "#dashboard_template_title", align: 'left' },
          
                { template: "#dashboard_template_menu", align: 'left' },
        ]
    });
    
    window['dashboardtoolbar'].appendTo('#dashboard_toolbar');
    
  
       
       
</script>
@endpush

@push('page-styles')

<style>
#dashboard_template_title, #dashboard_template_title span{
    font-family: "Lato", Arial, Sans-serif !important;
    font-weight: bold;
    color: {{$color_scheme['second_row_text_color']}};
    user-select: text;
}

#dashboard_template_title ,#dashboard_template_title:hover,#dashboard_template_title h1,#dashboard_template_title h1:hover{

    user-select: text;
    cursor: text;
}
#dashboard_toolbar .e-input-group{height: 26px;}


#dashboard_toolbar .e-input-group.e-ddl{
    border-radius: 4px;
}
#dashboard_toolbar .e-toolbar-left  .e-toolbar-item{
   padding-left: 12px !important;
}
#dashboard_toolbar .e-toolbar-right  .e-toolbar-item{
   padding-left: 0px !important;
}
#dashboard_toolbar .e-toolbar-right  .e-toolbar-item .e-menu-wrapper {
   padding-right: 0px !important;
}
#dashboard_toolbar, #dashboard_toolbar .e-toolbar-items{
    background-color: {{ $color_scheme['second_row_color'] }};
}
</style>
@endpush