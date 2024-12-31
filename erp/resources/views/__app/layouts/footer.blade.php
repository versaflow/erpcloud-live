@section('footer')
<footer class="footer fixed-bottom">
<div class="control-section">
        <div class="toolbar-menu-control">
            <div id="footertoolbar"></div>
        </div>
    </div>
</footer> 
@stop

@push('page-scripts')
<script>

var toolbarObj = new ej.navigations.Toolbar({
    items: [
        { template: "<p>{{ session('account_id') }}  - {{ session('customer_company') }}</p>" },
        { type: 'Separator' },
        { type: 'Separator' },
        @if(is_customer_active(session('account_id')))
        
        { type: 'Separator' },
        @endif
        ,
        @if(session('role_level') == 'Admin')
            @if($module)
            { prefixIcon: 'fas fa-cube', align: 'Right',tooltipText: 'Module', click: module_edit},
            { prefixIcon: 'fas fa-bars', align: 'Right',tooltipText: 'Menu', click: menu_edit},
            { prefixIcon: 'far fa-window-restore', align: 'Right',tooltipText: 'Buttons', click: buttons_view},
            { prefixIcon: 'fas fa-code-branch', align: 'Right',tooltipText: 'Triggers', click: triggers_view},
            @endif
        @endif
    ]
}, '#footertoolbar');

</script>
@endpush