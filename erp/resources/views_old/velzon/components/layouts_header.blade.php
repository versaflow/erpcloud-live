<!-- start layouts header -->
<div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="flex-grow-1 d-flex">
                            @if($gridlayouts)   
                            {!! $gridlayouts !!}
                            @endif
                        </div>
                        <div class="flex-shrink-0 layout_btns">
                            <div class="hstack text-nowrap gap-2">
                                @if(is_superadmin()) 
                            
                                <button type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false" class="btn btn-icon btn-primary btn-border waves-effect"><i
                                        class="mdi mdi-book"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="layoutsbtn_save{{ $grid_id }}"><i class="mdi mdi-book-check"></i>Save</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="layoutsbtn_edit{{ $grid_id }}"><i class="mdi mdi-book-cog"></i>Edit</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="layoutsbtn_duplicate{{ $grid_id }}"><i class="mdi mdi-book-arrow-right"></i>Copy</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="layoutsbtn_delete{{ $grid_id }}"><i class="mdi mdi-book-remove"></i>Delete</a></li>
                                    <div class="dropdown-divider"></div>
                                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="layoutsbtn_create{{ $grid_id }}"><i class="mdi mdi-book-plus"></i>Add New</a></li>
                                    <li><a class="dropdown-item d-flex align-items-center" href="#" id="layoutsbtn_manage{{ $grid_id }}"><i class="mdi mdi-book-multiple"></i>Manage</a></li>
                               
                                </ul>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
<!-- end layouts header -->
