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

    V3.1 November 17, 2019
        * Fixed incorrect parsing of the double-colon (::) when it was used
          immediately following a label without a space.

        * Syntax cleanup and comment improvements.

    V3.0 September 13, 2016
        * Simplified parser stage.  Being less-smart reduces errors.

        * Added list of reserved words to avoid label confusion.

        * Fixed bugs in expression parsing with embedded strings.

        * Fixed string parsing bug with escaped double quotes.

        * Fixed XB ! comments that were alone on a line.

        * Fixed XB :: operator when following PRINT or DISPLAY AT with colon options.

    V2.2 September 10, 2016
        * Added more debug statements.

        * Added check to not allow the auto line number from being overwritten by a
          forced line number if the forced line number is less than the current auto
          line number.  This prevents accidental loss of code.

    V2.1 June 25, 2016
        * Removed requirement to have .. after THEN or ELSE statements, or after the
          last expression before an ELSE statement.

        * Prevent the keyword 'PRINT' from being used as a label to avoid possible
          errors due to the BASIC use of the colon with a print statement: 'PRINT: :'

              V2.0 March 16, 2015
        * Fixed using RETURN in an error context when used in an IF/THEN/ELSE statement, i.e. IF exp THEN RETURN <label>.

        * Added additional support for the other ON <statement> (BREAK, ERROR, etc.) commands that can accept a line-number list.

        * Added a version number to keep track of updates.

        * Added a readme.txt with instructions on how to use TidBit on the command line or with PHP's built-in web server.

        * Removed requirement to have .. after the XB :: operator

        * Added ENDIF pseudo statement

    V1.3 Feb 26, 2011
        * Fixed REM and ! comments.

    V1.2 February 25, 2011
        * Added the missing RESTORE, BREAK, and UNBREAK commands / statements to the list
          of tokens that support a line number or list of line numbers.

    V1.1 February 13, 2011
        * Added forced line number override.

        * Fixed label bug.

        * Fixed max line number bug.

        * Change REM statements to be included in output code.

    V1.0 November 20, 2010
        * Initial release

    */

