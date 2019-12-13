<?php
    /*
    Released under the 3-Clause BSD License:

    Copyright 2010-2019 Matthew Hagerty (matthew <at> dnotq <dot> io)

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
    this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
    notice, this list of conditions and the following disclaimer in the
    documentation and/or other materials provided with the distribution.

    3. Neither the name of the copyright holder nor the names of its
    contributors may be used to endorse or promote products derived from this
    software without specific prior written permission.

    THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
    AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
    IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
    ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
    LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
    CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.

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
