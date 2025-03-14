<?php

function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . ' - ' . $message;
    
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            $log .= ' - ' . json_encode($data);
        } else {
            $log .= ' - ' . $data;
        }
    }
    
    file_put_contents(__DIR__ . '/../debug.log', $log . PHP_EOL, FILE_APPEND);
}