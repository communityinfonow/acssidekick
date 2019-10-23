<?php

use App\ACSQuery;
use App\Concept;
use App\Dataset;
use App\ItemList;
use App\ItemListItem;
use App\Query;
use App\Variable;
use App\User;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

// Normal UI routes
Route::get('/', function ()  {
	if (Auth::user()->hasRole('pending')) {
		return view('auth/pending');
	} else {
    	return view('querybuilder');
	}
})->middleware('auth');

Route::get('/admin', function() {
	if(!Auth::user()->hasRole('admin')) abort(404); // Bail unless admin
	return view('admin');
})->middleware('auth');

// Email verification support route
Route::get('register/verify/{token}','Auth\RegisterController@verify');

// Ajax routes
Route::get('/ajax/getusers', function(Request $request) {
    if(!Auth::user()->hasRole('admin')) abort(404); // Bail unless admin
    return User::all();
})->middleware('auth');

Route::put('/ajax/updateuser', function(Request $request) {
    if(!Auth::user()->hasRole('admin')) abort(404); // Bail unless admin
    User::where('id', $request->input('id'))->update(['role' => $request->input('role')]);
})->middleware('auth');

Route::delete('/ajax/deleteuser', function(Request $request) {
    if(!Auth::user()->hasRole('admin')) abort(404); // Bail unless admin
    User::destroy($request->input('id'));
})->middleware('auth');

Route::post('/ajax/buildsql', function(Request $request) {
	$qryobj = $request->json()->all();
	$sql = new ACSQuery();
	return $sql->generateSQL($qryobj);
})->middleware('auth');

Route::post('/ajax/delobject', function(Request $request) {
	if ($request->input('objname') == 'query') {
		if ($q = Query::where('id', $request->input('objid'))->where('owner_id', Auth::id())->first()) {
			$q->delete();
			return "";
		}	
	}

	if ($request->input('objname') == 'list') {
		if ($l = ItemList::where('id', $request->input('objid'))->where('owner_id', Auth::id())->first()) {
			ItemListItem::where('list_id', $l->id)->delete();
			$l->delete();
			return "";
		}
	}
})->middleware('auth');

Route::post('ajax/getlistlist', function(Request $request) {
	$list=array();
	$itemlists = ItemList::where('owner_id', Auth::id())->get();
	if (!$itemlists->isEmpty()) {
		foreach ($itemlists as $itemlist) {
			$list[]=array(
				'id' => $itemlist->id,
				'name' => $itemlist->name,
				'desc' => $itemlist->description
			);
		}
	}
	return $list;
})->middleware('auth');


Route::post('/ajax/getquerylist', function(Request $request) {
	$list=array();
	$queries = Query::where('owner_id', Auth::id())->get();
	if (!$queries->isEmpty()) {
		foreach ($queries as $query) {
			$list[]=array(
				'id' => $query->id,
				'name' => $query->name,
				'desc' => $query->description
			);
		}
	}
	return $list;
})->middleware('auth');

Route::post('/ajax/loadobject', function(Request $request) {
	if ($request->input('objname') == 'query') {
		// Load query object
		if ($q = Query::where('id', $request->input('objid'))->where('owner_id', Auth::id())->first()) {
			return $q->object;
		}	
	}
	if ($request->input('objname') == 'list') {
		// Load list object
		if ($l = ItemList::where('id', $request->input('objid'))->where('owner_id', Auth::id())->first()) {
			$i = ItemListItem::where('list_id', $l->id)->get();
			$items = '';
			foreach($i as $li) {
				$items .= $li->item ."\n";
			}

			return json_encode(array(
				'list' => $l,
				'items' => trim($items)
			));
		}
	}
})->middleware('auth');

