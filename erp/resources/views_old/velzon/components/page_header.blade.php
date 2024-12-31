<!-- start page header -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between py-0">
            <h4 class="mb-sm-0 font-size-18 py-2">{{ $title }}</h4>

            <div class="page-title-right d-flex">
                @if($related_items_menu_menu)   
                {!! $related_items_menu_menu !!}
                @endif
            </div>

        </div>
    </div>
</div>
<!-- end page header -->
