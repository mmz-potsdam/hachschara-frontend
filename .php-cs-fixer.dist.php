<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'config',
        'var',
    ])
    ->notPath('tests/bootstrap.php')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,
        '@PHP82Migration' => true,
        'control_structure_continuation_position' => ['position' => 'next_line'],
        'elseif' => false, // don't change else if to elseif
        'operator_linebreak' => ['only_booleans' => true],
        'modifier_keywords' => ['elements' =>
            // disable changing var into public
            [/*'const', 'method', 'property'*/]
        ],
    ])
    ->setFinder($finder)
;
