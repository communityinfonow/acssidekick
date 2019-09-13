@extends('layout')

@section('content')
        <div id="page-wrapper">
            <div class="row">
                <div class="col-lg-12">
                    <h4 style="text-transform: uppercase; font-weight: bold; margin-bottom: 20px;">Query Builder<span id="queryname"></span></h4>
                </div>
                <!-- /.col-lg-12 -->
            </div>
            <div class="row">
				<div><h4 style="position: relative; display: block; text-align: center;" class="divline"><span>Required</span></h4></div>
				<div class="col-lg-4">
					<div class="form-group">
						<label>Dataset and Year</label>
						<select id="selectdataset" class="form-control"></select>
					</div> 
				</div>
				<div class="col-lg-4">
					<div class="form-group">
						<label>Geography Type</label>
						<select disabled id="selectgeography" class="form-control"></select>
						<input type=hidden id="loadedgeo">
					</div> 
				</div>
				<div class="col-lg-4">
					<div class="form-group">
						<label>Topic or Table Name</label>
						<input disabled id="selectconcept" class="form-control autocomplete" placeholder="Type Keywords or Table ID ...">
					</div> 
				</div>
            </div>
			<div class="row">
				<div class="col-lg-6">
					<div class="form-group"> 
						<label>Available Fields</label>
						<input id="filtervariables" class="form-control" placeholder="Filter ...">
						<select id="selectvariables" multiple size=15 class="form-control" style="whitespace: wrap;">
						</select>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="form-group" style="max-width: 100%;">
						<label>Selected Fields</label>
						<input id="filterselected" class="form-control" placeholder="Filter ...">
						<select id="selectedvariables" multiple size=15 class="form-control">
						</select>
					</div>
				</div>
			</div>
			<div class="row">
				<div><h4 style="position: relative; display: block; text-align: center;" class="divline"><span>Optional</span></h4></div>
				<div class="col-lg-6">
					<div id="datafilters" class="form-group">
						<label>Data Filters</label>
						<div style="width: 100%;" class="input-group">
							<div style="width: 40%; vertical-align: top;" class="input-group-btn">
								<select id="datafiltervar" class="form-control">
									<option value="">Select a field ...</option>
								</select>
							</div>
							<div style="width: 15%;vertical-align: top;" class="input-group-btn">
								<select style="-moz-appearance: none; -webkit-appearance: none; appearance: none;" id="datafilterexpr" class="form-control">
									<option value="is">is</option>
									<option value="in">in set</option>
									<option value="in list">in list</option>
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
						<label>Percentage Calculator (Choose Denominator)</label>
						<div style="vertical-align: top;" class="input-group-btn">
							<select id="denominator" class="form-control disabled">
								<option value="">None Selected</option>
							</select>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div id="geoaggs" class="form-group">
						<label>Aggregate Geographies</label>
						<div style="width: 100%;" class="input-group">
							<div style="width: 40%; vertical-align: top;" class="input-group-btn">
								<input id="geoaggname" class="form-control" placeholder="New geo name ...">
							</div>
							<div style="width: 20%;vertical-align: top;" class="input-group-btn">
								<select style="-moz-appearance: none; -webkit-appearance: none; appearance: none;" id="geoaggexpr" class="form-control">
									<option value="">...</option>
								</select>
							</div>
							<div style="max-width: 40%; width: 40%; vertical-align: top;" class="input-group-btn">
								<textarea id="geoaggvals" class="form-control hidden" placeholder="One value per line ..."></textarea>
								<input id="geoaggvalstxt" class="form-control" placeholder="One value per line ...">
							</div>
							<div style="vertical-align: top;" class="input-group-btn">
								<button id="addgeoagg" class="btn btn-primary disabled">
									<i class="fa fa-plus" aria-hidden="true"></i>
								</button>
							</div>
						</div>
						<ul id="geoagglist" class="list-group">
						</ul>
					</div>
					
					<div id="customaggs" class="form-group">
						<label>Aggregate Fields</label>
						<div style="width: 100%;" class="input-group">
							<div style="width: 40%; vertical-align: top;" class="input-group-btn">
								<input id="customaggname" class="form-control" placeholder="New field name ...">
								<input type="hidden" id="customaggexpr">
							</div>
							<!--<div style="width: 20%;vertical-align: top;" class="input-group-btn">
								<select style="-moz-appearance: none; -webkit-appearance: none; appearance: none;" id="customaggexpr" class="form-control">
									<option value="">...</option>
								</select>
							</div>
							!-->
							<div style="max-width: 60%; width: 60%; vertical-align: top;" class="input-group-btn">
								<textarea id="customaggvals" class="form-control hidden" placeholder="Select fields ..."></textarea>
								<div style="width: 100%; max-width: 100%; height: 90px; overflow: auto;" id="customaggcheckboxes" class="form-control hidden"></div>
								<input id="customaggvalstxt" class="form-control" placeholder="Select fields ...">
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
				<div><h4 style="position: relative; display: block; text-align: center;" class="divline"><span>Results</span></h4></div>
				<div class="col-lg-12">
					<div class="form-group">
						<label>SQL Query</label>
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
