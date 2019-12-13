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

    require_once 'tidbit.php';
    $t = new tidbit();

    $line_num = 100;
    $line_inc = 10;
    $code_in = "// TI BASIC Translator Example\nA=1\nLoop:\n    PRINT A\n    A=A+1\n    IF A<10 THEN Loop\n// Loop End\nEND";
    $code_out = "";

    if ( isset($_POST['form-name']) AND "tidbit_web" === $_POST['form-name'] )
    {
        if ( $_POST['line_num'] && $_POST['line_num'] > 0 && $_POST['line_num'] <= 32767 )
            $line_num = $_POST['line_num'];

        if ( $_POST['line_inc'] && $_POST['line_inc'] > 0 && $_POST['line_inc'] <= 1000 )
            $line_inc = $_POST['line_inc'];

        if ( $_POST['code_in'] && strlen($_POST['code_in']) > 0 )
        {
            $order = array("\r\n", "\n", "\r");
            $code_in = str_replace($order, "\n", $_POST['code_in']);
            $code_out = $t->translate($_POST['code_in'], $line_num, $line_inc);
        }
    }
?>
<form name="translate" method="POST" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
<input type="hidden" name="form-name" value="tidbit_web" />
<div style="text-align:left; border: 1px solid #bbb; padding:10px;">
    <div>
        <label for="line_num">Starting On</label>
        <input type="text" name="line_num" id="line_num" value="<?php echo $line_num; ?>" />
        (must be between 1 and 32767)
    </div>
    <div>
        <label for="line_inc">Increment By</label>
        <input type="text" name="line_inc" id="line_inc" value="<?php echo $line_inc; ?>" />
        (must be between 1 and 100)
    </div>
    <div>
        <label for="code_in">Code In</label>
        <textarea name="code_in" id="code_in" style="width:100%; height:300px"><?php
            print htmlspecialchars(stripslashes($code_in), ENT_QUOTES); ?></textarea>
    </div>
    <input type="submit" value="Translate" />
    <div>
        <label for="code_out">Code Out</label>
        <textarea name="code_out" id="code_out" style="width:100%; height:300px"><?php
            print htmlspecialchars(stripslashes($code_out), ENT_QUOTES); ?></textarea>
    </div>
</div>
</form>
