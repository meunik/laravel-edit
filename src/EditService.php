<?php

namespace App\Service\Edita;

use Carbon\Carbon;

class EditService
{
    public $newTableClass;
    public $values;
    public $table;
    public $tableRelationships;
    public $before = false;
    public $after = false;
    public $exception = false;

    public $columnsCannotChange_defaults = ['pivot','created_at','updated_at'];
    public $relationshipsCannotChangeCameCase_defaults = ['pivot'];
    public $createMissingObjectInObjectArrays = false;
    public $deleteMissingObjectInObjectArrays = false;

    /**
     * Optional
     * Appends the list of columns that cannot be changed.
     */
    public function notChange($columnsCannotChange = [])
    {
        $columnsCannotChange = is_array($columnsCannotChange) ? $columnsCannotChange : func_get_args();
        $this->columnsCannotChange_defaults = array_merge($columnsCannotChange, $this->columnsCannotChange_defaults);
        return $this;
    }

    /**
     * Mandatory
     * object with the new values
     */
    public function values($values)
    {
        $this->values = is_object($values) ? $values->toArray() : $values;
        return $this;
    }

    /**
     * Mandatory
     * object with previous values
     */
    public function table($table)
    {
        $this->newTableClass = $table;
        return $this;
    }

    public function run()
    {
        $table = $this->newTableClass;
        $this->table = ($table::find($this->values['id'])) ?: abort(400, 'Id not found.');
        return $this->update($this->table, $this->values);
    }

    /********************************* privates *********************************/

    /**
     * Recurcive function, responsible for FACT EDITING
     */
    private function update($table, $values)
    {
        $relationships = $this->relationshipsList($table, $values);
        $keysEdit = $this->clean($values, $relationships);

        $before = $this->before($table, $values);

        // FACT EDITING
        foreach ($keysEdit as $item) {
            $exception = $this->exception($table, $values, $item);
            if ($exception) continue;

            if ($this->date($table[$item]) != $values[$item]) $table[$item] = $values[$item];
        }

        $this->after($table, $values, $before);
        $this->save($table);

        $this->relationships($table, $values, $relationships);

        return $this->table;
    }

    /**
     * $table = new values
     * $values = old values
     * $relationship = Relationship
     */
    private function relationships($table, $values, $relationships)
    {
        if (count($relationships) == 0) return $table;

        foreach ($relationships as $key => $value) {
            $key = $this->camelCaseToSnake_case($key);

            $exception = $this->exception($table, $values, $key);
            if ($exception) continue;

            $camelCase = $this->snake_caseToCamelCase($key);

            if (!isset($values[$key])) continue;

            // Checks the type of relationship as defined in the Model
            if ($this->is_multi($value)) {
                $this->arrayObjects($table, $values, $key);
            } else {
                $this->update($table[$camelCase], $values[$key]);
            }

        }

        return $relationships;
    }

    private function is_multi($relationshipValue) {
        return is_array($relationshipValue);
    }

    /**
     * $table = new values
     * $values = old values
     * $relationship = Relationship
     */
    private function arrayObjects($table, $values, $relationship)
    {
        $camelCase = $this->snake_caseToCamelCase($relationship);

        if (count($table->toArray())<=0) return false;

        $tableCollection = collect($table[$camelCase]);
        $valuesCollection = collect($values[$relationship]);

        $tableRelationship = $table->relationship;
        if (is_null($tableRelationship)) abort(400, "Parameter 'relationship' not found in the model");

        $tableRelationshipModel = new $tableRelationship[$camelCase][0];

        $keyName = $tableRelationshipModel->getKeyName();

        if (!is_null($table[$camelCase])) {
            foreach ($table[$camelCase] as $key => $object) {

                if ($valuesCollection->contains($keyName, $object[$keyName]) == false) $this->deleteMissingObjectInObjectArrays($table, $relationship, $key, $object);

                if ($valuesCollection->contains($keyName, $object[$keyName])) {
                    $where = $valuesCollection->where($keyName, $object->$keyName);

                    if ($where->count()>0) {
                        $value = array_values($where->all())[0];
                        $this->update($object, $value);
                    }
                }
            }
        }

        foreach ($values[$relationship] as $key => $object) {
            if (isset($object[$keyName]) || !isset($tableRelationship[$camelCase])) continue;
            $create = $table->$camelCase()->create($object);
        }
    }

    private function createMissingObjectInObjectArrays($table, $register, $relationship)
    {
        if ($this->exception) $this->exception($table, $register, $relationship, true);
    }

    private function clean($values, $tableRelationships)
    {
        $keysEdit = $this->removeRelationships($values, $tableRelationships);
        $keysEdit = $this->removeColumnsCannotChange($keysEdit);
        return array_keys($keysEdit);
    }

    private function removeColumnsCannotChange($table)
    {
        foreach ($this->columnsCannotChange_defaults as $item) {
            if (array_key_exists($item, $table)) unset($table[$item]);
        }
        return $table;
    }

    private function removeRelationships(Array $table, $relationships)
    {
        if ($relationships)
            foreach ($relationships as $key => $item) {
                $key = $this->camelCaseToSnake_case($key);
                if (array_key_exists($key, $table)) unset($table[$key]);
            }
        return $table;
    }

    private function relationshipsList($table, $values)
    {
        $tableRelationship = $table->relationship;
        $arrayKeys = ($tableRelationship) ? array_keys($tableRelationship) : [];

        $relationships = [];
        foreach ($values as $key => $value) {
            $key = $this->snake_caseToCamelCase($key);
            if ((!in_array($key, $this->columnsCannotChange_defaults)) && (!in_array($key, $this->relationshipsCannotChangeCameCase_defaults)) && (in_array($key, $arrayKeys))) {
                $relationships[$key] = $tableRelationship[$key];
            }
        }
        return $relationships;
    }

    private function deleteMissingObjectInObjectArrays($table, $relationship, $key, $object)
    {
        if ($this->deleteMissingObjectInObjectArrays) {
            if (isset($object['pivot'])) $object['pivot']->delete();
            $object->delete();
            unset($table[$relationship][$key]);
        }
    }

    private function date($date)
    {
        try {
            return ($this->validateDate($date)) ? Carbon::parse($date)->format('Y-m-d') : $date;
        } catch (\Exception $e) {
            return $date;
        }
    }
    private function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        try {
            $d = Carbon::createFromFormat($format, $date);
            return $d && $d->format($format) == $date;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function snake_caseToCamelCase($string, $countOnFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$countOnFirstCharacter) $str[0] = strtolower($str[0]);
        return $str;
    }

    private function camelCaseToSnake_case($string, $countOnFirstCharacter = false)
    {
        return strtolower( preg_replace( ["/([A-Z]+)/", "/_([A-Z]+)([A-Z][a-z])/"], ["_$1", "_$1_$2"], lcfirst($string) ) );;
    }

    /**
     * Optional
     * Performs treatment before the update.
     */
    private function before($table, $values)
    {
        if ($this->before) {
            $before = new $this->before;
            return $before->before($table, $values);
        }
        return $this;
    }

    /**
     * Optional
     * Performs after-update handling.
     */
    private function after($table, $values, $before)
    {
        if ($this->after) return $before->after($table, $values);
    }

    private function exception($table, $values, $camelCase, $create = false)
    {
        if ($this->exception) {
            $exception = new $this->exception;
            return $exception->exception($table, $values, $camelCase, $create);
        } else {
            return false;
        }
        return $this;
    }

    private function save($table)
    {
        if (count($table->toArray())>0) $table->save();
    }
}
