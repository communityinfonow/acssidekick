/*
 * Start Bootstrap - SB Admin 2 v3.3.7+1 (http://startbootstrap.com/template-overviews/sb-admin-2)
 * Copyright 2013-2016 Start Bootstrap
 * Licensed under MIT (https://github.com/BlackrockDigital/startbootstrap/blob/gh-pages/LICENSE)
 */

/* Static config stuff */
// Fix horizontal scroll for muti-select
// per https://github.com/twbs/bootstrap/issues/12536
//$("#select").siblings().find("div.dropdown-menu.open").css("overflow", "auto")
//

$(document).ajaxError(function( event, jqxhr, settings, thrownError ) {
    if (jqxhr.status == 401 && window.location.pathname != '/login' 
		&& window.location.pathname != '/register' && window.location.pathname != '/pending') {
        window.location.replace("/login");
    }
});

// Set up CSRF config
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

/* Global functions */

// Auto build fields array for jsgrid based on data
function buildfields(griddata) {
	var fields = [];
	var keys = Object.keys(griddata[0]);

	for (var i = 0; i < keys.length; i++) {
		// Figure length of longest word
		var words = keys[i].split(" ");
		var longest = 0;
		for (var x = 0; x < words.length; x++) {
			if (longest < words[x].length) {
				longest = words[x].length;
			} 
		}

		fields.push({
			name: keys[i],
			title: keys[i].replace(/::/g,'.').replace("->", "<br>"),
			type: "number", 
			width: (longest * 15)
		});
	}
	return fields;
}

// Query building support
function buildqueryobject() {
	var queryobj = {
		db: $("#selectdataset option:selected").val(),
		table: "",
		cols: [],
		labels: {},
		filters: [],
		denominator: "",
		customaggs: {}
	};

	$("#selectedvariables > option").each(function() {
		var geo;
		if ($("#selectgeography").val()) {
			geo=$("#selectgeography").val();
		} else {
			geo=$("#loadedgeo").val();
		}

		var tbl=geo + "_" + this.value.substr(0, this.value.indexOf('_'));
		if (queryobj.table == '') {
			queryobj.table = tbl;
		}
		queryobj.cols.push(tbl + '.' + this.value);
		queryobj.labels[this.value] = this.text;
	});

	queryobj.filters = $("#datafilters").data("filters");
	queryobj.denominator = $("#denominator").val();
	queryobj.customaggs = $("#customaggs").data("customaggs");

	return JSON.stringify(queryobj);
}

function guid() {
	function s4() {
		return Math.floor((1 + Math.random()) * 0x10000)
		.toString(16)
		.substring(1);
	}
	return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
	s4() + '-' + s4() + s4() + s4();
}

// Load dataset values
function loadselectoptions(selector, source, placeholder, selectedval) {
    if (typeof placeholder === 'undefined') { placeholder = ''; }
	$.getJSON(source, null, function(data) {
		$(selector + " option").remove(); // clear out select options
        if (placeholder != '') {
    		$(selector).append( // Add the placeholder
    			$("<option>selected disabled</option>").text(placeholder).val("")	
    		);
        }
		$.each(data, function(idx, dataset) {
				if (selectedval == dataset.value) {
					$(selector).append( 
						$("<option selected></option>").text(dataset.label).val(dataset.value)
					);
				} else {
					$(selector).append( 
						$("<option></option>").text(dataset.label).val(dataset.value)
					);
				}
		});	
	}).done(function() {
		//$(selector).change();
	}).fail(function(jqXHR, textStatus, errorThrown) {
        console.log("error " + textStatus);
        console.log("incoming Text " + jqXHR.responseText);
    });
}

