<?php

namespace Meunik\Edit;

trait Core
{
    private function save($table)
    {
        if (count($table->toArray())>0) {
            $clone = clone $table;
            unset($clone['laravelEdit']);
            unset($clone->laravelEdit);
            $clone->save();
        }
    }

    private function deleteMissingObjectInObjectArrays($table, $relationship, $key, $object)
    {
        if ($this->deleteMissingObjectInObjectArrays) {
            if (isset($object['pivot'])) $object['pivot']->delete();
            $object->delete();
            unset($table[$relationship][$key]);
        }
    }

    private function relationshipsList($table, $values)
    {
        $tableRelationship = $table->relationship;
        $arrayKeys = ($tableRelationship) ? array_keys($tableRelationship) : [];

        $relationships = [];
        foreach ($values as $key => $value) {
            $key = self::snake_caseToCamelCase($key);
            if ((!in_array($key, $this->columnsCannotChange_defaults)) && (!in_array($key, $this->relationshipsCannotChangeCameCase_defaults)) && (in_array($key, $arrayKeys))) {
                $relationships[$key] = $tableRelationship[$key];
            }
        }
        return $relationships;
    }

    private function removeRelationships(Array $table, $relationships)
    {
        if ($relationships)
            foreach ($relationships as $key => $item) {
                $key = self::camelCaseToSnake_case($key);
                if (array_key_exists($key, $table)) unset($table[$key]);
            }
        return $table;
    }

    private function removeColumnsCannotChange($table, $ignoreds)
    {
        foreach ($this->columnsCannotChange_defaults as $item)
            if (array_key_exists($item, $table)) unset($table[$item]);

        foreach ($ignoreds as $item)
            if (array_key_exists($item, $table)) unset($table[$item]);

        return $table;
    }

    private function clean($values, $tableRelationships, $ignoreds)
    {
        $keysEdit = $this->removeRelationships($values, $tableRelationships);
        $keysEdit = $this->removeColumnsCannotChange($keysEdit, $ignoreds);
        return array_keys($keysEdit);
    }

    private function is_multi($relationshipValue) {
        return is_array($relationshipValue);
    }

    /**
     * @param  Model  $table new values
     * @param  array  $values old values
     * @param  array  $relationships Relationship
     * @return mixed
     */
    private function arrayObjects($table, $values, $relationship)
    {
        $camelCase = self::snake_caseToCamelCase($relationship);

        if (count($table->toArray())<=0) return false;

        $tableCollection = collect($table[$camelCase]);
        $valuesCollection = collect($values[$relationship]);

        $tableRelationship = $table->relationship;
        if (is_null($tableRelationship)) self::error("Parameter 'relationship' not found in the model");

        $tableRelationshipModel = new $tableRelationship[$camelCase][0];

        $keyName = $tableRelationshipModel->getKeyName();

        if (!is_null($table[$camelCase])) {
            foreach ($table[$camelCase] as $key => $object) {
                if ($valuesCollection->contains($keyName, $object[$keyName]) == false)
                    $this->deleteMissingObjectInObjectArrays($table, $relationship, $key, $object);

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

            try {
                /**
                 * This code attaches new records to the pivot table for each appended field in the $create model that is not in the ignored columns list.
                 */
                if ($create->getAppends())
                    foreach ($create->getAppends() as $value)
                        if (array_key_exists($value, $table->pivot->getOriginal()) && !in_array($value, $ignoredColumns))
                            $table->$camelCase()->attach($create->id, [$value => $object[$value]]);

                else $table->$camelCase()->attach($create->id);
            } catch (\Throwable $th) {
                $table->$camelCase()->attach($create->id);
            }
        }
    }

    /**
     * @param  Model  $table new values
     * @param  array  $values old values
     * @param  array  $relationships Relationship
     * @return mixed
     */
    private function relationships($table, $values, $relationships)
    {
        if (count($relationships) == 0) return $table;

        foreach ($relationships as $key => $value) {

            $key = self::camelCaseToSnake_case($key);

            $exception = $this->exceptionService($table, $values, $key);
            if ($exception) continue;

            $camelCase = self::snake_caseToCamelCase($key);

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

    /**
     * @param  Model  $table
     * @param  array  $values
     * @return mixed
     */
    private function ignoreds($table, array $values)
    {
        $ignoredColumns = $table->ignoredColumns ?: [];
        $ignoredRelationships = $table->ignoredRelationships ?: [];
        $hidden = $table->getHidden() ?: [];
        try {
            $appends = $table->getAppends() ?: [];
        } catch (\Throwable $th) { $appends = []; }

        if ($table->pivot) {
            // Edit appends in pivot table
            foreach ($appends as $value) {
                if ($table->editAppends && array_key_exists($value, $table->pivot->getOriginal()) && !in_array($value, $ignoredColumns)) {
                    $table->pivot->$value = $values[$value];
                    $table->pivot->save();
                }
            }
        }

        $fillable = $table->getFillable() ?: [];
        foreach ($values as $coll => $item)
            if (!in_array($coll, $fillable) && !in_array($coll, $ignoredColumns))
                $ignoredColumnsNotInFillable[] = $coll;

        return array_merge($ignoredColumns, $ignoredRelationships, $appends, $hidden, $ignoredColumnsNotInFillable);
    }

    /**
     * Recurcive function, responsible for FACT EDITING.
     *
     * @param  Model  $table
     * @param  array  $values
     * @return mixed
     */
    private function update($table, $values)
    {
        $relationships = $this->relationshipsList($table, $values);
        $ignoreds = $this->ignoreds($table, $values);
        $keysEdit = $this->clean($values, $relationships, $ignoreds);

        $before = $this->beforeService($table, $values);

        $valuesEdit = ($this->valuesEditService($table, $values, $keysEdit))?:$values;

        // FACT EDITING
        foreach ($keysEdit as $item) {
            $exception = $this->exceptionService($table, $values, $item);
            if ($exception) continue;

            if ($this->date($table[$item]) != $valuesEdit[$item]) $table[$item] = $valuesEdit[$item];
        }

        $this->save($table);
        $this->afterService($table, $values, $before);

        $this->relationships($table, $values, $relationships);

        return $this->table;
    }
}
