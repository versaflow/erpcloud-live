<li class="nav-item menu_context" data-menu-id="{{$submenu->menu_id}}" data-button-function="{{$submenu->button_function}}" data-menu-location="{{$submenu->location}}">
    @if(isset($submenu->items) && count($submenu->items) > 0)
    <a class="nav-link " data-bs-toggle="collapse" aria-expanded="false" href="#submenu{{$submenu->menu_id}}">
    @else
    <a class="nav-link " data-target="{{$submenu->data_target}}" href="{{$submenu->url}}" @if($new_tab || $submenu->new_tab) target="_blank" @endif>
    @endif
    <span class="sidenav-mini-icon"> {{$submenu->text[0]}} </span>
    <span class="sidenav-normal"> {{$submenu->text}} <b class="caret"></b></span>
    </a>
    @if(isset($submenu->items) && count($submenu->items) > 0)
        <div class="collapse " id="submenu{{$submenu->menu_id}}">
            <ul class="nav nav-sm flex-column">
                @if($submenu->url > '' && $submenu->url != '#')
                <li class="nav-item menu_context" data-menu-id="{{$submenu->menu_id}}" data-button-function="{{$submenu->button_function}}" data-menu-location="{{$submenu->location}}">
                    <a class="nav-link " data-target="{{$submenu->data_target}}" href="{{$submenu->url}}" @if($new_tab || $submenu->new_tab) target="_blank" @endif>
                    <span class="sidenav-mini-icon"> {{$submenu->text[0]}} </span>
                    <span class="sidenav-normal"> {{$submenu->text}} <b class="caret"></b></span>
                    </a>
                </li>
                @endif
                @each('__app.layouts.partials.main_menu_recursive', $submenu->items, 'submenu')
            </ul>
        </div>
    @endif
</li>