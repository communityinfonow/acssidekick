@extends('layout')

@section('content')
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h2>Query Builder<span id="queryname"></span></h2>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
				<div class="col-lg-4">
					<div class="form-group">
						<label>Dataset</label>
						<select id="selectdataset" class="form-control"></select>
					</div> 
				</div>
				<div class="col-lg-4">
					<div class="form-group">
						<label>Geography</label>
						<select disabled id="selectgeography" class="form-control"></select>
					</div> 
				</div>
				<div class="col-lg-4">
					<div class="form-group">
						<label>Concept</label>
						<input disabled id="selectconcept" class="form-control autocomplete" placeholder="Start typing...">
					</div> 
				</div>
            </div>
			<div class="row">
				<div class="col-lg-6">
					<div class="form-group">
						<label>Available Variables</label>
						<input id="filtervariables" class="form-control" placeholder="Filter ...">
						<select id="selectvariables" multiple size=15 class="form-control">
						</select>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="form-group">
						<label>Selected Variables</label>
						<input id="filterselected" class="form-control" placeholder="Filter ...">
						<select id="selectedvariables" multiple size=15 class="form-control">
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6">
					<div id="datafilters" class="form-group">
						<label>Data Filters</label>
						<div style="width: 100%;" class="input-group">
							<div style="width: 40%; vertical-align: top;" class="input-group-btn">
								<select id="datafiltervar" class="form-control">
									<option value="">Select a variable ...</option>
								</select>
							</div>
							<div style="width: 15%;vertical-align: top;" class="input-group-btn">
								<select style="-moz-appearance: none; -webkit-appearance: none; appearance: none;" id="datafilterexpr" class="form-control">
									<option value="is">is</option>
									<option value="in">in</option>
									<option value="in list">list</option>
								</select>
							</div>
							<div style="width: 40%;vertical-align: top;" class="input-group-btn">
								<input id="datafiltervalue" class="form-control" placeholder="Value ...">
							</div>
							<div style="vertical-align: top;" class="input-group-btn">
								<button id="adddatafilter" class="btn btn-primary disabled">
									<i class="fa fa-plus" aria-hidden="true"></i>
								</button>
							</div>
						</div>
						<ul id="filterlist" class="list-group">
						</ul>
					</div>
					<div id="denominatorgrp" class="form-group">
						<label>Percentage Denominator</label>
						<div style="vertical-align: top;" class="input-group-btn">
							<select id="denominator" class="form-control disabled">
								<option value="">Disabled</option>
							</select>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div id="customaggs" class="form-group">
						<label>Aggregations</label>
						<div style="width: 100%;" class="input-group">
							<div style="width: 40%; vertical-align: top;" class="input-group-btn">
								<input id="customaggname" class="form-control" placeholder="Alias ...">
							</div>
							<div style="width: 20%;vertical-align: top;" class="input-group-btn">
								<select style="-moz-appearance: none; -webkit-appearance: none; appearance: none;" id="customaggexpr" class="form-control">
									<option value="">...</option>
								</select>
							</div>
							<div style="width: 40%; vertical-align: top;" class="input-group-btn">
								<textarea id="customaggvals" class="form-control hidden" placeholder="One value per line ..."></textarea>
								<div style="height: 90px; overflow: auto;" id="customaggcheckboxes" class="form-control hidden"></div>
								<input id="customaggvalstxt" class="form-control" placeholder="One value per line ...">
							</div>
							<div style="vertical-align: top;" class="input-group-btn">
								<button id="addcustomagg" class="btn btn-primary disabled">
									<i class="fa fa-plus" aria-hidden="true"></i>
								</button>
							</div>
						</div>
						<ul id="customagglist" class="list-group">
						</ul>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div class="form-group">
						<label>Query</label>
						<textarea readonly id="sqlquery" class="form-control"></textarea>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<a id="runbtn" data-toggle="tooltip" title="Preview results" class="btn btn-large btn-primary disabled" href="#">
						<i class="fa fa-table fa-lg" aria-hidden="true"></i>
					</a>
					<a id="exportjson" data-toggle="tooltip" title="Export JSON" class="btn btn-large btn-primary disabled" href="#">
						<i class="fa fa-file-code-o fa-lg" aria-hidden="true"></i>
					</a>
					<a id="exportcsv" data-toggle="tooltip" title="Export CSV" class="btn btn-large btn-primary disabled" href="#">
						<i class="fa fa-file-text-o fa-lg" aria-hidden="true"></i>
					</a>
				{{--
					<a id="exportxlsx" data-toggle="tooltip" title="Export XSLX" class="btn btn-large btn-primary disabled" href="#">
						<i class="fa fa-file-excel-o fa-lg" aria-hidden="true"></i>
					</a>
				--}}
					<button id="savequery" data-toggle="tooltip" title="Save Query" class="btn btn-large btn-success disabled pull-right">
						<i class="fa fa-floppy-o fa-lg" aria-hidden="true"></i>
					</button>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div id="resultsgrid" style="margin-top: 15px"></div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-12">
					<div id="resultsgridmessage" style="margin-top: 15px"></div>
				</div>
			</div>
            <!-- /.row -->
        </div>
        <!-- /#page-wrapper -->
@endsection
