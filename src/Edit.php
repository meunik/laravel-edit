<?php

namespace Meunik\Edit;

use Meunik\Edit\EditService;

class Edit
{
    /**
     * Stores the EditService class.
     * @var EditService
     */
    private $editService;

    /**
     * Stores the Model class.
     * @var Model
     */
    protected $model;

    /**
     * Stores the EditModel class.
     * @var EditModel
     */
    protected $editModel;

    /**
     * Enables the possibility of deleting objects in multiple relationships.
     * @var Bool
     */
    protected $deleteMissingObjectInObjectArrays = true;

    /**
     * Defines columns that cannot be changed by default.
     * @var Array
     */
    protected $columnsCannotChange_defaults = [];

    /**
     * Defines relationships that cannot be changed by default.
     * @var Array
     */
    protected $relationshipsCannotChangeCameCase_defaults = [];

    public $laravelEdit;


    public function __construct($model=null, $values=null) {
        $this->newEditService();
        if ($model) $this->model($model);
        $this->attributes();
        if ($values) $this->editService->values = is_object($values) ? $values->toArray() : $values;
    }

    /**
     * Stores the Model class in a variable.
     * @return void
     */
    private function model($model)
    {
        $this->model = new $model();
        $this->editService->model = new $model();
        $editModel = null;
        if ($this->model->editModel) $editModel = $this->model->editModel;
        elseif (class_exists(\App\Models\EditModel::class)) $editModel = \App\Models\EditModel::class;
        if ($editModel) {
            $this->editModel = new $editModel();
            $this->editService->editModel = new $editModel();
        }
    }

    /**
     * Stores the EditService class in a variable.
     * @return void
     */
    private function newEditService()
    {
        $this->editService = new EditService();
    }

    /**
     * Defines attributes in the EditService class.
     * @return void
     */
    private function attributes()
    {
        if (!$this->editModel) return null;
        $this->deleteMissingObjectInObjectArrays();
        $this->columnsCannotChange_defaults();
        $this->relationshipsCannotChangeCameCase_defaults();
    }
    private function deleteMissingObjectInObjectArrays()
    {
        if (isset($this->editModel->deleteMissingObjectInObjectArrays))
            $this->editService->deleteMissingObjectInObjectArrays = $this->editModel->deleteMissingObjectInObjectArrays;
    }
    private function columnsCannotChange_defaults()
    {
        if (isset($this->editModel->columnsCannotChange_defaults))
            $this->editService->columnsCannotChange_defaults = array_unique(array_merge(
                $this->editModel->columnsCannotChange_defaults,
                $this->editService->columnsCannotChange_defaults
            ));
    }
    private function relationshipsCannotChangeCameCase_defaults()
    {
        if (isset($this->editModel->relationshipsCannotChangeCameCase_defaults))
            $this->editService->relationshipsCannotChangeCameCase_defaults = array_unique(array_merge(
                $this->relationshipsCannotChangeCameCase_defaults, $this->editService->relationshipsCannotChangeCameCase_defaults
            ));
    }


    /**
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * * * * * * * * * * * * *   __CALL    * * * * * * * * * * * * *
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
    */

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return $this->$method(...$parameters);
        }

        return $this->editService->$method(...$parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return (new static)->$method(...$parameters);
    }
}
