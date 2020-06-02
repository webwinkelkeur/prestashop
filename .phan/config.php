<?php

return [
    'target_php_version' => '7.0',
    'directory_list' => [
        'common/',
        'webwinkelkeur/',
    ],
    'exclude_analysis_directory_list' => [
        'common/templates/',
    ],
    'plugins' => [
        'AlwaysReturnPlugin',
        'UnreachableCodePlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
    ],
];
