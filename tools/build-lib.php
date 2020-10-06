<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';

define("LIBPATH", __DIR__.'/..');
define("CWD", getcwd());
$opt = getopt('',['env:','in:','out:']);

$cfg = json_decode(file_get_contents(LIBPATH.'/src/config.json'),true);
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