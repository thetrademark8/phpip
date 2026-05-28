<?php

arch('controllers are suffixed and have no invoke method')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller')
    ->not->toHaveMethod('__invoke')
    ->ignoring('App\Http\Controllers\Api')
    ->group('arch');

arch('no debugging statements are left in the codebase')
    ->expect(['dd', 'dump', 'var_dump', 'ray', 'print_r'])
    ->not->toBeUsed()
    ->group('arch');

arch('models live in the models namespace')
    ->expect('App\Models')
    ->toBeClasses()
    ->group('arch');
