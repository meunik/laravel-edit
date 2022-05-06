<?php

namespace Meunik\Edit;

use Meunik\Edit\Edit;

class EditExemple extends Edit
{
    protected $deleteMissingObjectInObjectArrays = true;
    // protected $createMissingObjectInObjectArrays = true;
    protected $columnsCannotChange_defaults = [];
    protected $relationshipsCannotChangeCameCase_defaults = [];

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
}
