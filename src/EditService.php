<?php

namespace Meunik\Edit;

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
        $relationships = $this->relationshipsList($values);
        $keysEdit = $this->clean($values, $relationships);

        $before = $this->before($table, $values);

        // FACT EDITING
        foreach ($keysEdit as $item) {
            $exception = $this->exception($table, $values, $item);
            if ($exception) continue;

            if ($this->date($table[$item]) != $values[$item]) $table[$item] = $values[$item];
            // if (array_key_exists($item, $table->toArray())) unset($table[$item]);
        }

        $this->after($table, $values, $before);
        $this->save($table);

        $this->relationships($table, $values, $relationships);

        return $this->table;
    }

    private function relationships($table, $values, $relationships)
    {
        if (count($relationships) == 0) return $table;

        foreach ($relationships as $item) {

            $exception = $this->exception($table, $values, $item);
            if ($exception) continue;

            $camelCase = $this->snakeCaseToCamelCase($item);

            if (is_null($table[$camelCase])) continue;
            if (!isset($values[$item])) continue;

            if (isset($table[$camelCase][0]) && is_object($table[$camelCase][0])) {
                $this->arrayObjects($table, $values, $item);
            } else {
                $this->update($table[$camelCase], $values[$item], $this->relationshipsList($table[$camelCase]));
            }

        }

        return $relationships;
    }

    private function arrayObjects($table, $values, $relationship)
    {
        $camelCase = $this->snakeCaseToCamelCase($relationship);

        $countTable = count($table[$camelCase]);
        $countValues = count($values[$relationship]);

        $difference = $countValues - $countTable;

        $register = array_slice($values[$relationship], -$difference, $difference, true);
        $edit = array_slice($values[$relationship], 0, $countTable, true);

        // Multiple relationship registration is currently disabled
        // if (count($register) != 0) $this->createMissingObjectInObjectArrays($table, $register, $relationship);

        if (count($edit) != 0) {
            foreach ($table[$camelCase] as $key => $object) {
                if (!isset($values[$relationship][$key])) {
                    $this->deleteMissingObjectInObjectArrays($table, $relationship, $key, $object);
                    continue;
                }

                $this->update($object, $values[$relationship][$key]);
            }
        }
        return $table;
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
            foreach ($relationships as $item)
                if (array_key_exists($item, $table)) unset($table[$item]);

        return $table;
    }

    private function relationshipsList($table)
    {
        $relationship = [];
        foreach ($table as $key => $value) {
            if (is_array($value) && (!in_array($key, $this->columnsCannotChange_defaults)) && (!in_array($key, $this->relationshipsCannotChangeCameCase_defaults))) {
                $relationship[] = $key;
            }
        }
        return $relationship;
    }

    private function deleteMissingObjectInObjectArrays($table, $relationship, $key, $object)
    {
        if ($this->deleteMissingObjectInObjectArrays) {
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

    private function snakeCaseToCamelCase($string, $countOnFirstCharacter = false)
    {
        $str = str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
        if (!$countOnFirstCharacter) $str[0] = strtolower($str[0]);
        return $str;
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
