## Edit by request for Laravel

### Laravel wrapper for [Edit table by request format](https://github.com/meunik/laravel-edit)

## Installation

### Only Laravel
Require this package in your composer.json and update composer. This will download the package and the laravel-edit e Carbon libraries also.

    composer require meunik/laravel-edit
  
## Using

Ainda tenho q fazer.

```php
    EditExemple::table(TableModel::class)->values($request)->run();
```

If you don't want to change a column only at this time.

```php
    EditExemple::table(TableModel::class)->values($request)->notChange('column1', 'column2')->run();
```

## Configuration

Ainda tenho q fazer tbm.

Create a Model.

```php
    <?php

    namespace Meunik\Edit;

    use Meunik\Edit\Edit;

    class EditExemple extends Edit
    {
        protected $deleteMissingObjectInObjectArrays = true;
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

        protected $before = self::class;
        protected $after = self::class;
        protected $exception = self::class;

        public function before($table, $values)
        {
            // Code before update.
            return $this;
        }

        public function after($table, $values, $before)
        {
            // Code after update.
        }

        public function exception($table, $values, $camelCase, $create)
        {
            // Code before update.
        }
    }

```
    
### License

This Edit for Laravel is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)