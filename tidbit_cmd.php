<?php
    /*
    The MIT License (MIT)

    Copyright (c) 2010 Matthew Hagerty

    Permission is hereby granted, free of charge, to any person obtaining a
    copy of this software and associated documentation files (the "Software"),
    to deal in the Software without restriction, including without limitation
    the rights to use, copy, modify, merge, publish, distribute, sublicense,
    and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
    DEALINGS IN THE SOFTWARE.

    TI Basic Translator (TIdBiT)

    Matthew Hagerty
    https://dnotq.io
    */
    if ( $argc < 4 )
    {
        print "\nUsage: " . $argv[0] . " input_file start inc [output_file]\n\n";
        exit;
    }

    if ( ($code = file_get_contents($argv[1])) === FALSE )
    {
        print "\nCannot read input file: " . $argv[1] . "\n\n";
        exit;
    }

    $line_num = 100;
    if ( $argv[2] > 0 && $argv[2] <= 32767 )
        $line_num = $argv[2];

    $line_inc = 10;
    if ( $argv[3] > 0 && $argv[3] <= 1000 )
        $line_inc = $argv[3];

    require_once 'tidbit.php';
    $t = new tidbit();
    $code_out = $t->translate($code, $line_num, $line_inc);

    if ( $argc == 4 )
        print $code_out . "\n";
    else
    {
        print "Writing code to: $argv[4]\n";
        file_put_contents($argv[4], $code_out);
    }
