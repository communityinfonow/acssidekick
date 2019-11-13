@extends('layout')

@section('content')
<div class="container">
    <div style="margin-top: 20px;" class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-body">
					<p>You must verify your email address to complete registration.  A verification 
						link was sent to the email address you used in registration.  Once you have completed
						verification you may <a href="login" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><b>Log in</b></a>
						and use the Sidekick application.</p>  
					<p>If you are unable to complete verification, please <a href="https://cinow.info/contact/">Contact Us</a>
						for assistance.</p>
					<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
						{{ csrf_field() }}
					</form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
