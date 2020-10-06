<?php

mb_internal_encoding('UTF-8');
$_SERVER['_TIME_START'] = microtime(1);
$_SERVER['_MEM_START'] = memory_get_usage();
$_SERVER['_REQ_ID'] = str_pad(abs(crc32(rand(1,1000000).time())),10,'0',STR_PAD_RIGHT);
$_SERVER['_REQ_TS'] = time();