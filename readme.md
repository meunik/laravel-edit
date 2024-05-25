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

First you must inform the Model of the table `TableModel::edit($request)` which is the starting point, in the case of the above json it is `Establishment::class`, then it will be read key by key, if the value of one of these keys is an object, or an array, or an array of objects, the key will initially be considered with a table relationship, then this same key will be validated using [laravel's own Eloquent](https://laravel.com/docs/eloquent-relationships).

> Supports `$appends`, but the `$appends` compulsorily have the same name as the column in the table

## Installation

### Only Laravel
Require this package in your composer.json and update composer. This will download the package and the laravel-edit e Carbon libraries also.

    composer require meunik/laravel-edit
  
## Using

First you must add the trait `HasEdit` to the `use` of your Model.
```php
    <?php

    namespace App\Models;

    use App\Models\Other\Path\EditGlobalModel;
    use Meunik\Edit\HasEdit;
    use Illuminate\Database\Eloquent\Model;

    class TableModel extends Model
    {
        use HasEdit;

        protected $fillable = ['name'];

        public $editAppends = true;
        public $editModel = EditGlobalModel::class; // You need to set it only if you are going to use Global Model and if it is not in the default \App\Models\EditGlobalModel directory

        public $relationship = [
            'relationshipOne' => RelationshipOne::class,
            'relationshipTwo' => [RelationshipTwo::class], // put it inside an array if the relationship is an array of objects
        ];
    }
```

To use in the Controller, you must enter the Table Model `TableModel::edit($request->all())`, then the new values in the format of array or request `edit($request)` and finish by executing the edit `->run()`.
```php
    TableModel::edit($request)->run();
```

If you don't want to change a column only at this time.
```php
    TableModel::edit($request->all())->notChange('column1', 'column2')->run();
```

It can also be called through a Global Model.
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

## Global Model Configuration

Create a Model.

It is mandatory to have at least this model.
```php
    <?php

    namespace Model\Edit;

    use Meunik\Edit\Edit;

    class NameEditExempleGlobalModel extends Edit
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
    public function before()
    {
        $table = $this->laravelEdit->table;
        $values = $this->laravelEdit->values;
        
        // Code before update.
        
        return $this;
    }

    public function after()
    {
        $table = $this->laravelEdit->table;
        $values = $this->laravelEdit->values;
        $before = $this->laravelEdit->before;
        
        // Code after update.
    }
```

Perform treatment of the values before they are edited. This function is executed once before the line editing. It is mandatory to return an array containing the values that will be edited, following this format `['column' => 'value']`. This function replaces the values coming from the `Request`.
```php
    public function valuesEdit()
    {
        /*
         * Code before update.
         * Example
        */

        $table = $this->laravelEdit->table;
        $values = $this->laravelEdit->values;
        $column = $this->laravelEdit->keysEdit;

        return [
            'column1' => 'value1',
            'column2' => 'value2'
            ...
        ]
    }
```

Add exceptions to the columns. This function is executed before the editing of each column, if returned true, the column will be ignored in the editing, but it can be edited within this function. If returned false, the column will be edited automatically. This function does not change the value that will be edited in the column.
```php
    public function exception()
    {
        /*
         * Code before update.
         * Example
        */

        $table = $this->laravelEdit->table;
        $values = $this->laravelEdit->values;
        $column = $this->laravelEdit->attribute;
        $create = $this->laravelEdit->create;

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

> The `before()`, `after()`, `valuesEdit()` and `exception()` functions can be used within the table's model, in which case it will be specific to manipulating the data of this table.
    
### License

This Edit for Laravel is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
