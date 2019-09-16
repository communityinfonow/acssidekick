@extends('layout')
@section('content')
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h2>Admin Tools</h2>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
                <div class="col-lg-12">
					<h4>Users</h4>
					<div id="user-role-grid"></div>
				</div>
			</div>
            <div class="row">
                <div class="col-lg-12">
					<h4>Branch status</h4>
					<textarea rows=10 style="width: 100%;" disabled class="form-control">
@php
	$cmd="cd ..; git status -uno";
	passthru($cmd);	
@endphp
					</textarea>	
					&nbsp;
				</div>
			</div>
        </div>
        <!-- /#page-wrapper -->
@endsection
