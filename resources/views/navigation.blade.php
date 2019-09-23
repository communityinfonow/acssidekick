		<!-- Navigation -->
		<div class="branding-header jumbotron d-flex align-items-center">
			<h1>Community Information Now</h1>
		</div>
		<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0;">
			<div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
				<span class="navbar-brand">
                	<h3 style="margin:0;text-transform: uppercase;font-weight: bold;"><a href="{{ URL::to('/') }}">{{ config('app.name') }}</a></h3>
				</span>
            </div>
			<ul class="nav navbar-top-links">
				<li><a href="https://cinow.info/acs-sidekick/" target=_blank>About the ACS Sidekick</a></li>
				<li><a href="https://cinow.info/who-we-are/" target=_blank>About CI:Now</a></li>
				<!--<li><a href="{{ URL::asset('documents/ACS%20Sidekick%20Technical%20Notes.pdf') }} " target=_blank>User Notes</a></li>-->
				<li><a href="https://cinow.info/acs-sidekick-user-notes/" target=_blank>User Notes</a></li>
				<li><a href="https://cinow.info/contact/" target=_blank>Contact Us</a></li>
            @if (Auth::guest())
                <li class="navbar-right"><a href="{{ route('login') }}">Login</a></li>
                <li class="navbar-right"><a href="{{ route('register') }}">Register</a></li>
			</ul>
			@elseif (Auth::user()->hasRole('pending'))
                <li class="navbar-right dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
						Welcome, {{ Auth::user()->name }}!
                        <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li>
							<a href="logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        	<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                              {{ csrf_field() }}
                            </form>
                        </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
            @else
                <li class="navbar-right">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
						Welcome, {{ Auth::user()->name }}!
                        <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="#"><i class="fa fa-user fa-fw"></i> User Profile</a>
                        </li>
                        <li><a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a>
                        </li>
                        <li class="divider"></li>
                        <li>
							<a href="logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        	<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                              {{ csrf_field() }}
                            </form>
                        </li>
                    </ul>
                    <!-- /.dropdown-user -->
                </li>
                <!-- /.dropdown -->
            </ul>
			<!--<div><a href="{{ URL::asset('documents/sample.pdf') }}" target=_BLANK>HELP</a></div>-->
            <!-- /.navbar-top-links -->
            <div class="navbar-default sidebar" role="navigation">
                <div class="sidebar-nav navbar-collapse">
                    <ul class="nav" id="side-menu">
                        <li id="navquerybuilder"> <a href="{{ URL::to('/') }}"><i class="fa fa-database fa-fw"></i> Query Builder</a></li>
                        <li id="navqueries"><a href="#"><i class="fa fa-table fa-fw"></i> Queries<span id="querydropdown"></span></a>
							<ul id="navmyqueries" class="nav nav-second-level"></ul>
						</li>
                        <li id="navlists"><a href="{{ URL::to('/') }}"><i class="fa fa-list fa-fw"></i> Lists<span class="fa arrow"></span></a>
							<ul id="navmylists" class="nav nav-second-level"></ul>
						</li>
						@if(Auth::user()->hasRole('admin'))
						<li>&nbsp;</li> <!-- spacer -->
						<li id="navadmin"> <a href="{{ URL::to('/admin') }}"><i class="fa fa-laptop fa-fw"></i> Admin</a></li>
						@endif
                    </ul>
                </div>
				<!--<img width=256 src="{{ URL::asset('images/sidekick.gif') }}"></img>-->
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
			@endif
        </nav>
