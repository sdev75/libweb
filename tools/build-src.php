<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/srcbuilder.php';
include __DIR__.'/../utils/preprocessor.php';

define("LIBPATH", __DIR__.'/..');
$opt = getopt('',['env:','in:','out:','include-path:','debug:']);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = file_get_contents(LIBPATH.'/VERSION');

SrcBuilder::setEnvVars($env);
SrcBuilder::setIncludePath($opt['include-path']);
SrcBuilder::collectFiles($opt['in']);

// Preprocessor (basic test)
SrcBuilder::$pp = new Preprocessor();
SrcBuilder::$pp->vars['debug'] = $opt['debug'];

var_dump($opt['debug']);
foreach(SrcBuilder::$files as $filename){
	SrcBuilder::build($filename, $opt['in'],$opt['out']);
}

exit(0);