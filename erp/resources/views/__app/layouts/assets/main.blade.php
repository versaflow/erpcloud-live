@push('header_assets')
@include('__app.layouts.assets.header_libraries')
@include('__app.layouts.assets.header_theme')
@include('__app.layouts.assets.header_app')
@endpush

@push('footer_assets')
@include('__app.layouts.assets.footer_theme')
@include('__app.layouts.assets.footer_libraries')
@include('__app.layouts.assets.footer_app')
@endpush