<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/codebuilder.php';

define("LIBPATH", __DIR__.'/..');
define("CWD", getcwd());
$opt = getopt('',['env:','in:','out:']);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = file_get_contents(LIBPATH.'/VERSION');

CodeBuilder::setEnvVars($env);
CodeBuilder::setIncludePath(CWD.'/src');
CodeBuilder::collectFiles($opt['in']);

foreach(CodeBuilder::$files as $filename){
	CodeBuilder::build($filename, $opt['in'],$opt['out']);
}

// dump routes in .cache/var/routes
CodeBuilder::writeRoutesToFile(CWD.'/src/.cache/data/routes.php');
CodeBuilder::writeViewsToFile(CWD.'/src/.cache/data/views.php');

exit(0);