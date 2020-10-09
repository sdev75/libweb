<?php

$opt = getopt('',['env:','in:','out:','include-path:','path-code:','cache-path:', 'debug:']);

define("LIBPATH", __DIR__.'/..');
$version = file_get_contents(LIBPATH.'/VERSION');

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/viewbuilder.php';
include __DIR__.'/../utils/preprocessor.php';
include $opt['include-path']."/lib/libw-{$version}/view/parser.php";
include $opt['include-path']."/lib/libw-{$version}/view/combiner.php";

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = $version;

ViewBuilder::setEnvVars($env);
ViewBuilder::setIncludePath($opt['include-path']);
ViewBuilder::loadViewsToParse("{$opt['cache-path']}/data/views.php");
ViewBuilder::loadCodeIncludes("{$opt['cache-path']}/data/includes.php");

// Preprocessor (basic test)
ViewBuilder::$pp = new Preprocessor();
ViewBuilder::$pp->vars['debug'] = $opt['debug'];

foreach(ViewBuilder::$views as $view){
	ViewBuilder::build($view, $opt['path-code'], $opt['in'], $opt['out']);
}

exit(0);