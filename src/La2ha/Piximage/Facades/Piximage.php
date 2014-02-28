<?php namespace La2ha\Piximage\Facades;

use Illuminate\Support\Facades\Facade;

class Piximage extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'piximage';
    }

}