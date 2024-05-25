<?php

namespace App\Models;

use App\Models\Other\Path\EditGlobalModel;
use Meunik\Edit\HasEdit;
use Illuminate\Database\Eloquent\Model;

class TableModelExemple extends Model
{
    use HasEdit;

    protected $fillable = ['name'];

    // You need to set it only if you are going to use Global Model and if it is not in the default \App\Models\EditGlobalModel directory
    public $editModel = EditGlobalModel::class;
    public $editAppends = true;
    public $ignoredColumns = ['column1','column2'];
    public $ignoredRelationships = ['relationship1','relationship2'];

    public $relationship = [
        'relationshipOne' => RelationshipOne::class,
        'relationshipTwo' => [RelationshipTwo::class], // put it inside an array if the relationship is an array of objects
    ];

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