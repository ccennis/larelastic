# larelastic

An indexing and querying package to create, maintain and search an elastic instance with [Laravel Scout](https://laravel.com/docs/5.8/scout) as backbone.

### Contents
 * [Installation](#installation)
 	* [Indexes](#indexing)
 		* [Create and Maintain Index](#createMaintain)
 		* [Migrating data](#migrating)
 		* [Updating index](#updateIndex)
 		* [Drop index](#dropIndex)
 		* [Additional Configuration Options](#additionalOptions)
 * [Usage and Examples](#usage)
 	* [Searching](#searching)
	 	* [Where Example](#where)
	 	* [orWhere Example](#orWhere)
	 	* [Multimatch Example](#multi)
	 	* [Pagination Example](#pagination)
	 	* [Sorting Example](#sorting)
        * [GeoSorting Example](#geosorting)
        * [Aggs Example](#aggs)
        * [Multimatch Example](#multimatch)
 
 
## <a id=installation></a>Installation 


### Laravel
 
##### Add the Third Party Service Providers in config/app.php
 
 ```php
 Larelastic\Elastic\Providers\ElasticServiceProvider::class
 ```
 
##### Add the Third Party Aliases in config/app.php
 
 ```php
'Elastic' => Larelastic\Elastic\Facades\Elastic::class
 ```
 
##### Get Config Files
 
 ```php
 php artisan vendor:publish
 ```
 
##### Add the following variables to your .env file
 
 ````php
ELASTICSEARCH_HOST=http://elastic:9200
ELASTICSEARCH_PORT=9200
SCOUT_DRIVER=elastic
SCOUT_ELASTIC_HOST=http://elastic:9200
 ````
 
## <a id=indexing></a> Indexing
(for a completely new installation -- skip ahead past 3rd step if index exists)

Since this package relies on the existence of models and index configurators to create, search and maintain indices,
you must run the below commands to generate and set up the appropriate objects.

1.) create an indexConfigurator model (in this example, "Tattoo"):

```
 php artisan make:index-configurator TattooIndexConfigurator
```
 
2.) create your searchable Model:

```
php artisan make:searchable-model Product --index-configurator=TattooIndexConfigurator
```

<div id="createMaintain">
3.) create your Elastic index using model with full path:

```
php artisan elastic:create-index "App\Models\Tattoo"
```

## <a id=indexing></a> Indexing
4.) add data to your index

```php artisan scout:import "App\Models\Tattoo"```


### <a id=migrating></a>Migrating to new Index

```
php artisan elastic:migrate "App\MyModel" my_index_v2
```

This process creates a new index, imports all data from previous to this index, and then links the old index to this new one via an alias

### <a id=updateIndex></a> Update a mapping:

```
php artisan elastic:update-mapping "App\\Models\MyModel"
```

### Update index:

```
php artisan elastic:update-index "App\Models\MyModelConfigurator"
```

### <a id=dropIndex></a> Drop index:

```
php artisan elastic:drop-index "App\Models\MyModel"
```

### <a id=additionalOptions></a> Additional Configuration Options:

In your designated model, you may want to make use of the method: 

```
public function searchableQuery()
{
    return $self->newQuery();
}
```
This will enable you to fine tune the actual query that is sent to elastic if you need to eager load or specify relationships.
 
## <a id=usage></a> Usage and Examples

Where/OrWhere queries make use of the [bool](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html) methods (`must`, `must_not`, and `should`). Syntax is meant to be eloquent-like, but must use the `search` trait to differentiate it from actual Eloquent.

### Valid Operators

Operator | Function | Usage
---------| ---------|--------	
=		   | equals | ->where('col', '=', 'value') <br/> ->where('col', 'value')
<>	   | not equals | ->where('col', '<>', 'value')
in		|	in field array | ->where('col', 'in', ['value'])
begins_with| search phrase beginning matched to search | ->where('col', 'begins_with', 'value')
ends_with | search phrase ending matched to search | ->where('col', 'ends_with', 'value')
contains | search phrase found in field | ->where('col', 'contains', 'value')
gte |	>= | ->where('col', 'gte', 'value')
lte	|	<= | ->where('col', 'lte', 'value')
gt	|	>  | ->where('col', 'gt', 'value')
lt	|  <  | ->where('col', 'lt', 'value')
between  | date found between two dates | ->where('col', 'between', ['value1', 'value2'])
exists	  | the value is not null and is found in index | ->where('col', 'exists')

#### <a id="where"></a>Sample `Where` query

```php

$response = Tattoo::search()->where('name', 'mold')->get();
$response = Tattoo::search()->where('name', '=', 'mold')->get();


```

Both of the above queries are valid. Default operator is '=' unless provided. 

#### <a id="orWhere"></a>Sample `Or Where` query

```php

$response = Tattoo::search()->orWhere(['name', '=', 'mold'],['name', '=', 'Element'])->get();


```
takes two arrays, makes use of the `should` bool operator from Elastic. Evaluates to "find this OR that".

```php

$response = Tattoo::search()->orWhere(function($query, $boolean){
            $query->where('name', '=','Mold', $boolean)
            ->where('model', '=','test', $boolean);
            return $query;
        })->orWhere(function($query, $boolean){
            $query->where('name', '=','element', $boolean)
            ->where('model', '=','test', $boolean);
            return $query;
        })->get();


```
Closure, makes use of multiple groupings under `should` bool operator from Elastic. Evaluates to "find (this AND that) OR (this and that)".


#### <a id="multi"></a>Sample `Multimatch` query

```php

 $response = Tattoo::search()->whereMulti(['name', 'model'],'=','Mold')->get();
 
```

This makes use of the [multimatch](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html) elastic function, takes an array of fields. Default search type is [phrase prefix](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html#type-phrase), but accepts an argument to override, such as [best_fields](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html#type-best-fields): 

```php

 $response = Tattoo::search()->whereMulti(['name', 'model'],'=','Mold', 'best_fields')->get();
 
```

#### <a id="pagination"></a>Pagination

```php

 $response = Tattoo::search()->whereMulti(['name', 'model'],'=','Mold')->paginate(20);
 
```

Pagination functions much the same as in eloquent. It will wrap your response in the Paginator Length Aware metadata fields:

```
first_page_url: "http://myProject.com?page=1",
from: 1,
last_page: 2,
last_page_url: "http://myProject.com?page=3",
next_page_url: "http://myProject.com?page=2",
path: "http://myProject.com",
per_page: 20,
prev_page_url: null,
to: 2,
total: 36
```

#### <a id="sorting"></a>Sorting

Simple Sorting:

```php
$response = Tattoo::search()->where('name', 'mold')
sort->('name','desc')->get()
```
Sorting defaults to asc and can be stacked for multiple sorts. 

If you need to specify a field_type for the sort field (i.e. 'keyword' or 'raw') you can include it as a param:

```php
$response = Tattoo::search()->where('name', 'mold')
sort->('name', 'desc', 'keyword')->get()
```
The above may be necessary in the event you are already querying a nested object and want to ensure the datatype is listed separately.

#### <a id="geosorting"></a> Geosorting

Sorting by distance based on latitude and longitude. Sample query in Elastic:

```json
{
"sort": [{
		"_geo_distance": {
			"location": {
				"lat": 40,
				"lon": -70
			},
			"order": "asc",
			"unit": "mi",
			"mode": "min",
			"distance_type": "arc",
			"ignore_unmapped": true
		}
	]}
}
```
In order to use this in the package, you can use the following syntax:

```php
$data = [
                'field' => 'location_lat_long', 
                'lat' => $lat,
                'long' => $lat,
            ];

            $this->search->buildGeoSort($data);
```

Valid fields to pass in the data array are above in the elastic query. 

Valid arguments for unit include: mi (miles), in (inches), yd (yards), km (kilometers), cm (centimeters), mm (millimeters).

There are two distance calculation modes: arc (the default), and plane. The arc calculation is the most accurate.
```json
{

```php

mi (miles), in (inches), yd (yards), km (kilometers), cm (centimeters), mm (millimeters).


#### <a id="aggs"></a>Aggregation (aggs)

```php

$response = Tattoo::search()->where('name', 'mold')
->rollup('card_color')->get();

```

initial support for aggs takes a column name and creates buckets to count all matches. result set will be under an "aggs" object, like so:

```json 
{
	aggs: {
		agg_field: {
			doc_count_error_upper_bound: 0,
			sum_other_doc_count: 0,
			buckets: [{
					key: "G",
					doc_count: 35
				},
				{
					key: "B",
					doc_count: 1
				}
			]
		}
	}
}
```
