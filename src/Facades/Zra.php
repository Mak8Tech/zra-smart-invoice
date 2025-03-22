<?php

namespace Mak8Tech\ZraSmartInvoice\Facades;

use Illuminate\Support\Facades\Facade;

class Zra extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'zra';
    }
}
