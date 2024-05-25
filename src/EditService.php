<?php

namespace Meunik\Edit;

use Meunik\Edit\Core;
use Meunik\Edit\Utils;
use Meunik\Edit\FunctionsFromModel;

class EditService
{
    use FunctionsFromModel, Utils, Core;

    public $model;
    public $editModel;
    public $laravelEdit;

    public $values;
    public $table;
    public $tableRelationships;

    public $editAppends = false;
    public $columnsCannotChange_defaults = ['pivot','created_at','updated_at'];
    public $relationshipsCannotChangeCameCase_defaults = ['pivot'];
    public $deleteMissingObjectInObjectArrays = true;

    /**
     * (Optional)
     * Appends the list of columns that cannot be changed.
     *
     * @param array $columnsCannotChange
     * @return $this
     */
    public function notChange($columnsCannotChange = [])
    {
        $columnsCannotChange = is_array($columnsCannotChange) ? $columnsCannotChange : func_get_args();
        $this->columnsCannotChange_defaults = array_merge($columnsCannotChange, $this->columnsCannotChange_defaults);
        return $this;
    }

    /**
     * (Mandatory)
     * object with the new values.
     *
     * @param array|Request $values
     * @return $this
     */
    public function values($values)
    {
        $this->values = is_object($values) ? $values->toArray() : $values;
        return $this;
    }

    /**
     * (Mandatory)
     * Object with previous values.
     *
     * @param Model $table
     * @return $this
     */
    public function table($table)
    {
        $this->model = $table;
        return $this;
    }

    public function run()
    {
        $table = $this->model;
        $this->table = ($table::find($this->values['id'])) ?: self::error('Id not found.');
        return $this->update($this->table, $this->values);
    }
}
