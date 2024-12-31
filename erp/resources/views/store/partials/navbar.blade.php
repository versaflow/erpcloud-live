  <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg position-sticky mt-0 top-0 px-0 mx-0 shadow-none z-index-sticky bg-white" id="navbarBlur" data-scroll="true">
      <div class="container py-1 px-3">
        <a class="navbar-brand p-0" href="#">
            @if($branding_logo)
            <img src="{{$branding_logo}}" alt=""  height="50">
            @else
             <a class="navbar-brand mb-0 h1" href="#">{{ session('instance')->name }}</a>
            @endif
        </a>
      
       
          <ul class="navbar-nav  justify-content-end">
            <li class="nav-item d-flex align-items-center me-4">
              <a href="../../../pages/authentication/signin/illustration.html" class="nav-link text-body font-weight-bold px-0" target="_blank">
                <i class="fa fa-shopping-cart me-sm-1"></i>
                <span class="d-sm-inline d-none">Cart</span>
              </a>
            </li>
            <li class="nav-item d-flex align-items-center">
              <a href="../../../pages/authentication/signin/illustration.html" class="nav-link text-body font-weight-bold px-0" target="_blank">
                <i class="fa fa-user me-sm-1"></i>
                <span class="d-sm-inline d-none">Sign In</span>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>