<?php

namespace Meunik\Edit;

use Meunik\Edit\Edit;

class EditGlobalModelExemple extends Edit
{
    protected $deleteMissingObjectInObjectArrays = true;
    protected $columnsCannotChange_defaults = [];
    protected $relationshipsCannotChangeCameCase_defaults = [];

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

    public function valuesEdit()
    {
        /*
         * Code before update.
         * Example
        */

        $table = $this->laravelEdit->table;
        $values = $this->laravelEdit->values;
        $column = $this->laravelEdit->keysEdit;
    }

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
}