Route::get('/ajax/loadoptions/{object}/{param1?}', function($object, $param1 = null) {
	// Sanity check
	$allowed = array('Dataset', 'Geography', 'Concept', 'Variable');
	if (!in_array($object, $allowed)) {
		return "[]"; 
	}

	$input=Input::all();
	$model = "App\\".$object;

	if (isset($input['term'])) {
		$collection = $model::where('label','like','%'.$input['term'].'%')->get();
	} elseif ($object == 'Geography' && $param1 != NULL) {
		$ds = Dataset::where('code',$param1)->first();
		$collection = $model::where('dataset_id', $ds->id)->orderBy('id')->get();
	} elseif ($object == 'Dataset') {
		$collection = $model::orderBy('id','desc')->get();
	} elseif ($object == 'Concept') {
		$collection = $model::orderBy('code','desc')->get();
	} else {
		$collection = $model::all();
	}

	foreach($collection as $item) {
		if ($object == 'Geography') {
			$return[]=array(
				'label' => $item->code,
				'value' => $item->code
			);
		} else if ($object == 'Concept') {
			$return[]=array(
				'label' => "(".$item->code.") ".$item->label,
				'value' => $item->code
			);
		} else {
			$return[]=array(
				'label' => $item->label,
				'value' => $item->code
			);
		}
	}

	if (isset($return)) {
		return $return;
	} else {
		return array();
	}
})->middleware('auth');

Route::get('/ajax/loadvars', function() {
	$input=Input::all();
	if (!$input['ds'] || !$input['concept'] || !$input['geo']) {
		return "[]";
	} 

	$dataset = Dataset::where('code', $input['ds'])->first();
	$concept = Concept::where('dataset_id', $dataset->id)->where('code', $input['concept'])->first();

	if (isset($input['term'])) {
		if ($concept) {
			$collection = Variable::where('concept_id', $concept->id)->
			where('label','like','%'.$input['term'].'%')->
			where('typecode', 'like', '%E')->
			get();
		}
	} else {
		if ($concept) {
			$collection = Variable::where('concept_id', $concept->id)-> 
			where('typecode', 'like', '%E')-> 
			get(); 
		}
	}

	// Bail out if no collection
	if (!isset($collection)) {
		return array();
	}
	
	// Add in geography vars
	$return[]=array(
		'label' => $input['geo'],
		'value' => $input['concept'].'_'.$input['geo']
	);

	$return[]=array(
		'label' => $input['geo'].' NAME',
		'value' => $input['concept'].'_'.$input['geo'].'NAME'
	);

	// Add any upstream GEO dependencies
	if ($parents = config('datasets.'.$input['ds'].'.geo_parents.'.$input['geo'])) {
		foreach($parents as $parent) {
			$return[]=array(
				'label' => $parent,
				'value' => $input['concept'].'_'.$parent
			);
		}
	}

	foreach($collection as $item) {
		$return[]=array(
			'label' => '('.strtok($item->code,'_').') '.$item->label,
			'value' => $item->code
		);
	}

	if (isset($return)) {
		return $return;
	} else {
		return array();
	}

})->middleware('auth');

Route::post('/ajax/runsql', function(Request $request) {
	$qryobj = $request->json()->all();
	$sql = new ACSQuery();
	if (!isset($qryobj['format'])) {
		// Limit # of lines  in the preview grid
		// so we don't blow up JS
		$result = $sql->executeSQL($qryobj, 10000);
	} else {
		$result = $sql->executeSQL($qryobj);
	}

	if (isset($qryobj['format']) && $qryobj['format'] == 'json') {
		// Send as a file/download
		header('Content-Type: application/json');
		header('Content-Disposition: attachment; filename=results.json');
		print $result;
	} elseif (isset($qryobj['format']) && $qryobj['format'] == 'csv') { 
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename=results.csv');
		// Send a CSV to the browser
		$out = fopen('php://output', 'w');
		$header = false;
		foreach (json_decode($result, true) as $row) {
			if (empty($header)) {
				$header = array_keys($row);
				fputcsv($out, $header);
				$header = array_flip($header);
			}
			fputcsv($out, array_merge($header, $row));
		} 
		fclose($out);
	} else {
		// Send back to js for use in the inline grid
		return $result; 
	}
})->middleware('auth');

Route::post('/ajax/saveobject', function(Request $request) {
	$acs = New ACSQuery();

	$objname = $request->json('objname');
	$obj = $request->json('obj');
	$userid = Auth::id();
	
	$acs->saveObject($objname, $obj, $userid);
	
})->middleware('auth');
