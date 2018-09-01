<?php

namespace Spruce\Utility;

use Exception;

class Debug {
    /*
    * Dump given parameters and stop PHP execution.
    *
    * @param mixed  ...
    */
    static public function s() {
        try {
            throw new Exception();
        } catch (Exception $e) {
            if (php_sapi_name() !== "cli") {
                header("Content-Type: text/plain;charset=utf-8");
            }
            $trace = $e->getTrace();
            if (isset($trace[0])) {
                $file = realpath($trace[0]["file"]);
                $line = $trace[0]["line"];
                $where = sprintf("", $file, $line);
                $sep1 = str_repeat("─", mb_strlen($file) + 2)."┬".str_repeat("─", mb_strlen($line) + 2);
                $sep2 = str_repeat("─", mb_strlen($file) + 2)."┴".str_repeat("─", mb_strlen($line) + 2);
                printf("┌%s┐\n│ %s │ %d │\n└%s┘\n\n", $sep1, $file, $line, $sep2);
            }
        }

        foreach (func_get_args() as $value) {
            debug_zval_dump($value);
            print PHP_EOL;
        }

        exit(0);
    }

    /*
    * Printf given message and stop PHP execution.
    *
    * @param string $message
    * @param mixed  ...
    */
    static public function sf($message) {
        if (func_num_args() > 0) {
            call_user_func_array("printf", func_get_args());
        }

        exit(0);
    }
}