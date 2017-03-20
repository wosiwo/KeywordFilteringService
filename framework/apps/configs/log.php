<?php
$log['master'] = array(
    'type' => 'FileLog',
    'file' => WEBPATH . '/logs/app.log',
);

$log['test'] = array(
    'type' => 'FileLog',
    'file' => WEBPATH . '/logs/test.log',
);

return $log;