<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';

define("LIBPATH", __DIR__.'/..');
$opt = getopt('',['env:','in:','out:']);

$version = file_get_contents(LIBPATH.'/VERSION');
$codes = array_keys($cfg);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = $version;

foreach($codes as $code){

	$files = $cfg[$code];
	$output = $opt['out']."/libweb/$code.php";
	LibBuilder::parse($opt['in'], $files, $env, $output);
}

exit(0);