<?php

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/codebuilder.php';

define("LIBPATH", __DIR__.'/..');
define("CWD", getcwd());
$opt = getopt('',['env:','in:','out:','include-path:']);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = file_get_contents(LIBPATH.'/VERSION');

CodeBuilder::setEnvVars($env);
CodeBuilder::setIncludePath($opt['include-path']);
CodeBuilder::collectFiles($opt['in']);
CodeBuilder::$metadata = new CodeControllerMetaCollection();

foreach(CodeBuilder::$files as $filename){
	CodeBuilder::build($filename, $opt['in'],$opt['out']);
}

CodeBuilder::buildRoutesFromMetadata();
CodeBuilder::buildViewsFromMetadata();
CodeBuilder::writeRoutesToFile("{$opt['include-path']}/.cache/data/routes.php");
CodeBuilder::writeViewsToFile("{$opt['include-path']}/.cache/data/views.php");
CodeBuilder::writeIncludesToFile("{$opt['include-path']}/.cache/data/includes.php");

exit(0);