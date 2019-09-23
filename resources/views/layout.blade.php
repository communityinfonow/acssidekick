<!DOCTYPE html>
<html lang="en">
<head>
@if (env('GA_TRACK_ID') && env('GA_TRACK_ID') != '')
	<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('js', new Date());

		gtag('config', '{{ env('GA_TRACK_ID') }}');
	</script>
@endif	
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
	<meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    <!-- Bootstrap Core CSS -->
    <link href="{{ URL::asset('lib/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">

	<!-- JQuery-UI CSS -->
	<link href="{{ URL::asset('lib/jquery/jquery-ui.css') }}" rel="stylesheet">

	<!-- jsgrid -->
	<link href="{{ URL::asset('lib/jsgrid/jsgrid.min.css') }}" rel="stylesheet">
	<link href="{{ URL::asset('lib/jsgrid/jsgrid-theme.min.css') }}" rel="stylesheet">

	<!--
	<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.css" />
	<link type="text/css" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid-theme.min.css" />
	-->

    <!-- MetisMenu CSS -->
    <link href="{{ URL::asset('lib/metisMenu/metisMenu.min.css') }}" rel="stylesheet">


    <!-- Custom CSS -->
    <link href="{{ URL::asset('css/sidekick.css') }}" rel="stylesheet">

    <!-- Custom Fonts -->
    <link href="{{ URL::asset('lib/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body>
    <div style="width: 100%;" class="container-fluid" id="wrapper">
		@include('navigation')
		@include('modals')
		@yield('content')
    </div>

    <!-- jQuery -->
    <script src="{{ URL::asset('lib/jquery/jquery.min.js') }}"></script>
	<script src="{{ URL::asset('lib/jquery/jquery-ui.js') }}"></script>

	<!-- jsgrid -->
	<script src="{{ URL::asset('lib/jsgrid/jsgrid.min.js') }}"></script>

	<!-- FastMD5 -->
	<script src="{{ URL::asset('js/md5.min.js') }}"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="{{ URL::asset('lib/bootstrap/js/bootstrap.min.js') }}"></script>

    <!-- Metis Menu Plugin JavaScript -->
    <script src="{{ URL::asset('lib/metisMenu/metisMenu.min.js') }}"></script>

    <!-- Custom Theme JavaScript -->
    <script src="{{ URL::asset('js/sidekick.js') }}"></script> 
</body>
</html>
