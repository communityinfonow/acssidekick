		<!-- Navigation -->
		<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
			<div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
				<span class="navbar-brand">
                	<a href="{{ URL::to('/') }}">{{ config('app.name') }}</a>
				</span>
            </div>
            <!-- /.navbar-header -->
            @if (Auth::guest())
			<ul class="nav navbar-top-links navbar-right">
                <li><a href="{{ route('login') }}">Login</a></li>
                <li><a href="{{ route('register') }}">Register</a></li>
			</ul>
			@elseif (Auth::user()->hasRole('pending'))
            <ul class="nav navbar-top-links navbar-right">
				<li>Welcome, {{ Auth::user()->name }}!</li>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
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
            <ul class="nav navbar-top-links navbar-right">
				<li>Welcome, {{ Auth::user()->name }}!</li>
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">
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
                    </ul
                </div>
                <!-- /.sidebar-collapse -->
            </div>
            <!-- /.navbar-static-side -->
			@endif
        </nav>
