<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    dd($context);
    /*dump($_SERVER['REMOTE_ADDR']);*/
    if($context)
    {
        $context['APP_ENV'] = 'dev';
        $context['APP_DEBUG'] = true;
    }
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
