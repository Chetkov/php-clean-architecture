<?php

declare(strict_types=1);

return [
    'target_php_version' => '7.4',
    'minimum_target_php_version' => '7.4',
    'directory_list' => [
        'bin',
        'src',
        'vendor',
    ],
    'exclude_analysis_directory_list' => [
        'vendor',
    ],
    'analyzed_file_extensions' => [
        'php',
    ],
    'allow_missing_properties' => false,
    'null_casts_as_any_type' => false,
    'null_casts_as_array' => false,
    'array_casts_as_null' => false,
    'scalar_implicit_cast' => false,
    'scalar_array_key_cast' => false,
    'strict_method_checking' => true,
    'strict_object_checking' => true,
    'strict_param_checking' => true,
    'strict_property_checking' => true,
    'strict_return_checking' => true,
    'dead_code_detection' => false,
    'unused_variable_detection' => true,
    'redundant_condition_detection' => true,
    'quick_mode' => false,
    'suppress_issue_types' => [
        'PhanPossiblyFalseTypeReturn',
        'PhanUnusedVariableCaughtException',
    ],
    'plugins' => [],
];
