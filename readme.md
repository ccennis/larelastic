# Larelastic
 
A package to quickly get off the ground with querying your elastic index by making use of the query, bool, sort and filter methods. 

### Contents
* [Installation](#installation)
* [Usage and Examples](#usage)
	* [Filter Example](#filter)
	* [Should Example](#should)
	* [Sorting Example](#sorting)
	* [Pagination Example](#pagination)
	* [Multimatch Example](#multimatch)
* [Nested Queries](#nested)

## <a id="installation"></a>Installation 

### Composer

#### Add to your composer.json "repositories" section

```json
{ "type": "git", "url": "git@github.com:ccennis/larelastic.git" }
```

#### Require the package from **command line**

```text
composer require ccennis/larelastic
```

### Laravel

##### Add the Third Party Service Providers in config/app.php

```php
ccennis\larelastic\Providers\LarelasticServiceProvider::class
```

##### Add the Third Party Aliases in config/app.php

```php
'Elastic' => ccennis\larelastic\Facades\Elastic::class
```

##### Get Config Files

```php
php artisan vendor:publish
```

#### Add the following variables to your .env file

````php
ELASTICSEARCH_HOST=http://localhost
ELASTICSEARCH_PORT=9200
ELASTICSEARCH_INDEX=myIndex
````

## <a id="usage"></a> Usage and Examples

 
[You can find a sample elastic index here.](https://www.elastic.co/guide/en/kibana/current/tutorial-load-dataset.html) I will use examples from the account index below.


Any of the [bool](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html) methods (`must`, `must_not`, `should`, and `filter`) require an array of field, operator, value, and nested


Key | Type | Description
--- | ---- | -----------
field | String | allows dot notation, e.g. "seller.email"
operator | String | allowable params: "eq, begins with, ends with, contains, gte, lte, gt, lt, in"
value | String, date, integer | your query item
nested | Boolean | true or false depending on whether your params are within a nested document

Sample Bool query to find a seller by email:

```php

$mustData[] = [
    'field' => 'address',
    'operator' => 'eq',
    'value' => '171 Putnam Avenue',
    'nested' => false
];

Elastic::must($mustData)->query();

```

This will return a json response of the elastic result set.

The below $mustData by itself will return 502 results but let's also apply a filter of gender F to narrow it further.

```php
        $mustData[] = [
            'field' => 'age',
            'operator' => 'gt',
            'value' => '30',
            'nested' => false
        ];
```
### <a id="filter"></a>Filter Example

```php
        $filterData['clauses'] = [[
            'field' => 'gender',
            'operator' => 'eq',
            'value' => 'F'
        ]];

        Elastic::must($mustData)
            ->filter($filterData)
            ->query();
```
            
Now you should have a result set of about 249.

### <a id="should"></a>Should Example

We can also apply a "should" parameter to say we would like the employer to be Lotron, Zosis or Amazon. This would be treated as an "OR" statement in contrast to the "AND" statement of the MUST clause.

        $shouldData['clauses'] = [
            [
                'field' => 'employer',
                'operator' => 'eq',
                'value' => 'Zosis',
                'nested' => false
            ],
            [
                'field' => 'employer',
                'operator' => 'eq',
                'value' => 'Lotron',
                'nested' => false
            ],
            [
                'field' => 'employer',
                'operator' => 'eq',
                'value' => 'Amazon',
                'nested' => false
            ]
        ];

        return Elastic::must($mustData)
        ->filter($filterData)
        ->should($shouldData)
        ->query();
        
If you have multiple OR scenarios that need to be considered independently, you could chain them as separate datasets:

		Elastic::should($shouldDataSet1)
		->should($shouldDataSet2)
		->query();
        
Now you should only have 2 people who work at the places listed in our `should` and who are female. Let's sort them. Bear in mind your elastic index schema must have the correct datatypes assigned for search and sort. 


### <a id="sorting"></a>Sorting Example

See `Using Field Types`for sorting using fields with specific field_types.

        $sortData[] = [
            'field' => 'account_number',
            'order' => 'desc',
            'nested' => false
        ];
        
#### Missing Values
You can optionally specify what to do with `missing` values regarding sort by passing in `'missing' => "_first"`. By default, they are sorted "_last". 

#### Nested Sort
You can pass in `true` for 'nested' param with sorting. This will require you to put the full path as the field:

        $sortData[] = [
             'field' => 'seller.rankNumber',
              'order' => 'asc',
              'nested' => true
         ];
                       
This will produce the following json object under your sort object:

		{
		    "seller.rankNumber": {
		        "missing": "_last",
		        "order": "asc",
		        "nested_path": "seller"
		    }
		}
        
Final query:    

			return Elastic::must($mustData)
			->filter($filterData)
			->should($shouldData)
			->sort($sortData)
			->query();

### <a id="pagination"></a>Pagination Example
        
For pagination, the Elastic facade also accepts `page` and `size` methods, i.e. 

		return Elastic::must($mustData)
		->page(2)
		->size(100)
		->query()
	
For simple, quick queries ([term queries](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html)), you can just use a "get" method:
	
	  return Elastic::get([
	    'field' => 'employer',
	    'operator' => 'eq',
	    'value' => 'Zosis'
	]);
	
To return a specific fieldset, you can use the `_source` method:

	return Elastic::_source('id')->get([
	            'field' => 'employer',
	            'operator' => 'eq',
	            'value' => 'Zosis'
	        ]); 
	        
To query a different index than your .env default, or if you choose not to set a default, you can specify your index:

	return Elastic::index('myIndex')->_source('id')->get([
	            'field' => 'employer',
	            'operator' => 'eq',
	            'value' => 'Zosis'
	        ]);
	        
### <a id="multimatch"></a>Multimatch Example

Larelastic will support [multimatch queries](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html). The query [type](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html#multi-match-types) is set as 'cross_ fields' by default but you can pass in another type as a second parameter (e.g. Elastic::multimatch($multiMatchData, 'best_fields');

    $multiMatchData[] = [
        'field' => [
            'product_description', 
            'mpn', 
            'manufacturer_name'
        ],
        'operator' => 'eq',
        'value' => $keywords,
        'nested' => false
     ];

    $response = Elastic::multimatch($multiMatchData)->query();
	        
### <a id="nested"></a>Nested Queries

Larelastic supports querying nested documents. 

Consider the schema

	"mappings": {
		"items": {
			"properties": {
				"seller": {
					"type": "nested",
					"properties": {
						"email": {
							"type": "keyword",
							"normalizer": "lowercase_normalizer"
						}
					}
				}
			}
		}
	}

in order to query an `items` by the nested field `seller.email` you can specify `nested => true` in your bool clause. 

the `sort` method will also allow a nested param which will allow you to sort on a nested document field. 

### Using Field Types

In some schemas you may use a field for various reasons. If you need to designate a name field as raw or a keyword, you can specify this field_type when sorting.

For the below schema entry, in order to be able to sort on this field, you would need to add `'field_type' => 'raw'` to your $sortData.

     "manufacturer_name": {
            "type": "text",
            "analyzer": "my_search_analyzer",
            "search_analyzer": "standard",
            "fields": {
                "raw": {
                    "type": "keyword",
                    "normalizer": "lowercase_normalizer",
                    "index": "true"
                }
            }
        }

	return Elastic::sort([
           'field' => 'manufacturer_name',
           'order' => 'desc',
           'nested' => false,
           'field_type' => 'raw'
        ])->query();

### <a id="aggregated"></a>Aggregated Queries

 [You can perform aggregated queries.](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations.html) 
 
 Currently supported are agg queries with `bucketing`, `metric`, and `pipeline` queries. This field is the "agg_type" field. 
 
 options for `bucket_types` for rolling up data by intervals are `avg`, `sum`, `max`, `min`, and `count`.
 
 `bucket_field` is your own name for your aggregation.
 
 `histogram_field` can be a date or integer, which will determing your `interval`.
 
         $bucketData[] = [
            'agg_type' => 'pipeline',
            'bucket_name' => 'monthly_sales',
            'bucket_field' => 'sales',
            'bucket_type' => 'sum',
            'histogram_field' => 'order_item.created_at',
            'agg_field' => 'order_item.sell_price',
            'interval' => 'month',
            'min_doc_count' => '0',
            'bounds_min' => "2019-01-01",
            'bounds_max' => "2019-02-01",
            'nested' => true
        ];