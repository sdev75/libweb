<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';

$data = stream_get_contents(STDIN);
EnvBuilder::parse($data);
foreach(EnvBuilder::getVars() as $k => $v){
	fprintf(STDOUT, "{$v['key']}={$v['val']}\n");
}

exit(0);