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
