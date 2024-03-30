## Edit by request for Laravel

### Laravel wrapper for [Edit table by request format](https://github.com/meunik/laravel-edit)

Automates editing based on request formatting.

Editing an establishment's table, request example.

```json
{
    "id": 1,
    "name": "Example Establishment Name",
    "telephone": [
        {
            "id": 1,
            "number": "(00) 00000-0000",
        },
        {
            "id": 2,
            "numero": "(00) 00000-0000",
        }
    ],
    "Address": {
        "id": 1,
        "zip": "00000-000",
        "address": "Example Establishment Street",
        "number": 653,
        "complement": "nd",
        "neighborhood": "Example Neighborhood",
        "city": "Example City",
        "state": "Example State"
    }
}
```
First you must inform the Model of the table `NameEditExampleModel::table(TableModel::class)` which is the starting point, in the case of the above json it is `EstablishmentExample::class`, then it will be read key by key, if the value of one of these keys is an object, or an array, or an array of objects, the key will initially be considered with a table relationship, then this same key will be validated using [laravel's own Eloquent](https://laravel.com/docs/9.x/eloquent-relationships).

> Suport `$appends`

## Installation

### Only Laravel
Require this package in your composer.json and update composer. This will download the package and the laravel-edit e Carbon libraries also.

    composer require meunik/laravel-edit
  
## Using

First you must inform the Model of the table `NameEditExampleModel::table(TableModel::class)`, then the new values in the format of array or request `->values($request)` and finish by executing the edit `->run()`.

```php
    NameEditExempleModel::table(TableModel::class)->values($request)->run();
```

If you don't want to change a column only at this time.

```php
    NameEditExempleModel::table(TableModel::class)->values($request)->notChange('column1', 'column2')->run();
```


In the case of using multi relationships in the table model, you must:

```php
    public $relationship = [
        'relationshipOne' => RelationshipOne::class,
        'relationshipTwo' => [RelationshipTwo::class], // put it inside an array if the relationship is an array of objects
    ];
```

If you want to ignore a column or relationship in a specific table, add this to the model of that table:

```php
    public $ignoredColumns = ['column1','column2'];
    public $ignoredRelationships = ['relationship1','relationship2'];
```

## Model Configuration

Create a Model.

It is mandatory to have at least this model.
```php
    <?php

    namespace Model\Edit;

    use Meunik\Edit\Edit;

    class NameEditExempleModel extends Edit
    {
        // Add configs and exceptions
    }
```

Define column names and relationship names that by default cannot be changed.
```php
    protected $columnsCannotChange_defaults = [
        'id',
        'column1',
        'column2',
        'pivot',
        'created_at',
        'updated_at',
    ];
    protected $relationshipsCannotChangeCameCase_defaults = [
        'relationship1',
        'relationship2'
    ];
```
In cases of one-to-many relationships, if the relationship is located in the request and the number of objects in the request is empty or less than the number of objects already in the table, it will exclude those that do not exist in the request.
```php
    protected $deleteMissingObjectInObjectArrays = true;
```
Add a pre-treatment and a post-treatment.
```php
    protected $before = self::class;
    protected $after = self::class;

    public function before($table, $values)
    {
        // Code before update.
        return $this;
    }

    public function after($table, $values, $before)
    {
        // Code after update.
    }
```
Add exceptions. This function is executed before editing, if it returns true, the column name or relationship will be ignored in automatic editing, but it can be edited within this function. If it returns false, the name will be automatically edited.
```php
    protected $exception = self::class;

    public function exception($table, $values, $column, $create)
    {
        /*
         * Code before update.
         * Example
        */
        switch ($column) {
            case "nameColumnException":
                return true;
                break;

            case "nameRelationshipException":
                return true;
                break;

            default:
                return false;
                break;
        }
    }
```
    
### License

This Edit for Laravel is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
