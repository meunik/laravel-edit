<?php

namespace App\Service\Editar;

use Src\EditService;

class Edit
{
    /**
     * Stores the EditService class.
     * @var EditService
     */
    private $editService;

    /**
     * Enables the possibility of creating objects in multiple relationships.
     * @var Bool
     */
    protected $createMissingObjectInObjectArrays = false;

    /**
     * Enables the possibility of deleting objects in multiple relationships.
     * @var Bool
     */
    protected $deleteMissingObjectInObjectArrays = false;

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

    /**
     * Enable treatments before the update.
     * @var String|Bool
     */
    protected $before = false;

    /**
     * Enable treatments after update.
     * @var String|Bool
     */
    protected $after = false;

    /**
     * Enables exception handling.
     * @var String|Bool
     */
    protected $exception = false;


    public function __construct() {
        $this->newEditService();
        $this->attributes();
    }

    /**
     * Chama a classe EditService.
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
        $this->createMissingObjectInObjectArrays();
        $this->deleteMissingObjectInObjectArrays();
        $this->columnsCannotChange_defaults();
        $this->relationshipsCannotChangeCameCase_defaults();
        $this->beforeAfter();
        $this->exception();
    }
    private function createMissingObjectInObjectArrays()
    {
        $this->editService->createMissingObjectInObjectArrays = $this->createMissingObjectInObjectArrays;
    }
    private function deleteMissingObjectInObjectArrays()
    {
        $this->editService->deleteMissingObjectInObjectArrays = $this->deleteMissingObjectInObjectArrays;
    }
    private function columnsCannotChange_defaults()
    {
        $this->editService->columnsCannotChange_defaults = array_unique(array_merge($this->columnsCannotChange_defaults, $this->editService->columnsCannotChange_defaults));
    }
    private function relationshipsCannotChangeCameCase_defaults()
    {
        $this->editService->relationshipsCannotChangeCameCase_defaults = array_unique(array_merge($this->relationshipsCannotChangeCameCase_defaults, $this->editService->relationshipsCannotChangeCameCase_defaults));
    }
    private function beforeAfter()
    {
        $this->editService->before = $this->before;
        $this->editService->after = $this->after;
    }

    private function exception()
    {
        $this->editService->exception = $this->exception;
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