// Constructs a RAW http post and expects a file download
function requestexport(path, data) {
	var xhr = new XMLHttpRequest();
	xhr.open('POST', path, true);
	xhr.responseType = 'arraybuffer';
	xhr.onload = function () {

		if (this.status === 200) {
			var filename = "";
			var disposition = xhr.getResponseHeader('Content-Disposition');
			if (disposition && disposition.indexOf('attachment') !== -1) {
				var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
				var matches = filenameRegex.exec(disposition);
				if (matches != null && matches[1]) filename = matches[1].replace(/['"]/g, '');
			}
			var type = xhr.getResponseHeader('Content-Type');
	
			var blob = new Blob([this.response], { type: type });
			if (typeof window.navigator.msSaveBlob !== 'undefined') {
				// IE workaround for "HTML7007: One or more blob URLs were revoked by closing the blob for which they were created. 
				// These URLs will no longer resolve as the data backing the URL has been freed."
					window.navigator.msSaveBlob(blob, filename);
			} else {
				var URL = window.URL || window.webkitURL;
				var downloadUrl = URL.createObjectURL(blob);
	
				if (filename) {
					// use HTML5 a[download] attribute to specify filename
					var a = document.createElement("a");
					// safari doesn't support this yet
					if (typeof a.download === 'undefined') {
						window.location = downloadUrl;
					} else {
						a.href = downloadUrl;
						a.download = filename;
						document.body.appendChild(a);
						a.click();
					}
				} else {
					window.location = downloadUrl;
				}
				setTimeout(function () { URL.revokeObjectURL(downloadUrl); }, 100); // cleanup
			}
		}
	};		

	xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
	xhr.setRequestHeader('X-CSRF-TOKEN', $('meta[name="csrf-token"]').attr('content'));
	xhr.send(JSON.stringify(data));
}

// Helper function for saving queryies, lists, etc.
function saveobject(objname, obj, responsetarget) {
	var postdata = {};
	postdata['objname']=objname;
	postdata['obj']=obj;

	$.post('ajax/saveobject', JSON.stringify(postdata), function(resp) {
		$(responsetarget).html(resp);
		if (objname == 'query') {
			updatequerylist();
		}

		if (objname == 'list') {
			updatelistlist();

			// To trigger a query refresh in case new values were added
			$("#selectvariables").trigger('dblclick');
		}
	});
}

function updatelistlist() {
	$("#navmylists").html("");
	
	$.post('ajax/getlistlist').done(function(data) {
		data.forEach(function(item) {
			$("#navmylists").append(
				'<li id="list-'+item.id+'">' +
				'<i style="display:inline-block; width:  40px; padding: 10px 10px 10px 30px;" class="fa fa-trash text-danger delsavedlist"></i> ' +
				'<a style="display:inline-block; width: 200px; padding: 10px 10px 10px  5px;" class="savedlist" href="#" data-toggle="tooltip" title="'+item.desc+'">' +
				item.name + '</a></li>'
			);
		});
	});
	
	// Add the "Add List" option
	$("#navmylists").append(
		'<li id="addlist">' +
		'<i style="display:inline-block; width:  40px; padding: 10px 10px 10px 30px;" class="fa fa-plus-circle text-success"></i>' +
		'<a style="display:inline-block; width: 200px; padding: 10px 10px 10px  5px;" href="#"><em>Add New</em></a>' +
		'</li>'
	);

	// Re-create the addlist event binding
	$("#addlist").click(function() {
		if (!$(this).hasClass("disabled")) {
			$("#savelisttitle").text("Add List");
			$("#savelistmodal").modal();
		}
	});
		
}

function updatequerylist() {
	$("#navmyqueries").html("");
	$.post('ajax/getquerylist').done(function(data) {
		data.forEach(function(item) {
			$("#navmyqueries").append(
				'<li id="query-'+item.id+'">' +
				'<i style="display:inline-block; width:  40px; padding: 10px 10px 10px 30px;" class="fa fa-trash text-danger delsavedquery"></i> ' +
				'<a style="display:inline-block; width: 200px; padding: 10px 10px 10px  5px;" class="savedquery" href="#" data-toggle="tooltip" title="'+item.desc+'">' +
				item.name + '</a></li>'
			);
		});
		if ($('#navmyqueries li').length >= 1) {
			// IF there is at least one list item
			$('#querydropdown').addClass("fa");
			$('#querydropdown').addClass("arrow");
		} else {
			$('#querydropdown').removeClass("fa");
			$('#querydropdown').removeClass("arrow");
		}
	});
}

// Document ready actions
$(function() {
	// Bootstrap / SBAdmin2 theme stuff
	$('#side-menu').metisMenu();

	//Loads the correct sidebar on window load,
	//collapses the sidebar on window resize.
	// Sets the min-height of #page-wrapper to window size

    $(window).bind("load resize", function() {
        var topOffset = 50;
        var width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        var height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1) height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });
    
    var url = window.location;
    // var element = $('ul.nav a').filter(function() {
    //     return this.href == url;
    // }).addClass('active').parent().parent().addClass('in').parent();
    var element = $('ul.nav a').filter(function() {
        return this.href == url;
    }).addClass('active').parent();

    while (true) {
        if (element.is('li')) {
            element = element.parent().addClass('in').parent();
        } else {
            break;
        }
    }

	/* Admin support */
	var roles = [
		{ Name: "Pending", Id: "pending" },
		{ Name: "User", Id: "user" },
		{ Name: "Admin", Id: "admin" }
	];	
	
	$("#user-role-grid").jsGrid({
		width: "100%",
		height: "400px",

		inserting: false,
		editing: true,
		sorting: true,
		paging: true,
		autoload: true,

		controller: {
			loadData: function() {
				return $.ajax({
					url: "/ajax/getusers",
					dataType: "json"
				});
			},
			updateItem: function(item) {
				return $.ajax({
					url: "/ajax/updateuser",
					type: "PUT",
					data: item
				});
			},
			deleteItem: function(item) {
				return $.ajax({
					url: "/ajax/deleteuser",
					type: "DELETE",
					data: item
				});
			}
		},

		fields: [
			{ name: "name",  editing: "false" },
			{ name: "email", editing: "false" },
			{ name: "role", type: "select", items: roles, valueField: "Id", textField: "Name",
				/*
				editTemplate: function (value) {
					// Retrieve the DOM element (select)
					// Note: prototype.editTemplate
					var $editControl = jsGrid.fields.select.prototype.editTemplate.call(this, value);

					// Attach onchange listener !
					$editControl.change(function(){
						var selectedValue = $(this).val();
						alert(selectedValue);
					});
					return $editControl;
				}
				*/
			},
			{ type: "control" },
		]
	});

	/* intercept querybuilder nav click to prevent work loss */
 	$("#navquerybuilderhref").click(function(e) {
		e.preventDefault();
		var confirmed = true;
		if (window.location.pathname == '/') { // We were in the querybuilder and clicked the "Query Builder" nav link 
			// If no query has been loaded, but a dataset has been chosem
			if ($("#selectdataset").val() != '' && !$("#sqlquery").data('queryobj')) {
				if (!confirm("Resetting the query builder will cause unsaved changes to be lost. Proceed?")) {
					confirmed = false;	
				}
			}

			// If a query has been loaded but doesn't match current state
			if ($("#sqlquery").data('queryobj') && $("#sqlquery").data('queryobj') != buildqueryobject()) { // A query has been loaded
				if (!confirm("WARNING: Unsaved changes will be lost!")) {
					confirmed = false;
				}
			}	
		}

		if (confirmed) {
			window.location = '/';
		}
	});


	/* App initialization */
	$("#datafilters").data("filters", []); // We're going to need this later
	$("#customaggs").data("customaggs", []); // This one too

    // initialize page 
	// Load user's saved queries
	updatequerylist();
	updatelistlist();

	loadselectoptions("#selectdataset", "ajax/loadoptions/Dataset", "Choose from dropdown ...");
	$("#selectgeography").prop("disabled", true);
	$("#selectconcept").val('');
	$("#selectconcept").prop("disabled", true);
	$("#datafiltervar").prop("disabled", true);
	$("#datafilterexpr").prop("disabled", true);
	$("#datafiltervalue").prop("disabled", true);	
	$("#denominator").prop("disabled", true);
	$("#geoaggname").prop("disabled", true);
	$("#geoaggname").val('');
	$("#customaggname").prop("disabled", true);
	$("#customaggname").val('');
	$("#geoaggexpr").prop("disabled", true);
	//$("#customaggexpr").prop("disabled", true);
	$("#customaggexpr").val('');
	$("#geoaggvalstxt").prop("disabled", true);
	$("#customaggvalstxt").prop("disabled", true);
	$("#geoaggvals").prop("disabled", true);
	$("#geoaggspacer").addClass('hidden');
	$("#geoaggvaltypediv").addClass('hidden');
	$("#geoaggvaltypetxt").prop('checked', true);
	$("#customaggvals").prop("disabled", true);
	$("#sqlquery").val('');

	/* DOM bindings */
	/* Organization follows the layout of the items on the page */

	// Clear persistant children
	$("#selectdataset").change(function() {
		if ($(this).data('prevds') && $(this).val()) {  // Switched from valid DS to a different valid DS
			if ($("#sqlquery").data('queryobj')) { // If we had a query object, update it
				var qry = buildqueryobject();
				$.post('ajax/buildsql', qry).done(function(data) {
					$("#sqlquery").val(data).trigger("change");
				});
			}
		} else {

	        // Enable/disable selectgeography 
			if($(this).val()) { // A dataset is selected
	            $("#selectgeography").prop("disabled", false);
	        } else { // no selection
	            $("#selectgeography").prop("selectedIndex", 0);
	            $("#selectgeography").prop("disabled", true);
	        }

			// Reset app when dataset changes
			loadselectoptions("#selectgeography", "ajax/loadoptions/Geography/" + $("#selectdataset").val(), "Choose from dropdown ...");
			$("#selectconcept").val('');
			$("#filtervariables").val('');
			$("#selectvariables option").remove();
			$("#filterselected").val('');
			$("#selectedvariables option").remove();
			$("#datafiltervar").prop("selectedIndex", 0);
			$("#datafiltervar").prop("disabled", true);
			$("#datafilterexpr").prop("selectedIndex", 0);
			$("#datafilterexpr").prop("disabled", true);
			$("#datafiltervalue").prop("disabled", true);	
			$("#filterlist").html("");
			$("#datafilters").data("filters", []);
			$("#geoaggname").prop("disabled", true);
			$("#geoaggname").val('');
			$("#customaggname").prop("disabled", true);
			$("#customaggname").val('');
			$("#geoaggvalstxt").prop("disabled", true);
			$("#customaggvalstxt").prop("disabled", true);
			$("#geoaggvals").prop("disabled", true);
			$("#customaggvals").prop("disabled", true);
			$("#geoagglist").html("");
			$("#customagglist").html("");
			$("#customaggs").data("customaggs", []);
			$("#geoaggspacer").addClass('hidden');
			$("#geoaggvaltypediv").addClass('hidden');
			$("#geoaggvaltypetxt").prop('checked', true);
			$("#sqlquery").val('');
			$("#resultsgrid").html("");
		}

		// Save new selection for later change comparison
		$(this).data('prevds', $(this).val());
	});

	$("#selectgeography").change(function() {
		// Enable select concept if a geo is selected
        if($(this).val()) { // A geography is selected
            $("#selectconcept").prop("disabled", false);
        } else { // no selection
            $("#selectconcept").val("");
            $("#selectconcept").prop("disabled", true);
        }

		// Reset downsteam elements
		$("#selectconcept").val('');
		$("#filtervariables").val('');
		$("#selectvariables option").remove();
		$("#filterselected").val('');
		$("#selectedvariables option").remove();
		$("#datafiltervar").prop("selectedIndex", 0);
		$("#datafiltervar").prop("disabled", true);
		$("#datafilterexpr").prop("selectedIndex", 0);
		$("#datafilterexpr").prop("disabled", true);
		$("#datafiltervalue").prop("disabled", true);
		$("#filterlist").html("");
		$("#datafilters").data("filters", []);
		$("#geoaggname").prop("disabled", true);
		$("#geoaggname").val('');
		$("#customaggname").prop("disabled", true);
		$("#customaggname").val('');
		$("#geoaggvalstxt").prop("disabled", true);
		$("#customaggvalstxt").prop("disabled", true);
		$("#geoaggvals").prop("disabled", true);
		$("#customaggvals").prop("disabled", true);
		$("#geoagglist").html("");
		$("#customagglist").html("");
		$("#customaggs").data("customaggs", []);
		$("#geoaggspacer").addClass('hidden');
		$("#geoaggvaltypediv").addClass('hidden');
		$("#geoaggvaltypetxt").prop('checked', true);
		$("#sqlquery").val('');
		$("#resultsgrid").html("");
	});

    // Handle autocomplete of concept
    $("#selectconcept").autocomplete({
        source: 'ajax/loadoptions/Concept',
        minLength: 1,
		change: function() {
			this.value = this.value.toUpperCase();
			
        	var sel_ds=$("#selectdataset").val();
			var sel_geo=$("#selectgeography").val();
        	var sel_concept=$("#selectconcept").val();

        	if($(this).val()) { // a concept is selected
        	    loadselectoptions("#selectvariables", "ajax/loadvars?ds="+sel_ds+"&geo="+sel_geo+"&concept="+sel_concept);
        	}
		}
    });
	
	$("#selectconcept").on("autocompleteselect", function(event, ui){
		console.log(ui);
        var sel_ds=$("#selectdataset").val();
		var sel_geo=$("#selectgeography").val();
		loadselectoptions("#selectvariables", "ajax/loadvars?ds="+sel_ds+"&geo="+sel_geo+"&concept="+ui.item.value);
	});

	// Variables filter
    $("#filtervariables").keyup(function() {
        var filter=$(this).val().toLowerCase();
        $("#selectvariables>option").each(function(){
            var option = $(this).text().toLowerCase();
            if (option.indexOf(filter) !== -1) {
                $(this).show();
            } else {
				$(this).hide();
            }
        })
    });

    // User selects variables to include in the query 
    $("#selectvariables").dblclick(function() {
		$("#selectvariables option:selected").remove().appendTo("#selectedvariables");	

		// Update the data filter dropdown
		$("#datafiltervar option").remove();
		$("#datafiltervar").append($('<option>', {
			value: "",
			text: "Select a field ..."
		}));
		$("#selectedvariables option").clone().appendTo('#datafiltervar');
		$("#datafiltervar").prop("disabled", false);
		$("#denominator").prop("disabled", false);
		
		// Enable custom aggregations 
		$("#geoaggname").prop("disabled", false);
		$("#customaggname").prop("disabled", false);
		
		// Rebuild options lists and checkboxes
		$("#geoaggexpr option").remove();
		var denominator = $("#denominator").val();
		$("#denominator option").remove();
		$("#customaggcheckboxes").empty();

		$("#denominator").append($('<option>', {
			value: "",
			text: "None Selected"
		}));

		$("#geoaggexpr").append($('<option>', {
			value: "",
			text: "..."
		}));

		$("#selectedvariables>option").each(function(i, item){
			// Only append if the first character after the underscore is not 0-9.
			// This should include geography and reference fields while excluding table data fields.

			var colcode = item.value.substr(item.value.indexOf("_")+1);
			// Build custom geo select options
			if ($("#loadedgeo").val() == colcode.replace('NAME','') || $("#selectgeography option[value='" + colcode.replace('NAME','') +"']").length > 0) { // If the column is "KNOWNGEO" or "KNOWNGEONAME" 
				// Add the reference column to the available aggregations dropdown
				$("#geoaggexpr").append($('<option>', {
					value: item.value,
					text: item.text
				}));
			} else { // Value columns
				// Add a checkbox for the data column
				$("#customaggcheckboxes").append(
					'<input style="max-width: 40%;" type="checkbox" id="cb-"' + item.value + ' name="' + item.value + '" value="' + item.text + '"> ' +
					item.text + '<br />' + "\n"
				);

				// Add a select option to the denominator drop-down
				$("#denominator").append($('<option>', {
					value: item.value,
					text: item.text
				}));
			}
		});
		$("#denominator").val(denominator);

		// Update query object
		var qry = buildqueryobject();
		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	});

	// Fire selectvars dblclick with arrow
	$("#selectarrow").click(function() {
		$("#selectvariables").trigger('dblclick');
	});

	// Selected variables filter	
    $("#filterselected").keyup(function() {
        var filter=$(this).val().toLowerCase();
        $("#selectedvariables>option").each(function(){
            var option = $(this).text().toLowerCase();
            if (option.indexOf(filter) !== -1) {
                $(this).show();
            } else {
				$(this).hide();
            }
        })
    });

	// User de-selects variables to remove them from the query
	$("#selectedvariables").dblclick(function() {
		$("#selectedvariables option:selected").each(function() {
			var tbl=$("#selectconcept").val();
			var optcol=this.value;
			var opttbl=optcol.substr(0, this.value.indexOf("_"));

			if (tbl == opttbl) {
				// I have no idea why 'this' doesn't work here
				$('#selectedvariables option[value="'+optcol+'"]').remove().appendTo("#selectvariables");
			} else {
				this.remove();
			}
		});

		$("#selectedvariables option:selected").remove().appendTo("#selectvariables");

		// Rebuild custom aggregation options list
		$("#geoaggexpr option").remove();
		denominator = $("#denominator").val();
		$("#denominator option").remove();
		$("#customaggcheckboxes").empty();

		$("#denominator").append($('<option>', {
			value: "",
			text: "None Selected"
		}));

		$("#geoaggexpr").append($('<option>', {
			value: "",
			text: "..."
		}));

		$("#selectedvariables>option").each(function(i, item){
			// Only append if the first character after the underscore is not 0-9.
			// This should include geography and reference fields while excluding table data fields.
			var colcode = item.value.substr(item.value.indexOf("_")+1);
			if ($("#selectgeography option[value='" + colcode.replace('NAME','') +"']").length > 0) {
				// Add the reference column to the available aggregations dropdown
				$("#geoaggexpr").append($('<option>', {
					value: item.value,
					text: item.text
				}));
			} else {
				// Add a checkbox for the data column
				$("#customaggcheckboxes").append(
					'<input type="checkbox" id="cb-"' + item.value + ' name="' + item.value + '" value="' + item.text + '"> ' +
					item.text + '<br />' + "\n"
				);

				// Add a select option to the denominator drop-down
				$("#denominator").append($('<option>', {
					value: item.value,
					text: item.text
				}));
			}
			$("#denominator").val(denominator);
		});

		// Sort to return to right spot
		$("#selectvariables").html($("#selectvariables option").sort(function(a, b) {
			// options with these labels will rise to the top of the sort
			// in the order given
			var overrides = [
				'US', 'US NAME', 
				'REGION', 'REGION NAME',
				'DIVISION', 'DIVISION NAME',
				'STATE', 'STATE NAME', 
				'COUNTY', 'COUNTY NAME',
				'CSA', 'CSA NAME',
				'CDCURR', 'CDCURR NAME',
				'SDUNI', 'SDUNI NAME',
				'ZCTA', 'ZCTA NAME',
				'TRACT', 'TRACT NAME',
				'BLKGRP', 'BLKGRP NAME'
			];

			for (var i = 0; i < overrides.length; i++) {
				if (a.text == overrides[i]) return -1;
				if (b.text == overrides[i]) return 1; 
			}
	
			return (a.value < b.value) ? -1 : (a.value > b.value) ? 1 : 0;
		}));

		// Update the data filter dropdown
		$("#datafiltervar option").remove();
		$("#datafiltervar").append($('<option>', {
			value: "",
			text: "Select a field ..."
		}));
		$("#selectedvariables option").clone().appendTo('#datafiltervar');
		if ($("#datafiltervar option").length === 1) {
			$("#datafiltervar").prop("disabled", true);
		}

		// Loop through and remove dependant filters that are already set
		$("#datafilteris option").each(function() {
			if ($('#selectedvariables option[value="' + this.value + '"').length == 0) {
				$("#" + md5(this.value)).remove();
				this.remove();
			}
		});

		// Shut off the denominator field if there are no options
		if ($("#selectedvariables option").length == 0) {
			$("#denominator").prop("disabled", true);
		}


		// Shut off the Custom Geo fields if there are no Selected Vars left
		if ($("#selectedvariables option").length == 0) {
			$("#geoaggname").prop("disabled", true);
			$("#customaggname").prop("disabled", true);
			$("#customaggmember").prop("disabled", true);

			// Maybe this should trigger only with GEO change??
			$("#customaggs").data("customaggs", []);
			$("#customagglist li").remove();
			$("#geoagglist li").remove();
		}

		// Update query object
		var qry = buildqueryobject();
		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	}); // end $("selectedvars").dblClick()

	// Fire selectedvars dblclick with arrow
	$("#selectedarrow").click(function() {
		$("#selectedvariables").trigger('dblclick');
	});

	$("#datafiltervar").change(function() {
		if ($("#datafiltervar option:selected").val() !== "") {
			$("#datafilterexpr").prop("disabled", false);
			$("#datafiltervalue").prop("disabled", false);	
			if ($("#datafiltervalue").val() != "") {
				$("#adddatafilter").removeClass("disabled");		
			}
		} else {
			$("#datafilterexpr").prop("disabled", true);	
			$("#datafiltervalue").prop("disabled", true);
			$("#adddatafilter").addClass("disabled");
		}
	});


	$("#datafilterexpr").change( function() {
		// Transmogrify into a text or textarea
		// depending on the value of expression
		var currentval=$("#datafiltervalue").val();
		var textbox='<input id="datafiltervalue" class="form-control" placeholder="Value ...">';
		var textarea='<textarea style="vertical-align: top;" id="datafiltervalue" class="form-control" placeholder="One value per line ...">';
		var select='<select id="datafiltervalue" class="form-control"></select>';

		if ($("#datafilterexpr option:selected").val() == 'in') {
			$("#datafiltervalue").replaceWith(textarea);
		} 

		if ($("#datafilterexpr option:selected").val() == 'is') {
			$("#datafiltervalue").replaceWith(textbox);
		} 

		if ($("#datafilterexpr option:selected").val() == 'in list') {
			$("#datafiltervalue").replaceWith(select)

			$("#datafiltervalue").append($('<option>', {
				value: "",
				text: "Select a list ..." 
			}));
	
			$.post('ajax/getlistlist').done(function(data) {
				data.forEach(function(item) {
					$("#datafiltervalue").append($('<option>', {
						value: item.id,
						text: item.name
					}));
				});	
			});
		}
		
		$("#datafiltervalue").val(currentval);

		// We have to recreate this binding since we recreated the dom element.
		// The one below (outside the closure) also has to be there.	
		$("#datafiltervalue").bind('input propertychange', function() {
			if ($("#datafiltervalue").val() !== "") {
				$("#adddatafilter").removeClass("disabled");
			} else {
				$("#adddatafilter").addClass("disabled");
			}
		});
	});

	$("#datafiltervalue").bind('input propertychange', function() {
		if ($("#datafiltervalue").val() !== "") {
			$("#adddatafilter").removeClass("disabled");
		} else {
			$("#adddatafilter").addClass("disabled");
		}
	});

	$("#adddatafilter").click(function() {
		if ($(this).hasClass('disabled')) { return; } // ignore stray events

		var filterid = md5(guid()); // Collision proof enough 

		var filter = {
			id: filterid,
			variable: $("#datafiltervar option:selected").val(),
			expr: $("#datafilterexpr option:selected").val(),
			value: $("#datafiltervalue").val(), 
			value_label: $("#datafiltervalue").val(), 
		};

		if (filter.expr == "in list") {
			filter.value_label = $("#datafiltervalue option:selected").text();
		}

		if ($("#datafiltervalue").is("textarea")) { 
			var lines = [];
			var instring = "";
			
			lines = filter.value.split(/\n/);
			for (var i = 0; i < lines.length; i++) {
				if ($.trim(lines[i]) != "") {
					instring += "'" + $.trim(lines[i]).replace(/\'/g, "''") + "'";	
					instring += ", ";
				}
			}
			if (instring != "") {
				// Remove extraneous comma-space
				instring = instring.substring(0, instring.length -2);
				instring = "(" + instring + ")";
			}

			// Reassign filter value
			filter.value = instring;
		}
	
		$("#datafilters").data("filters").push(filter);

		// Update the visible list
		$("#filterlist").append(
			'<li id="' + filter.id + 
			'" style="padding:5px;" class="list-group-item clearfix">' + 
			filter.variable + ' <b>' + filter.expr + '</b> ' + filter.value_label + 
			'<span class="pull-right"><button class="btn btn-sm btn-danger datafilterdel"><i class="fa fa-trash"></i></button></span>' + 
			'</li>'
		); 

		// Clear the addfilter form 
		$("#datafiltervar").prop("selectedIndex", 0);
		$("#datafilterexpr option[value='is']").prop("selected", true);
		$("#datafilterexpr").prop("disabled", true);
		$("#datafilterexpr").trigger("change");
		$("#datafiltervalue").val("");
		$("#datafiltervalue").prop("disabled", true);
		$("#adddatafilter").addClass("disabled");

		// Update query object
		var qry = buildqueryobject();
		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	});
	
	// Datafilter delete buttons
	// We need a delegated handler on something that exists in the DOM.  The buttons 
	// themselves are added dynamically so outside the reach of $.
	$("#filterlist").on('click', '.datafilterdel', function() {
		var listid=$(this).closest('li').attr('id');
		
		// Remove from filters data store
		$("#datafilters").data("filters", $.grep($("#datafilters").data("filters"), function(e) {
			return e.id != listid;
		}));
	
		// Remove the list item
		$(this).closest('li').remove();

		// Clear the addfilter form
		$("#datafiltervar").prop("selectedIndex", 0);
		$("#datafilterexpr option[value='is']").prop("selected", true);
		$("#datafilterexpr").prop("disabled", true);
		$("#datafilterexpr").trigger("change");
		$("#datafiltervalue").val("");
		$("#datafiltervalue").prop("disabled", true);
		$("#adddatafilter").addClass("disabled");
		
		// Update query object
		var qry = buildqueryobject();
		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	});

	$("#denominator").change(function() {
		// Update query object
		var qry = buildqueryobject();
		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	});
	
	// Geo Aggregations 
	$("#geoaggname").bind('input propertychange', function() {
		if ($("#geoaggname").val() !== "") {
			$("#geoaggexpr").prop("disabled", false);
			if ($("#geoaggexpr").val() != '') {
				$("#geoaggvals").prop("disabled", false);
				$("#geoaggvalstxt").prop("disabled", false);
			}
			if ($("#geoaggvals").val() !== "" && $("#geoaggexpr").val() != '') {
				$("#addgeoagg").removeClass("disabled");$("#addgeoagg").removeClass("disabled");
			}
		} else {
			$("#geoaggexpr").prop("disabled", true);
			$("#geoaggvals").prop("disabled", true);
			$("#geoaggvalstxt").prop("disabled", true);
			$("#addgeoagg").addClass("disabled");
		}
	});

	$("#geoaggexpr").change(function() {
		if ($("#geoaggexpr").val() !== '') {
			$("#geoaggvalstxt").prop("disabled", false);
			$("#geoaggvals").prop("disabled", false);
			if ($("#geoaggname").val() !== "" && $("#geoaggvals").val() !== "") {
				$("#addgeoagg").removeClass("disabled");
			}
			$("#geoaggspacer").removeClass('hidden');
			$("#geoaggvaltypediv").removeClass('hidden');
		} else {
			$("#geoaggvals").prop("disabled", true);
			$("#geoaggvalstxt").prop("disabled", true);
			$("#addgeoagg").addClass("disabled");
			$("#geoaggspacer").addClass('hidden');
			$("#geoaggvaltypediv").addClass('hidden');
		}
	});

/*
		if ($("#datafilterexpr option:selected").val() == 'in list') {
			$("#datafiltervalue").replaceWith(select)

			$("#datafiltervalue").append($('<option>', {
				value: "",
				text: "Select a list ..." 
			}));
	
			$.post('ajax/getlistlist').done(function(data) {
				data.forEach(function(item) {
					$("#datafiltervalue").append($('<option>', {
						value: item.id,
						text: item.name
					}));
				});	
			});
		}
*/
	
	$("#geoaggvalstxt").focus(function() {
		console.log($('input[name=geoaggvaltype]:checked').val());
		var select='<select id="geoaggvals" class="form-control"></select>';
		if ($('input[name=geoaggvaltype]:checked').val() == 'list') { // List is selected 
			$("#geoaggvals").replaceWith(select)
			$("#geoaggvals").append($('<option>', {
				value: "",
				text: "Select a list ..." 
			}));
	
			$.post('ajax/getlistlist').done(function(data) {
				data.forEach(function(item) {
					$("#geoaggvals").append($('<option>', {
						value: item.id,
						text: item.name
					}));
				});	
			});
		}
		$("#geoaggvalstxt").addClass('hidden');
		$("#geoaggvals").removeClass('hidden');
		$("#geoaggvals").focus();
	});	

	$("#geoaggvals").blur(function() {
		$("#geoaggvalstxt").val($("#geoaggvals").val().split(/\n/)[0]+" [...]");
		$("#geoaggvals").addClass('hidden');
		$("#geoaggvalstxt").removeClass('hidden');
	});

	$("#geoaggvals").bind('input propertychange', function() {
		if ($("#geoaggvals").val() !== "" && $("#geoaggexpr").val() != '') {
			$("#addgeoagg").removeClass("disabled");
		} else {
			$("#addgeoagg").addClass("disabled");
		}
	});

	$("#geoaggvals").blur(function() {
		$("#geoaggvalstxt").val($("#geoaggvals").val().split(/\n/)[0]+" [...]");
		$("#geoaggvals").addClass('hidden');
		$("#geoaggvalstxt").removeClass('hidden');
	});

	// Column aggregration
	$("#customaggname").bind('input propertychange', function() {
		if ($("#customaggname").val() !== "") {
			$("#customaggexpr").val('COLUMNS');
			$("#customaggvalstxt").prop("disabled", false);
			$("#customaggvals").prop("disabled", false);	
			if ($("#customaggcheckboxes :checkbox:checked").length > 1) {
				$("#addcustomagg").removeClass("disabled");
			}
		} else {
			$("#customaggexpr").val('');
			$("#customaggvalstxt").prop("disabled", true);
			$("#customaggvals").prop("disabled", true);
			$("#addcustomagg").addClass("disabled");
		}
		$("#customaggexpr").change();
	});

	// Toggle checkbox area for COLUMN aggregations
	$("#customaggexpr").change(function() {
		if ($("#customaggexpr").val() == 'COLUMNS') {
			$("#customaggvalstxt").addClass('hidden');
			$("#customaggcheckboxes").removeClass('hidden');
		} else {
			$("#customaggcheckboxes").addClass('hidden');
			$("#customaggvalstxt").removeClass('hidden');
		}
	});

	// Toggle hide of textarea for cleaner formatting
	$("#customaggvalstxt").focus(function() {
		$("#customaggvalstxt").addClass('hidden');
		$("#customaggvals").removeClass('hidden');
		$("#customaggvals").focus();
	});	

	$("#customaggvals").blur(function() {
		$("#customaggvalstxt").val($("#customaggvals").val().split(/\n/)[0]+" [...]");
		$("#customaggvals").addClass('hidden');
		$("#customaggvalstxt").removeClass('hidden');
	});

	$("#customaggvals").change( function() {
		if ($("#customaggvals").val() !== "") {
			$("#addcustomagg").removeClass("disabled");
		} else {
			$("#addcustomagg").addClass("disabled");
		}
	});

	$("#customaggcheckboxes").click(function() {
		if ($("#customaggcheckboxes :checkbox:checked").length > 1) { // At least two columns are required for aggregation
			$("#addcustomagg").removeClass("disabled");
		} else {
			$("#addcustomagg").addClass("disabled");	
		}
	});

	// Combined add handler
	$("#addgeoagg, #addcustomagg").click(function() {
	/*
		var filter = {
			id: filterid,
			variable: $("#datafiltervar option:selected").val(),
			expr: $("#datafilterexpr option:selected").val(),
			value: $("#datafiltervalue").val(), 
			value_label: $("#datafiltervalue").val(), 
		};
	*/
		if ($(this).hasClass('disabled')) { return; } // ignore stray events

		var customaggid = md5(guid()); // Collision proof enough 
		
		// Set type based on options select
		var customaggtype = "row";
		if ($("#customaggexpr").val() == 'COLUMNS') {
			customaggtype = "col";	
		}

		if (customaggtype == "row") {
			var customagg = {
				id: customaggid,
				type: customaggtype,	
				key: $("#geoaggexpr option:selected").val(),
				keyname: $("#geoaggexpr option:selected").text(),
				alias: $("#geoaggname").val(),
				vals: $("#geoaggvals").val(), 
				cols: [], 
				collist: ""
			};
		} else {
			var customagg = {
				id: customaggid,
				type: customaggtype,	
				key: $("#customaggexpr").val(),
				keyname: $("#customaggexpr").val(),
				alias: $("#customaggname").val(),
				vals: $("#customaggvals").val(), 
				cols: [], 
				collist: ""
			};
		}

		// Transform into SQL ready list
		var lines = [];
		var instring = "";
			
		lines = customagg.vals.split(/\n/);
		for (var i = 0; i < lines.length; i++) {
			if ($.trim(lines[i]) != "") {
				instring += "'" + $.trim(lines[i]).replace(/\'/g, "''") + "'";	
				instring += ", ";
			}
		}
		
		if (instring != "") {
			// Remove extraneous comma-space
			instring = instring.substring(0, instring.length -2);
			instring = "(" + instring + ")";
		}

		// Reassign filter value
		customagg.vals = instring;

		// Create columns list
		var collist = "";
		$("#customaggcheckboxes :checkbox:checked").each(function() {
			customagg.cols.push({column: this.name, label: this.value});
			collist += this.value + ', '
		});
		if (collist != "") {
			collist = collist.substr(0, collist.length -2);
		}
		customagg.collist = collist;


		$("#customaggs").data("customaggs").push(customagg);

		// Update the visible list
		if (customagg.type == 'row') {
			$("#geoagglist").append(
				'<li id="' + customagg.id + 
				'" style="padding:5px;" class="list-group-item clearfix">' + 
				'<b>' + customagg.alias + '</b>: ' + customagg.keyname + ' <b>in</b> ' + customagg.vals +  
				'<span class="pull-right"><button class="btn btn-sm btn-danger customaggdel"><i class="fa fa-trash"></i></button></span>' + 
				'</li>'
			);
		} else {
			$("#customagglist").append(
				'<li id="' + customagg.id + 
				'" style="padding:5px;" class="list-group-item clearfix">' + 
				'<b>' + customagg.alias + '</b>: ' + customagg.keyname + ' <b>in</b> ' + customagg.collist + 
				'<span class="pull-right"><button class="btn btn-sm btn-danger customaggdel"><i class="fa fa-trash"></i></button></span>' + 
				'</li>'
			); 
		}

		// Reset form state
		$("#geoaggname").val("");
		$("#customaggname").val("");
		$("#geoaggexpr").val("");
		$("#customaggexpr").val("");
		$("#geoaggvalstxt").val("");
		$("#customaggvalstxt").val("");
		$("#geoaggvals").val("");
		$("#customaggvals").val("");
		$("#customaggcheckboxes").addClass('hidden');
		$("#customaggcheckboxes :checkbox:checked").each(function() {
			this.checked = false;
		});
		$("#geoaggvalstxt").removeClass('hidden');
		$("#customaggvalstxt").removeClass('hidden');
		$("#geoaggexpr").prop("disabled", true);
		$("#customaggexpr").prop("disabled", true);
		$("#geoaggvalstxt").prop("disabled", true);
		$("#customaggvalstxt").prop("disabled", true);
		$("#geoaggvals").prop("disabled", true);
		$("#customaggvals").prop("disabled", true);
		$("#addgeoagg").addClass("disabled");
		$("#addcustomagg").addClass("disabled");

		// Update query object
		var qry = buildqueryobject();

		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	});
	
	// Custom Agg delete buttons
	// We need a delegated handler on something that exists in the DOM.  The buttons 
	// themselves are added dynamically so outside the reach of $.
	$("#geoagglist, #customagglist").on('click', '.customaggdel', function() {
		var listid=$(this).closest('li').attr('id');
		
		// Remove from customaggs data store
		$("#customaggs").data("customaggs", $.grep($("#customaggs").data("customaggs"), function(e) {
			return e.id != listid;
		}));
	
		// Remove the list item
		$(this).closest('li').remove();

		// Update query object
		var qry = buildqueryobject();
		$.post('ajax/buildsql', qry).done(function(data) {
			$("#sqlquery").val(data).trigger("change");
		});
	});

	// Handle the run button.
	$("#sqlquery").change(function() {
		if ($(this).val() != '') {
			$("#runbtn").removeClass("disabled");
			$("#exportjson").removeClass("disabled");
			$("#exportcsv").removeClass("disabled");
			$("#exportxlsx").removeClass("disabled");
			$("#savequery").removeClass("disabled");
		} else {
			$("#runbtn").addClass("disabled");
			$("#exportjson").addClass("disabled");
			$("#exportcsv").addClass("disabled");
			$("#exportxlsx").addClass("disabled");
			$("#savequery").addClass("disabled");
			$("#resultsgrid").html("");
		}
	});

	$("#runbtn").click(function() {
		// Clear old grid
		$("#resultsgrid").html("");

		var qry = buildqueryobject();

		$.post('ajax/runsql', qry).done(function(res) {
			var griddata = [];
			var gridfields = [];

			// Fix property names with dots (JSGrid limitation)
			JSON.parse(res).forEach(function (arrayItem) {
				var keynames = Object.keys(arrayItem);
				var item = {};
				for (var i in keynames) {
					newkey=keynames[i].replace(/\./g,"::");
					item[newkey]=arrayItem[keynames[i]];
				}
				griddata.push(item);
			});

			if (Object.keys(griddata).length !== 0) {

				gridfields = buildfields(griddata);
	
				$("#resultsgrid").jsGrid({
					width: "100%",
					height: "400px",
					
					sorting: true,
					paging: true,
					data: griddata,
					fields: gridfields
				});
			}
			
			if (griddata.length >= 10000) {
				$("#resultsgridmessage").html('<div class="alert alert-warning" role="alert"><strong>Warning:</strong> Preview grid data truncated to first 10000 rows</div>');
			} else {
				$("#resultsgridmessage").html('');
			}
		});
	});

	$("#exportjson").click(function() {
		var qry = JSON.parse(buildqueryobject());
		qry['format']='json';
		requestexport('ajax/runsql', qry);
	});

	$("#exportcsv").click(function() {
		var qry = JSON.parse(buildqueryobject());
		qry['format']='csv';
		requestexport('ajax/runsql', qry);
	});

	$("#savequery").click(function() {
		if (!$(this).hasClass("disabled")) {
			$("#savequerymodal").modal();
			if ($("#savequerydesc").val().length > 0) {
				$("#savequerybutton").removeClass("disabled");
			}
		}
	});

	$("#savequerymodal").on('hidden.bs.modal', function() {
		// Clear the last status message
		$("#savequeryresult").html("");	
		$("#savequeryform").trigger("reset");
	});

	$("#savequeryname").change(function() {
		if (this.value.length > 0) {
			$("#savequerydesc").prop("disabled", false);
		} else {
			$("#savequerydesc").prop("disabled", true);
		}
	});

	$("#savequerydesc").change(function() {
		if (this.value.length > 0) {
			$("#savequerypublic").prop("disabled", false);
			$("#savequerybutton").removeClass("disabled");	
		} else {
			$("#savequerypublic").prop("disabled", true);
			$("#savequerybutton").addClass("disabled");
		}
	});

	$("#savequerybutton").click(function() {
		var query = {};

		if ($(this).hasClass("disabled") == false) {

			query.id = "";	
			query.name = $("#savequeryname").val();
			query.desc = $("#savequerydesc").val();
			query.ispublic = $("#savequerypublic").val(); 

			query.dataset = $("#selectdataset option:selected").val();
			query.geography = $("#selectgeography option:selected").val();
			query.concept = $("#selectconcept").val(); 
			query.varsavail = [];
			$("#selectvariables option").each(function() {
				query.varsavail.push({"value":this.value,"text":this.text});
			});
			query.varsselected = [];
			$("#selectedvariables option").each(function() {
				query.varsselected.push({"value":this.value,"text":this.text});
			});
			query.denominator = $("#denominator").val();
			query.datafilters = $("#datafilters").data("filters");
			query.customaggs = $("#customaggs").data("customaggs");

			// Do the save and load the result
			saveobject("query", query, '#savequeryresult');	
			
			// Caputer the current state for change comparison
			$("#sqlquery").data('queryobj', buildqueryobject());
		}
	});

	// Handle loading saved query from nav list
	$("#navmyqueries").on('click', '.savedquery', function() {
		var queryid = $(this).closest('li').attr('id').split('-')[1];

		// Load up the query object
		$.post('ajax/loadobject', {"objname": "query", "objid": queryid}).done(function(data) {
			var queryobj = JSON.parse(data);

			$("#queryname").html(' ('+ queryobj.name +')');	
			$('#selectdataset option[value="'+queryobj.dataset+'"').prop('selected', true);
			
			// Save new selection for later change comparison
			$('#selectdataset').data('prevds', queryobj.dataset);

			loadselectoptions("#selectgeography", "ajax/loadoptions/Geography/" + $("#selectdataset").val(), 
				"Choose from dropdown ...", queryobj.geography);
			//$('#selectgeography option[value="'+queryobj.geography+'"').prop('selected', true);
			$('#loadedgeo').val(queryobj.geography);
			$('#selectgeography').prop('disabled', false);


			$("#selectconcept").val(queryobj.concept);
			$("#selectconcept").prop('disabled', false);
			$("#selectvariables option").remove();
			queryobj.varsavail.forEach(function(item) {
				$("#selectvariables").append($('<option>', item));
			});
			$("#selectedvariables option").remove();
			queryobj.varsselected.forEach(function(item) {
				$("#selectedvariables").append($('<option>', item));
			});
			
			$("#datafilters").data("filters", queryobj.datafilters);
			$("#filterlist").html("");
			queryobj.datafilters.forEach(function(filter) {
				$("#filterlist").append(
					'<li id="' + filter.id + 
					'" style="padding:5px;" class="list-group-item clearfix">' + 
					filter.variable + ' <b>' + filter.expr + '</b> ' + filter.value_label + 
					'<span class="pull-right"><button class="btn btn-sm btn-danger datafilterdel"><i class="fa fa-trash"></i></button></span>' + 
					'</li>'
				); 
			});
			$("#customaggs").data("customaggs", queryobj.customaggs);
			$("#geoagglist").html("");
			$("#customagglist").html("");
			queryobj.customaggs.forEach(function(customagg) {
				// Update the visible list
				if (customagg.type == 'row') {
					$("#geoagglist").append(
						'<li id="' + customagg.id + 
						'" style="padding:5px;" class="list-group-item clearfix">' + 
						'<b>' + customagg.alias + '</b>: ' + customagg.keyname + ' <b>in</b> ' + customagg.vals +  
						'<span class="pull-right"><button class="btn btn-sm btn-danger customaggdel"><i class="fa fa-trash"></i></button></span>' + 
						'</li>'
					);
				} else {
					$("#customagglist").append(
						'<li id="' + customagg.id + 
						'" style="padding:5px;" class="list-group-item clearfix">' + 
						'<b>' + customagg.alias + '</b>: ' + customagg.keyname + ' <b>in</b> ' + customagg.collist + 
						'<span class="pull-right"><button class="btn btn-sm btn-danger customaggdel"><i class="fa fa-trash"></i></button></span>' + 
						'</li>'
					); 
				}
			});
			$("#selectvariables").trigger('dblclick');
			$("#denominator").val(queryobj.denominator);


			// save freshly loaded for change comparison
			$("#sqlquery").data('queryobj', buildqueryobject() );
		});
	});

	// Handle deletes directly from the query nav list
	$("#navmyqueries").on('click', '.delsavedquery', function() {
		var queryid = $(this).closest('li').attr('id').split('-')[1];
		var listitem = $(this).closest('li');

		$.post('ajax/delobject', {"objname": "query", "objid": queryid}).done(function(data) {
			listitem.remove();
		});
	});

	// Open the list editor in add mode
	$("#addlist").click(function() {
		$("#savelisttitle").text("Add List");
		$("#savelistmodal").modal();
	});
	
	// Open the list editor in Edit mode 
	$("#navmylists").on('click', '.savedlist', function() {
		var listid = $(this).closest('li').attr('id').split('-')[1];

		// Load up the existing list
		$.post('ajax/loadobject', {"objname": "list", "objid": listid}).done(function(data) {
			var listobj = JSON.parse(data);

			$("#savelistname").val(listobj.list.name);
			$("#savelistdesc").val(listobj.list.description).attr('disabled', false);
			if (listobj.list.public == 1) {
				$("#savelistpublic").prop('checked', true).attr('disabled', false);
			} else {
				$("#savelistpublic").prop('checked', false).attr('disabled', false);
			}
			$("#savelistitems").val(listobj.items).attr('disabled', false);
			$("#savelistbutton").removeClass('disabled');
		});
		
		$("#savelisttitle").text("Edit List");
		$("#savelistmodal").modal();
	});
	

	$("#savelistmodal").on('hidden.bs.modal', function() {
		// Clear the last status message
		$("#savelistresult").html("");	
		$("#savelistform").trigger("reset");
	});

	$("#savelistname").change(function() {
		if (this.value.length > 0) {
			$("#savelistdesc").prop("disabled", false);
		} else {
			$("#savelistdesc").prop("disabled", true);
		}
	});

	$("#savelistdesc").change(function() {
		if (this.value.length > 0) {
			$("#savelistitems").prop("disabled", false);
		} else {
			$("#savelistitems").prop("disabled", true);
		}
	});

	$("#savelistdesc").change(function() {
		if (this.value.length > 0) {
			$("#savelistpublic").prop("disabled", false);
			$("#savelistbutton").removeClass("disabled");	
		} else {
			$("#savelistpublic").prop("disabled", true);
			$("#savelistbutton").addClass("disabled");
		}
	});

	$("#savelistbutton").click(function() {
		var list = {};

		if ($(this).hasClass("disabled") == false) {

			list.id = "";	
			list.name = $("#savelistname").val();
			list.desc = $("#savelistdesc").val();
			list.items = $("#savelistitems").val().split('\n');
			list.ispublic = $("#savelistpublic").val(); 

			// Do the save and load the result
			saveobject("list", list, '#savelistresult');	
		}
	});

	// Handle deletes directly from the query nav list
	$("#navmylists").on('click', '.delsavedlist', function() {
		var listid = $(this).closest('li').attr('id').split('-')[1];
		var listitem = $(this).closest('li');

		$.post('ajax/delobject', {"objname": "list", "objid": listid}).done(function(data) {
			listitem.remove();
		});
	});
});
