<?php

namespace Meunik\Edit;

use Meunik\Edit\Edit;

trait HasEdit
{
    public static function edit($values=null)
    {
        return new Edit(get_called_class(), $values);
    }
}
