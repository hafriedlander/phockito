<?php

namespace Phockito;


class BacktraceFormatter
{
    function formatBacktrace(array $backtrace)
    {
        // Remove the first element from the backtrace: it will always be eval()'d code in Phockito.php
        array_shift($backtrace);

        $lines = array_map(
            function ($info, $index) {
                return $this->_formatLineOfBacktrace($index, $info);
            },
            $backtrace,
            array_keys($backtrace)
        );

        return implode($lines, "\n");
    }

    private function _formatLineOfBacktrace($index, $info)
    {
        $file = isset($info['file']) ? $info['file'] : 'No file';
        $line = isset($info['line']) ? $info['line'] : 'no line';
        $invocationOperator = isset($info['type']) ? $info['type'] : '#';
        $classAndInvocation = isset($info['class']) ? $info['class'] . $invocationOperator : '';
        $function = isset($info['function']) ? $info['function'] : 'No function';
        $args = $this->_formatArgs(isset($info['args']) ? $info['args'] : array());

        return "#$index $file($line): $classAndInvocation$function($args)";
    }

    private function _formatArgs($args)
    {
        $args = array_map(
            function ($arg) {
                return $this->_truncateArg($this->_formatArg($arg));
            },
            $args
        );
        return implode(', ', $args);
    }

    private function _formatArg($arg)
    {
        if (is_object($arg)) {
            if (method_exists($arg, '__toString')) {
                return (string)$arg;
            } else {
                return get_class($arg);
            }
        } elseif (is_array($arg)) {
            return 'array[' . count($arg) . ']';
        } else {
            return print_r($arg, true);
        }
    }

    private function _truncateArg($arg)
    {
        if (strlen($arg) > 50) {
            return substr($arg, 0, 47) . '...';
        } else {
            return $arg;
        }
    }
}