<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/srcbuilder.php';
include __DIR__.'/../utils/preprocessor.php';

define("LIBPATH", __DIR__.'/..');
$opt = getopt('',['env:','in:','out:','include-path:','debug:', 'cache-path:']);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = file_get_contents(LIBPATH.'/VERSION');

SrcBuilder::setEnvVars($env);
SrcBuilder::setIncludePath($opt['include-path']);
SrcBuilder::collectFiles($opt['in']);

// Preprocessor (basic test)
SrcBuilder::$pp = new Preprocessor();
SrcBuilder::$pp->vars['debug'] = $opt['debug'] ?? false;

foreach(SrcBuilder::$files as $a){
	SrcBuilder::build($a['filename'], $a['ext'], $opt['in'],$opt['out']);
}

exit(0);