class
tidbit
{
    const SS_START      = 1;
    const SS_IN_STRING  = 2;
    const SS_IN_EXP     = 3;
    const SS_IN_THEN    = 4;
    const SS_IN_ELSE    = 5;
    const SS_IN_GOTO    = 6;
    const SS_IN_GOSUB   = 7;
    const SS_IN_RETURN  = 8;
    const SS_IN_ERROR   = 9;
    const SS_IN_RESTORE = 10;
    const SS_IN_BREAK   = 11;
    const SS_IN_UNBREAK = 12;
    const SS_DONE       = 13;

    const MAX_LINE_NUM  = 32767;

    private $labels;
    private $code_in;
    private $head;
    private $tail;
    private $len;
    private $debug;
    private $sname = array(
            1 => 'START',
            2 => 'STRING',
            3 => 'EXP',
            4 => 'THEN',
            5 => 'ELSE',
            6 => 'GOTO',
            7 => 'GOSUB',
            8 => 'RETURN',
            9 => 'ERROR',
            10 => 'RESTORE',
            11 => 'BREAK',
            12 => 'UNBREAK'
        );
    private $reserved_words = array(
        'abs', 'accept', 'all', 'and', 'append', 'asc', 'at', 'atn',
        'base', 'beep', 'break', 'bye',
        'call', 'chr$', 'close', 'con', 'continue', 'cos',
        'data', 'def', 'delete', 'digit', 'dim', 'display',
        'else', 'end', 'eof', 'erase', 'error', 'exp',
        'fixed', 'for',
        'go', 'gosub', 'goto',
        'if', 'image', 'input', 'int', 'internal',
        'len', 'let', 'linput', 'list', 'log',
        'max', 'merge', 'min',
        'new', 'next', 'not', 'num', 'number', 'numeric',
        'old', 'on', 'open', 'option', 'or', 'output',
        'permanent', 'pi', 'pos', 'print',
        'randomize', 'read', 'rec', 'relative', 'rem', 'res', 'resequence', 'restore', 'return', 'rnd', 'rpt$', 'run',
        'save', 'seg$', 'sequential', 'sgn', 'sin', 'size', 'sqr', 'step', 'stop', 'str$', 'sub', 'subend', 'subexit',
        'tab', 'tan', 'then', 'to', 'trace',
        'ualpha', 'unbreak', 'untrace', 'update', 'using',
        'val', 'validate', 'variable',
        'warning',
        'xor'
        );


    /**
     * Constructor
     */
    public function
    __construct($debug=false)
    {
        $this->debug = $debug;
    }
    // __construct()


    private function
    dbg($text)
    {
        if ( $this->debug == True ) print "$text\n";
    }
    // dbg()


    private function
    getline()
    {
        $line = FALSE;

        if ( $this->tail >= $this->len )
            return $line;

        // Find the next end of line.  CR, LF, CRLF
        while ( $this->tail < $this->len )
        {
            // Check for a newline.
            if ( $this->code_in[$this->tail] == "\r" || $this->code_in[$this->tail] == "\n" )
            {
                // Don't include the newline.
                $line = substr($this->code_in, $this->head, $this->tail - $this->head);

                // Check for CRLF and increment the tail.
                if ( $this->code_in[$this->tail] == "\r" && ($this->tail + 1) < $this->len &&
                $this->code_in[$this->tail + 1] == "\n" )
                    $this->tail++;

                // Skip the newline and adjust the head.
                $this->tail++;
                $this->head = $this->tail;
                break;
            }

            // Convert any white space characters to a space.
            else if ( $this->isspace($this->code_in[$this->tail]) )
                $this->code_in[$this->tail] = ' ';

            $this->tail++;
        }

        // Catch the end of data condition that does not end with a newline.
        if ( $this->head < $this->tail )
        {
            $line = substr($this->code_in, $this->head, $this->tail - $this->head);
            $this->head = $this->tail;
        }

        return $line;
    }


    /**
     * Translate the code.
     *
     * @param[in] $code_in      Input code
     * @param[in] $line_num     Starting line number
     * @param[in] $line_inc     Line number increment
     */
    public function
    translate($code_in, $line_num = 100, $line_inc = 10)
    {
        if ( $line_num < 1 || $line_num > self::MAX_LINE_NUM )
            $line_num = 100;

        if ( $line_inc < 1 || $line_inc > 100 )
            $line_inc = 10;

        $this->code_in = $code_in;
        $this->len = strlen($code_in);
        $this->head = 0;
        $this->tail = 0;

        $code_out = '';
        $code = array();
        $this->labels = array();
        $fixup = array();
        $lines = 0;
        $proc_inc = 1000;
        $cont = '';
        $lookahead = '';
        $trailing = '';
        $max_line_len = 140;

        $this->dbg("\nPass 1, find all labels and assign line numbers\n");

        // Pass 1, find all labels and assign line numbers.
        $nextline = $this->getline();
        while ( ($buf = $nextline) !== FALSE )
        {
            $lines++;
            $buf = trim($buf);

            while ( TRUE )
            {
                $nextline = $this->getline();
                if ( $nextline === FALSE ) { $lookahead = ''; break; }

                // The look-ahead needs to skip blank lines, lines that start with //,
                // or lines that only contain pseudo tokens (ENDIF)
                $lookahead = trim($nextline);
                if ( $lookahead == '' || substr($lookahead, 0, 2) == '//' || strtoupper($lookahead) == 'ENDIF' )
                {
                    $lines++;
                    $this->dbg("Look-ahead skipping blank line, comment, or pseudo token.\n");
                    continue;
                }

                break;
            }


            // Skip blank lines, lines that start with //, or lines that only
            // contain pseudo tokens (ENDIF)
            if ( $buf == '' || substr($buf, 0, 2) == '//' || strtoupper($buf) == 'ENDIF' )
            {
                $this->dbg("Skipping blank line, comment, or pseudo token.\n");
                continue;
            }


            // If a line starts with a line number, remove it and override the current
            // line number *only* if the override line number is greater than or equal
            // to the current line number.  This prevents accidentally overwriting code.
            if ( preg_match('/^([0-9]+)(.*)/', $buf, $a_buf) == 1 )
            {
                // If there is continuation text (multiple lines being joined), do not
                // over-ride the line number counter, but do strip the line number from
                // the input.
                if ( $cont == '' )
                {
                    if ( (int)$a_buf[1] >= $line_num )
                    {
                        $this->dbg("Overriding line number: [$line_num] with [" . (int)$a_buf[1] . "]\n");
                        $line_num = (int)$a_buf[1];
                    }

                    else
                        $this->dbg("Ignoring line number override: [" . (int)$a_buf[1] . "], " .
                                    "keeping line number: [$line_num]\n");
                }

                $buf = trim($a_buf[2]);
            }

            // Blank the label and check for REM before anything else.
            $label = '';

            // Retain REM lines without further processing.
            if ( substr($buf, 0, 3) != "REM" || (strlen($buf) > 3 && $buf[3] != ' ') )
            {
                // Skip joining lines if there is an ! outside of a string,
                // which is an XB trailing line remark.
                $in_string = FALSE;
                $len = strlen($buf);
                for ( $i = 0 ; $i < $len ; $i++ )
                {
                    // Check if in a string, being careful to support double quote escape ""
                    if ( $buf[$i] == '"' )
                    {
                        if ( $in_string == FALSE )
                            $in_string = TRUE;

                        // If escaped doubled quote, skip the second quote and continue.
                        else if ( ($i+1) < $len && $buf[$i+1] == '"' )
                            $i++;

                        else
                            $in_string = FALSE;

                        continue;
                    }

                    if ( $buf[$i] == '!' && $in_string == FALSE )
                    {
                        // Strip and save the trailing remarks.
                        if ( $trailing == '' )
                            $trailing = ($i > 0 && $buf[$i - 1] == ' ') ? ' !' : '!';

                        $trailing .= substr($buf, $i + 1);
                        $buf = trim(substr($buf, 0, $i));
                        break;
                    }

                    // Ignore anything on a line after a //
                    if ( $buf[$i] == '/' && $in_string == FALSE && ($i + 1) < $len && $buf[$i+1] == '/' )
                    {
                        // Strip the trailing // comment.
                        $buf = trim(substr($buf, 0, $i));
                        break;
                    }
                }

                // Combine lines that end with tidbit's special continuation operator ..,
                // XB's multi-statement operator ::, an IF statement's THEN or ELSE, or
                // where the next line is an ELSE statement.
                if ( substr($buf, -2) == '..' )
                {
                    // Discard the ..
                    $cont .= substr($buf, 0, -2);
                    $this->dbg("Continuation text: [$cont]\n");
                    continue;
                }

                if ( substr($buf, -2) == '::' ||
                strtoupper(substr($buf, -4)) == 'THEN' ||
                strtoupper(substr($buf, -4)) == 'ELSE' ||
                (strlen($lookahead) == 4 && strtoupper(substr($lookahead, 0, 4)) == 'ELSE') ||
                (strlen($lookahead) > 4  && strtoupper(substr($lookahead, 0, 5)) == 'ELSE ') )
                {
                    // Retain the ::, THEN, ELSE, or add a space for the next line.
                    $cont .= $buf . ' ';
                    $this->dbg("Continuation text: [$cont]\n");
                    continue;
                }

                if ( $cont != '' )
                {
                    $buf = $cont . $buf;
                    $cont = '';
                    $this->dbg("Found line: [$buf]\n");
                }
            }


            // See if a line starts a label.  If a double colon is found, this is
            // NOT a label.
            if (
            preg_match('/^([A-Za-z0-9_-]+:)[^:].*/', $buf, $a_buf) == 1 ||
            preg_match('/^([A-Za-z0-9_-]+:)$/', $buf, $a_buf) == 1 )
            {
                // The $label var is used below to see if the label is alone
                // on the line.
                $label = $a_buf[1];
                $idx = substr($a_buf[1], 0, -1);

                if ( isset($this->labels[$idx]) )
                {
                    $this->dbg("WARNING: Ignoring duplicate label [$label] " .
                                "on line [$lines]\n\n");
                    $label = '';
                }

                // Silently ignore labels that are reserved words.  This will ignore
                // the valid PRINT: statement which has the syntax of a label.
                elseif ( !in_array(strtolower($idx), $this->reserved_words) )
                {
                    $this->labels[$idx] = $line_num;
                    $this->dbg("Label: [$idx] on line: $line_num\n");
                }

                else
                {
                    $this->dbg("WARNING: Ignoring reserved word label [$label] " .
                                "on line [$lines]\n\n");
                    $label = '';
                }
            }

            // If the label was alone on a line, store the label name to have its
            // line number adjusted by the first line with some code on it.  This
            // was added when the line number override was added, since a line
            // following a label could force a line number, the label could end up
            // having a non-existent line number reference.
            if ( $label != '' && $buf == $label )
            {
                $fixup[] = substr($a_buf[1], 0, -1);
                $this->dbg("Needs fixup: [" . substr($a_buf[1], 0, -1) . "]\n");
            }

            // Save the line if the label is not the only text on the line.
            else if ( $buf != $label || $trailing != '' )
            {
                // Fix up any empty labels that preceded this line.
                foreach ( $fixup as $idx )
                {
                    $this->labels[$idx] = $line_num;
                    $this->dbg("Fixup: [$idx] = $line_num\n");
                }

                $fixup = array();

                // Remove any label.
                if ( $label != '' )
                    $buf = trim(substr($buf, strlen($label)));

                // Re-append any ! comment.
                $buf .= $trailing;
                $trailing = '';

                $len = strlen($line_num . $buf);
                if ( $len > $max_line_len )
                {
                    $this->dbg("WARNING: Source line [$lines], BASIC line [$line_num] " .
                        "is too long: [$len]\n");
                }

                $this->dbg("Code: $line_num $buf\n");

                $code[$line_num] = $buf;
                $line_num += $line_inc;

                if ( $line_num > self::MAX_LINE_NUM )
                {
                    $code_out = "FATAL ERROR: line number exceeds maximum of " . self::MAX_LINE_NUM . "\n";
                    $code = array();
                    break;
                }
            }
        }

        $this->dbg("\nLabels:\n");
        $this->dbg(print_r($this->labels, TRUE));

        $this->dbg("\n\nProgram Code:\n");
        $this->dbg(print_r($code, TRUE));

        $this->dbg("\nPass 2, replace labels with line numbers...\n");

        // Pass 2, replace labels with line numbers.
        foreach ( $code as $linenum => $buf )
        {
            // Look for statements that use line numbers:
            // THEN, ELSE, GOTO, GOSUB, ERROR
            // Make sure these are NOT in a string.
            $line = $this->parse($buf, $linenum);
            $this->dbg("$linenum $line\n");
            $code_out .= "$linenum $line\n";
        }

        return $code_out;
    }
    // translate()


    /**
     * Parse a line and replace labels with line numbers.
     */
    private function
    parse($buf, $linenum)
    {
        // Add a space to $buf to make EOL parsing easier.
        $buf .= ' ';
        $len = strlen($buf);
        $tok = '';
        $newbuf = '';
        $state = self::SS_START;
        $state_next = self::SS_START;
        $depth = 0;

        // Scan each character and detect tokens.
        // $i = current character
        // $j = token head
        for ( $i = 0, $j = 0 ; $i < $len ; $i++ )
        {
            // Get the current and next character.
            $c = $buf[$i];
            if ( $i + 1 < $len ) { $n = $buf[$i + 1]; }
            else                 { $n = ''; }

            if ( $c == ":" && $n == ":" ) { $multi_tok = TRUE; }
            else                          { $multi_tok = FALSE; }

            // Determine the token.
            switch ( $state )
            {
            case self::SS_START :

                // Skip trailing spaces.
                if ( $this->isspace($c) && $i == $j ) {
                    $j++;
                }

                // Capture and ignore to the end of the line.
                else if ( $c == '!' ) {
                    $i = $len - 2;
                }

                else if ( $c == '"' ) {
                    $state = self::SS_IN_STRING;
                }

                // Check for expressions or functions, and skip them.
                else if ( $c == '(' )
                {
                    $state = self::SS_IN_EXP;
                    $depth = 1;
                }

                // Check for the double colon for multiple statements on a line.
                else if ( $multi_tok == TRUE && $i == $j )
                {
                    $this->dbg("MULTI-TOKEN: [::]");

                    // Trim the buffer before adding the "::", unless the buffer
                    // ends with a colon.  This is to support a PRINT : : ::
                    // statement, which is valid.
                    $newbuf = trim($newbuf);
                    if ( substr($newbuf, -1) == ':' ) { $newbuf .= ' '; }
                    $newbuf .= '::';
                    $i++;
                    $j = $i + 1;
                }

                // Spaces appear between tokens and a final space was added to the
                // input buffer as a sentinel.
                else if ( $this->isspace($c) || $c == ',' || $multi_tok == TRUE )
                {
                    $tok = substr($buf, $j, $i - $j);
                    $save = TRUE;
                    $utok = strtoupper($tok);

                    // If a multi-statement token (::) is the separator, push
                    // the first colon character back so it will be processes as
                    // part of the multi-statement token on the next pass.
                    if ( $multi_tok == TRUE ) {
                        $c = '';
                        if ( $i > 0 ) { $i--; }
                    }

                    // Add support for, and skip, an ENDIF token.
                    if ( $utok == 'ENDIF' ) { $save = FALSE; }

                    // Check for a reserved word.
                    // If the token is a label, replace it.
                    if ( isset($this->labels[$tok]) )
                    {
                        $newbuf .= $this->labels[$tok] . $c;
                        $save = FALSE;
                        $this->dbg("LABEL      : [$tok]");
                    }

                    else {
                        $this->dbg("LITERAL    : [$tok]");
                    }

                    if ( $save == TRUE ) { $newbuf .= $tok . $c; }
                    $j = $i + 1;
                }

                break;

            case self::SS_IN_STRING :

                if ( $c == '"' )
                {
                    // If double "", skip both and stay in the string.
                    if  ( $n == $c ) {
                        $i++;
                    }
                    else
                    {
                        if ( $state_next == self::SS_START )
                            $this->dbg("STRING: " . substr($buf, $j, $i - $j + 1));

                        $state = $state_next;
                        $state_next = self::SS_START;
                    }
                }

                break;

            case self::SS_IN_EXP :

                // Watch for and ignore strings inside expressions or function calls.
                if ( $c == '"' )
                {
                    $state = self::SS_IN_STRING;
                    // Return to this state once the string is complete.
                    $state_next = self::SS_IN_EXP;
                }

                // Count the open and close parens.  Exit state when the count is 0.
                else if ( $c == '(' ) { $depth++; }
                else if ( $c == ')' ) { $depth--; }

                if ( $depth == 0 )
                {
                    $this->dbg("EXPRESSION: " . substr($buf, $j, $i - $j + 1));
                    $state = self::SS_START;
                    $newbuf .= substr($buf, $j, $i - $j + 1);
                    if ( $n == ' ' ) $newbuf .= $n;
                    $j = $i + 1;
                }

                break;

            default :
                print "Scanner bug!\n\n";
                exit;
            }
        }

        return trim($newbuf);
    }
    // parse()


    /* Character testing functions.
     * Converted to PHP from John Millaway's <ctype.h> file.
     *
     * Note: These functions expect a character,
     * such as 'a', or '?', not an integer.
     * If you want to use integers, first convert
     * the integer using the chr() function.
     *
     * Examples:
     *
     * isalpha('a'); // returns 1
     * isalpha(chr(97)); // same thing
     *
     * isdigit(1); // NO!
     * isdigit('1'); // yes.
     */
    private function isalnum ($c){ return ((($this->ctype__[( ord($c) )]&(01 | 02 | 04 )) != 0)?1:0);}
    private function isalpha ($c){ return ((($this->ctype__[( ord($c) )]&(01 | 02 )) != 0)?1:0);}
    private function isascii ($c){ return (((( ord($c) )<=0177) != 0)?1:0);}
    private function iscntrl ($c){ return ((($this->ctype__[( ord($c) )]& 040 ) != 0)?1:0);}
    private function isdigit ($c){ return ((($this->ctype__[( ord($c) )]& 04 ) != 0)?1:0);}
    private function isgraph ($c){ return ((($this->ctype__[( ord($c) )]&(020 | 01 | 02 | 04 )) != 0)?1:0);}
    private function islower ($c){ return ((($this->ctype__[( ord($c) )]& 02 ) != 0)?1:0);}
    private function isprint ($c){ return ((($this->ctype__[( ord($c) )]&(020 | 01 | 02 | 04 | 0200 )) != 0)?1:0);}
    private function ispunct ($c){ return ((($this->ctype__[( ord($c) )]& 020 ) != 0)?1:0);}
    private function isspace ($c){ return ((($this->ctype__[( ord($c) )]& 010 ) != 0)?1:0);}
    private function isupper ($c){ return ((($this->ctype__[( ord($c) )]& 01 ) != 0)?1:0);}
    private function isxdigit ($c){ return ((($this->ctype__[( ord($c) )]&(0100 | 04 )) != 0)?1:0);}
    private $ctype__ = array(
    32,32,32,32,32,32,32,32,32,40,40,40,40,40,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,32,
    -120,16,16,16,16,16,16,16,16,16,16,16,16,16,16,16,4,4,4,4,4,4,4,4,4,4,16,16,16,16,16,16,
    16,65,65,65,65,65,65,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,16,16,16,16,16,
    16,66,66,66,66,66,66,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,16,16,16,16,32,
    0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
    0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
    0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
    0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0);
}
// tidbit_model
