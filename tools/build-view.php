<?php

define("LIBPATH", __DIR__.'/..');
define("CWD", getcwd());
$version = file_get_contents(LIBPATH.'/VERSION');

include __DIR__.'/build-error.php';
include __DIR__.'/../utils/envbuilder.php';
include __DIR__.'/../utils/libbuilder.php';
include __DIR__.'/../utils/viewbuilder.php';
include CWD."/src/.cache/lib/libweb-{$version}/view.php";
include CWD."/src/.cache/lib/libweb-{$version}/view_parser.php";

$opt = getopt('',['env:','in:','out:','path-code:']);

$cfg = json_decode(file_get_contents(LIBPATH.'/src/config.json'),true);

$codes = array_keys($cfg);

EnvBuilder::import($opt['env']);
$env = EnvBuilder::getVars();
LibBuilder::$version = $version;

ViewBuilder::setEnvVars($env);
ViewBuilder::setIncludePath(CWD.'/src');
ViewBuilder::loadViewsToParse(CWD.'/src/.cache/data/views.php');

foreach(ViewBuilder::$views as $view){
	ViewBuilder::build($view, $opt['path-code'], $opt['in'], $opt['out']);
}


exit(0);