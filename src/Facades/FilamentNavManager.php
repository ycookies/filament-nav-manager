<?php

namespace Ycookies\FilamentNavManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Ycookies\FilamentNavManager\FilamentNavManager
 */
class FilamentNavManager extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Ycookies\FilamentNavManager\FilamentNavManager::class;
    }
}
