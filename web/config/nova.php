<?php

return [
    'local_only' => filter_var(env('NOVA_LOCAL_ONLY', false), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE),
];
