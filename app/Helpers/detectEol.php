<?php
/**
 * Detects the end-of-line character of a file.
 * @param string $filename The filename of file to check.
 * @param string $default Default EOL (if not detected).
 * @return string|null The detected EOL, or default one.
 */
function detectEol($filename, $default=null){
    $content = file_get_contents($filename);
    static $eols = array(
        "\0x000D000A", // [UNICODE] CR+LF: CR (U+000D) followed by LF (U+000A)
        "\0x000A",     // [UNICODE] LF: Line Feed, U+000A
        "\0x000B",     // [UNICODE] VT: Vertical Tab, U+000B
        "\0x000C",     // [UNICODE] FF: Form Feed, U+000C
        "\0x000D",     // [UNICODE] CR: Carriage Return, U+000D
        "\0x0085",     // [UNICODE] NEL: Next Line, U+0085
        "\0x2028",     // [UNICODE] LS: Line Separator, U+2028
        "\0x2029",     // [UNICODE] PS: Paragraph Separator, U+2029
        "\0x0D0A",     // [ASCII] CR+LF: Windows, TOPS-10, RT-11, CP/M, MP/M, DOS, Atari TOS, OS/2, Symbian OS, Palm OS
        "\0x0A0D",     // [ASCII] LF+CR: BBC Acorn, RISC OS spooled text output.
        "\0x0A",       // [ASCII] LF: Multics, Unix, Unix-like, BeOS, Amiga, RISC OS
        "\0x0D",       // [ASCII] CR: Commodore 8-bit, BBC Acorn, TRS-80, Apple II, Mac OS <=v9, OS-9
        "\0x1E",       // [ASCII] RS: QNX (pre-POSIX)
        //"\0x76",       // [?????] NEWLINE: ZX80, ZX81 [DEPRECATED]
        "\0x15",       // [EBCDEIC] NEL: OS/390, OS/400
    );
    $cur_cnt = 0;
    $cur_eol = $default;
    foreach($eols as $eol){
        if(($count = substr_count($content, $eol)) > $cur_cnt){
            $cur_cnt = $count;
            $cur_eol = $eol;
        }
    }
    return $cur_eol;
}