<?php

$header = <<<'EOF'
Copyright (c) 2009 - 2010, SoftLayer Technologies, Inc. All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

 * Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.
 * Neither SoftLayer Technologies, Inc. nor the names of its contributors may
   be used to endorse or promote products derived from this software without
   specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
EOF;

// Use PHP-CS-Fixer 2+ if it is available
if (\class_exists('PhpCsFixer\Config', false)) {
    return PhpCsFixer\Config::create()
        ->setUsingCache(true)
        ->setRiskyAllowed(true)
        ->setRules(array(
            '@Symfony' => true,
            'array_syntax' => array('syntax' => 'long'),
            'binary_operator_spaces' => array(
                'align_double_arrow' => false,
                'align_equals' => false,
            ),
            'blank_line_after_opening_tag' => true,
            'header_comment' => array('header' => $header),
            'ordered_imports' => true,
            'php_unit_construct' => true,
        ))
        ->setFinder(
            PhpCsFixer\Finder::create()->in(__DIR__)
        )
    ;
}

Symfony\CS\Fixer\Contrib\HeaderCommentFixer::setHeader($header);

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    ->fixers(array(
        'newline_after_open_tag',
        'ordered_use',
        'php_unit_construct',
        'long_array_syntax',
        'unalign_double_arrow',
        'unalign_equals',
    ))
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__)
    )
;
