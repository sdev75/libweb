<?php

define("LIBPATH", __DIR__.'/..');
define("CWD", getcwd());
$version = file_get_contents(LIBPATH.'/VERSION');

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/viewbuilder.php';
include CWD."/src/lib/libweb-{$version}/view.php";
include CWD."/src/lib/libweb-{$version}/view/parser.php";
include CWD."/src/lib/libweb-{$version}/view/combiner.php";

$opt = getopt('',['env:','in:','out:','include-path:','path-code:']);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = $version;

ViewBuilder::setEnvVars($env);
ViewBuilder::setIncludePath($opt['include-path']);
ViewBuilder::loadViewsToParse("{$opt['include-path']}/.cache/data/views.php");
ViewBuilder::loadCodeIncludes("{$opt['include-path']}/.cache/data/includes.php");

foreach(ViewBuilder::$views as $view){
	ViewBuilder::build($view, $opt['path-code'], $opt['in'], $opt['out']);
}


exit(0);