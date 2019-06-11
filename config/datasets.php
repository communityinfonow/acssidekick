<?php

return [
	// Dataset configurations
/* legacy format - need to update
	'2015_acs5' => [
		'label' => '2015 ACS 5-year',
		'base_url' => 'https://api.census.gov/data/2015/acs5',
		'variable_files' => [
			'storage/app/private/2015_acs5_variables_d.json'
			//'storage/app/private/2015_acs5_variables_s.json',
			//'storage/app/private/2015_acs5_variables_dp.json'
		],
		'geographies' => [ 
			'US' => 'us',
			'REGION' => 'region',
			'DIVISION' => 'division',
			'STATE' => 'state',
			'COUNTY' => 'county',
			'STATAREA' => 'combined+statistical+area',
			'ZCTA' => 'zip+code+tabulation+area'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE')
		],
		'value_type' => 'BIGINT(11)'	
	],
*/	
	'2016_acs5' => [
		'label' => '2016 ACS 5-year',
		'titles' => [
			'detail_tables' => [
				'base_url' => 'https://api.census.gov/data/2016/acs/acs5',
				//'variable_file' => 'https://api.census.gov/data/2016/acs/acs5/variables.json',
				'variable_file' => 'storage/app/private/2016_acs5_variables_d.json', // use a local copy while testing
				'value_type' => 'BIGINT(11)'
			]
		],
		'geographies' => [ 
			'US' => 'us',
			'REGION' => 'region',
			'DIVISION' => 'division',
			'STATE' => 'state',
			'COUNTY' => 'county',
			'STATAREA' => 'combined+statistical+area',
			'ZCTA' => 'zip+code+tabulation+area'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE')
		]
	]
];
