<?php

return [
	// Dataset configurations
	'2019_acs5' => [
		'label' => '2018 ACS 5-year',
		'titles' => [
			'detail_tables' => [
				'base_url' => 'https://api.census.gov/data/2019/acs/acs5',
				'variable_file' => 'https://api.census.gov/data/2019/acs/acs5/variables.json',
				'value_type' => 'BIGINT(11)'
			]
		],
		'geographies' => [ 
			'US' => 'us',
			'REGION' => 'region',
			'DIVISION' => 'division',
			'STATE' => 'state',
			'COUNTY' => 'county',
			'CSA' => 'combined+statistical+area',
			'CDCURR' => 'congressional+district',
			'SDUNI' => 'school+district+(unified)',
			'ZCTA' => 'zip+code+tabulation+area',
			'TRACT' => 'tract',
			'BLKGRP' => 'block+group'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE'),
			'CDCURR' => array('STATE'),
			'SDUNI' => array('STATE'),
			'TRACT' => array('STATE','COUNTY'),
			'BLKGRP' => array('STATE','COUNTY','TRACT')
		]
	],
	'2018_acs5' => [
		'label' => '2018 ACS 5-year',
		'titles' => [
			'detail_tables' => [
				'base_url' => 'https://api.census.gov/data/2018/acs/acs5',
				'variable_file' => 'https://api.census.gov/data/2018/acs/acs5/variables.json',
				'value_type' => 'BIGINT(11)'
			]
		],
		'geographies' => [ 
			'US' => 'us',
			'REGION' => 'region',
			'DIVISION' => 'division',
			'STATE' => 'state',
			'COUNTY' => 'county',
			'CSA' => 'combined+statistical+area',
			'CDCURR' => 'congressional+district',
			'SDUNI' => 'unified+school+district',
			'ZCTA' => 'zip+code+tabulation+area',
			'TRACT' => 'census+tract',
			'BLKGRP' => 'block+group'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE'),
			'CDCURR' => array('STATE'),
			'SDUNI' => array('STATE'),
			'TRACT' => array('STATE','COUNTY'),
			'BLKGRP' => array('STATE','COUNTY','TRACT')
		]
	],
	'2017_acs5' => [
		'label' => '2017 ACS 5-year',
		'titles' => [
			'detail_tables' => [
				'base_url' => 'https://api.census.gov/data/2017/acs/acs5',
				'variable_file' => 'https://api.census.gov/data/2017/acs/acs5/variables.json',
				'value_type' => 'BIGINT(11)'
			]
		],
		'geographies' => [ 
			'US' => 'us',
			'REGION' => 'region',
			'DIVISION' => 'division',
			'STATE' => 'state',
			'COUNTY' => 'county',
			'CSA' => 'combined+statistical+area',
			'CDCURR' => 'congressional+district',
			'SDUNI' => 'unified+school+district',
			'ZCTA' => 'zip+code+tabulation+area'
			'TRACT' => 'census+tract',
			'BLKGRP' => 'block+group'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE'),
			'CDCURR' => array('STATE'),
			'SDUNI' => array('STATE'),
			'TRACT' => array('STATE','COUNTY'),
			'BLKGRP' => array('STATE','COUNTY','TRACT')
		]
	],
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
			'COUNTY' => 'county',
			'CSA' => 'combined+statistical+area',
			'CDCURR' => 'congressional+district',
			'SDUNI' => 'unified+school+district',
			'ZCTA' => 'zip+code+tabulation+area'
			'TRACT' => 'census+tract',
			'BLKGRP' => 'block+group'
		],
		'geo_parents' => [
			'COUNTY' => array('STATE'),
			'CDCURR' => array('STATE'),
			'SDUNI' => array('STATE'),
			'TRACT' => array('STATE','COUNTY'),
			'BLKGRP' => array('STATE','COUNTY','TRACT')
		]
	]
];
