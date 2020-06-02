<?php

return [
    'target_php_version' => '7.0',
    'directory_list' => [
        'common/',
        'webwinkelkeur/',
        'trustprofile/',
        'www/classes/',
        'stubs/',
    ],
    'exclude_analysis_directory_list' => [
        'common/templates/',
        'www/classes/',
        'stubs/',
    ],
    'exclude_file_regex' => '~/common/~',
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
    ],
];
