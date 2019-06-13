<?php

return [
	// Dataset configurations
	'2016_acs5' => [
		'label' => '2016 ACS 5-year',
		'titles' => [
			'detail_tables' => [
				'base_url' => 'https://api.census.gov/data/2016/acs/acs5',
				'variable_file' => 'https://api.census.gov/data/2016/acs/acs5/variables.json',
				'value_type' => 'BIGINT(11)'
			]
		],
		'geographies' => [ 
			'US' => 'us',
			'REGION' => 'region',
			'DIVISION' => 'division',
			'STATE' => 'state',
			//'COUNTY' => 'county',
			//'STATAREA' => 'combined+statistical+area',
			//'ZCTA' => 'zip+code+tabulation+area'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE')
		]
	]
];
