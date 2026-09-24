<nav class="navbar navbar-default navbar-static-top m-b-0">
    <div class="navbar-header">
        <a class="navbar-toggle font-20 hidden-sm hidden-md hidden-lg " href="javascript:void(0)" data-toggle="collapse" data-target=".navbar-collapse">
            <i class="fa fa-bars"></i>
        </a>
        <div class="top-left-part">
            <a class="logo" href="{{url('dashboard')}}">
                <b><img src="{{ asset('plugins/images/logo.png') }}" alt="logo"/></b>
                <span>
                    <img src="{{ asset('plugins/images/logo-text.png') }}" alt="logo-text" class="dark-logo" />
                </span>
            </a>
        </div>       
        
        <ul class="nav navbar-top-links navbar-left hidden-xs">
            <li>
                <a href="javascript:void(0)" class="sidebartoggler font-20 waves-effect waves-light"><i class="icon-arrow-left-circle"></i></a>
            </li>
        </ul>
        <ul class="nav navbar-top-links navbar-right pull-right">
            <li class="dropdown">
                <a class="dropdown-toggle waves-effect waves-light font-20" data-toggle="dropdown" href="javascript:void(0);">
                    <img src="{{ asset(!empty(Auth::user()->photo) ? Auth::user()->photo : 'images/profile/no-image.png') }}" class="img-circle" style="height: 35px;"> {{ Auth::user()->first_name . ' ' . Auth::user()->last_name }}
                    <i class="fa fa-chevron-circle-down"></i>
                </a>
                <ul class="dropdown-menu mailbox animated flipInY" style="width: 240px;">
                    <li>
                        <a href="{{url('profile-edit')}}" style="padding: 8px 20px;"><i class="fa fa-user"></i> Profile</a>
                    </li>
                    <li style="margin-top: 5px;" role="separator" class="divider"></li>
                    @if(Auth::user()->access_type == 'vendor')
                    <li>
                        <a href="{{url('vendor-profile')}}" style="padding: 8px 20px;"><i class="fa fa-user"></i> Vendor Profile</a>
                    </li>
                    <li style="margin-top: 5px;" role="separator" class="divider"></li>
                    @endif
                    <li>
                        <a href="{{url('logout')}}" style="padding: 8px 20px;"><i class="fa fa-power-off"></i> Logout</a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>