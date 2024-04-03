<?php

namespace Meunik\Edit;

trait FunctionsFromModel
{
    private function hidden($table)
    {
        $hidden = $table->getHidden() ?: [];
        $table->setHidden(array_merge($hidden, ['laravelEdit']));
        return $table;
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
            return $table->exception();
        } elseif ($this->editModel && method_exists($this->editModel, 'exception')) {
            $this->editModel->laravelEdit = $this->laravelEdit;
            return $this->editModel->exception();
        } else return false;

        return true;
    }

    private static function validReturnValuesEdit($valuesEdit)
    {
        if (!is_array($valuesEdit)) self::error("The 'valuesEdit()' function should return an array with the values that will be registered", 404);

        return $valuesEdit;
    }

    /**
     * Replaces the value with the treated value if such a function exists.
     *
     * @param  Model  $table
     * @param  array  $values
     * @param  array  $keysEdit
     * @return mixed
     */
    private function valuesEditService($table, $values, $keysEdit)
    {
        $table = $this->hidden($table);

        $this->laravelEdit = ($this->laravelEdit)?: new LaravelEdit;
        $this->laravelEdit->table = $table;
        $this->laravelEdit->values = $values;
        $this->laravelEdit->keysEdit = $keysEdit;

        if (method_exists($table, 'valuesEdit')) {
            $table->laravelEdit = $this->laravelEdit;
            return self::validReturnValuesEdit($table->valuesEdit());

        } elseif ($this->editModel && method_exists($this->editModel, 'valuesEdit')) {
            $this->editModel->laravelEdit = $this->laravelEdit;
            return self::validReturnValuesEdit($table->valuesEdit());
        }

        return false;
    }
}

class LaravelEdit {}
