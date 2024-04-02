<?php

namespace Meunik\Edit;

use Carbon\Carbon;

class EditService
{
    public $model;
    public $editModel;
    public $laravelEdit;

    public $values;
    public $table;
    public $tableRelationships;

    public $columnsCannotChange_defaults = ['pivot','created_at','updated_at'];
    public $relationshipsCannotChangeCameCase_defaults = ['pivot'];
    public $deleteMissingObjectInObjectArrays = true;

    public function teste()
    {
        return 'teste';
    }

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
        $this->model = $table;
        return $this;
    }

    public function run()
    {
        $table = $this->model;
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

        $ignoredColumns = $table->ignoredColumns ?: [];
        $ignoredRelationships = $table->ignoredRelationships ?: [];
        $hidden = $table->getHidden() ?: [];
        $appends = $table->getAppends() ?: [];
        if ($table->pivot) {
            foreach ($appends as $value) {
                if (array_key_exists($value, $table->pivot->getOriginal()) && !in_array($value, $ignoredColumns)) {
                    $table->pivot->$value = $values[$value];
                    $table->pivot->save();
                }
            }
        }
        $ignoreds = array_merge($ignoredColumns, $ignoredRelationships, $appends, $hidden);
        $keysEdit = $this->clean($values, $relationships, $ignoreds);

        $before = $this->beforeService($table, $values);

        // FACT EDITING
        foreach ($keysEdit as $item) {
            $exception = $this->exceptionService($table, $values, $item);
            if ($exception) continue;

            if ($this->date($table[$item]) != $values[$item]) $table[$item] = $values[$item];
        }

        $this->save($table);
        $this->afterService($table, $values, $before);

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

            $exception = $this->exceptionService($table, $values, $key);
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


            $tableRelationship = $table->relationship[$camelCase][0];
            $ignoredColumns = $table->ignoredColumns ?: [];

            $create = $tableRelationship::create($object);
            if ($create->getAppends())
                foreach ($create->getAppends() as $value)
                    if (array_key_exists($value, $table->pivot->getOriginal()) && !in_array($value, $ignoredColumns))
                        $table->$camelCase()->attach($create->id, [$value => $object[$value]]);

            else $table->$camelCase()->attach($create->id);
        }
    }

    private function clean($values, $tableRelationships, $ignoreds)
    {
        $keysEdit = $this->removeRelationships($values, $tableRelationships);
        $keysEdit = $this->removeColumnsCannotChange($keysEdit, $ignoreds);
        return array_keys($keysEdit);
    }

    private function removeColumnsCannotChange($table, $ignoreds)
    {
        foreach ($this->columnsCannotChange_defaults as $item)
            if (array_key_exists($item, $table)) unset($table[$item]);

        foreach ($ignoreds as $item)
            if (array_key_exists($item, $table)) unset($table[$item]);

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
            // $camelCase = $this->snake_caseToCamelCase($relationship);
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
    private function beforeService($table, $values)
    {
        $table = $this->hidden($table);

        $this->laravelEdit = ($this->laravelEdit)?: new LaravelEdit;
        $this->laravelEdit->table = $table;
        $this->laravelEdit->values = $values;

        if (method_exists($table, 'before')) {
            $table->laravelEdit = $this->laravelEdit;
            return $table->before();
        } elseif ($this->editModel && method_exists($this->editModel, 'before')) {
            $this->editModel->laravelEdit = $this->laravelEdit;
            return $this->editModel->before();
        }
        return $this;
    }

    /**
     * Optional
     * Performs after-update handling.
     */
    private function afterService($table, $values, $before)
    {
        $table = $this->hidden($table);

        $this->laravelEdit = ($this->laravelEdit)?: new LaravelEdit;
        $this->laravelEdit->table = $table;
        $this->laravelEdit->values = $values;
        $this->laravelEdit->before = $before;

        if (method_exists($table, 'after')) {
            $table->laravelEdit = $this->laravelEdit;
            return $table->after();
        } elseif ($this->editModel && method_exists($this->editModel, 'after')) {
            $this->editModel->laravelEdit = $this->laravelEdit;
            return $this->editModel->after();
        }
        return $this;
    }

    private function exceptionService($table, $values, $attribute, $create = false)
    {
        $table = $this->hidden($table);

        $this->laravelEdit = ($this->laravelEdit)?: new LaravelEdit;
        $this->laravelEdit->table = $table;
        $this->laravelEdit->values = $values;
        $this->laravelEdit->attribute = $attribute;
        $this->laravelEdit->create = $create;

        if (method_exists($table, 'exception')) {
            $table->laravelEdit = $this->laravelEdit;
            $table->exception();
        } elseif ($this->editModel && method_exists($this->editModel, 'exception')) {
            $this->editModel->laravelEdit = $this->laravelEdit;
            $this->editModel->exception();
        } else return false;
        return true;
    }

    /**
     * Substitui o valor pelo valor tratado caso exista essa função.
     *
     * @param  Model  $table
     * @param  array  $values
     * @param  array  $keysEdit
     * @return mixed
     */
    private function treatmentService($table, $values, $keysEdit)
    {
        $table = $this->hidden($table);

        $this->laravelEdit = ($this->laravelEdit)?: new LaravelEdit;
        $this->laravelEdit->table = $table;
        $this->laravelEdit->values = $values;
        $this->laravelEdit->keysEdit = $keysEdit;

        if (method_exists($table, 'treatment')) {
            $table->laravelEdit = $this->laravelEdit;
            $table->treatment();
        } elseif ($this->editModel && method_exists($this->editModel, 'treatment')) {
            $this->editModel->laravelEdit = $this->laravelEdit;
            $this->editModel->treatment();
        } else return false;
        return true;
    }

    private function hidden($table)
    {
        $hidden = $table->getHidden() ?: [];
        $table->setHidden(array_merge($hidden, ['laravelEdit']));
        return $table;
    }

    private function save($table)
    {
        if (count($table->toArray())>0) {
            $teste = clone $table;
            unset($teste['laravelEdit']);
            unset($teste->laravelEdit);
            $teste->save();
        }
    }
}

class LaravelEdit {}
