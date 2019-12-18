		<!-- Modal windows -->
		<div id="savequerymodal" style="top: 20%; left: 40%;" class="modal modal-sm fade" role="dialog">
			<div class="modal-content">
				<div class="modal-header modal-header-primary">
					<span style="vertical-align: middle;" class="modal-title">
						<h4>Save Query
						<button style="display: inline-block; vertical-align: middle;" type="button" class="col btn btn-primary pull-right" data-dismiss="modal">
							<i class="fa fa-lg fa-times"  aria-hidden="true"></i>
						</button></h4>
					</span>
				</div>
				<div class="modal-body">
					<form id="savequeryform">
						<div class="form-group">
							<label>Name</label>
							<input id="savequeryname" class="form-control" placeholder="Query name ...">
						</div>
						<div class="form-group">
							<label>Description</label>
							<textarea disabled id="savequerydesc" class="form-control" placeholder="Brief description ..."></textarea>
						</div>
						<div class="input-group">
							<label>Public <input disabled id="savequerypublic" type="checkbox" checked class="form-check-input"></label>
							<span class="input-group-btn">
								<button id="savequerybutton" type="button" class="form-control btn btn-success disabled">Save</button>
							</span>
						</div>
					</form>
				</div>
				<div  id="savequeryresult" class="modal-footer"></div>
			</div>
		</div>
		<div id="savelistmodal" style="top: 20%; left: 40%;" class="modal modal-sm fade" role="dialog">
			<div class="modal-content">
				<div class="modal-header modal-header-primary">
					<span style="vertical-align: middle;" class="modal-title">
						<h4><span id="savelisttitle">List</span>
						<button style="display: inline-block; vertical-align: middle;" type="button" class="col btn btn-primary pull-right" data-dismiss="modal">
							<i class="fa fa-lg fa-times"  aria-hidden="true"></i>
						</button></h4>
					</span>
				</div>
				<div class="modal-body">
					<form id="savelistform">
						<div class="form-group">
							<label>Name</label>
							<input id="savelistname" class="form-control" placeholder="List name ...">
						</div>
						<div class="form-group">
							<label>Description</label>
							<textarea disabled id="savelistdesc" class="form-control" placeholder="Brief description ..."></textarea>
						</div>
						<div class="form-group">
							<label>Items</label>
							<textarea disabled id="savelistitems" class="form-control" placeholder="One per line ..."></textarea>
						</div>
						<div class="input-group">
							<label>Public <input disabled id="savelistpublic" type="checkbox" checked class="form-check-input"></label>
							<span class="input-group-btn">
								<button id="savelistbutton" type="button" class="form-control btn btn-success disabled">Save</button>
							</span>
						</div>
					</form>
				</div>
				<div  id="savelistresult" class="modal-footer"></div>
			</div>
		</div>
		<div id="customaggmodal" style="top: 20%; left: 10%;" class="modal modal-lg fade" role="dialog">
			<div class="modal-content">
				<div class="modal-header modal-header-primary">
					<span style="vertical-align: middle;" class="modal-title">
						<h4><span id="customaggstitle">Select Columns</span>
						<button style="display: inline-block; vertical-align: middle;" type="button" class="col btn btn-primary pull-right" data-dismiss="modal">
							<i class="fa fa-lg fa-times"  aria-hidden="true"></i>
						</button></h4>
					</span>
				</div>
				<div class="modal-body">
					<form id="customaggform">
						<div class="form-group">
							<div style="width: 100%; overflow: scroll; height:300px;" id="customaggcheckboxes" class="form-control hidden"></div>
						</div>
						<div class="input-group">
							<span class="input-group-btn">
								<button id="addcustomagg" type="button" class="form-control btn btn-primary disabled">Add</button>
							</span>
						</div>
					</form>
				</div>
				<div  id="customaggresult" class="modal-footer"></div>
			</div>
		</div>
