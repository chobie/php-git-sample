<?php
/*
 * The MIT License
 *
 * Copyright (c) 2010 - 2011 Shuhei Tanuma
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
$profiler['begin'] = microtime(true);
$memories['begin'] = memory_get_usage();

require "silex.phar";

/** specify your repository dir */
define("GIT_REPOSITORY_DIR","/home/chobie/src/libgit2/.git");
/** specify your default reference*/
define("DEFAULT_REFERENCE","refs/heads/development");

define("REPOSITORY_NAME", basename(dirname(GIT_REPOSITORY_DIR)));
define("REFNAME",basename(DEFAULT_REFERENCE));




$app = new Silex\Application();

$app->get('/', function(){
		$repo =  new Git\Repository(GIT_REPOSITORY_DIR);
		$ref = $repo->lookupRef(DEFAULT_REFERENCE);

		echo "<html><body>";
		echo "<h1><a href='/'>" . REPOSITORY_NAME . "</a></h1>";
		echo "<table border='1'>";

		$commit = $repo->getCommit($ref->getId());
		foreach($commit->getTree() as $entry) {
		if($entry->isBlob()) {
		$head = $entry->toHeader();
		echo "<tr>";
		printf("<td><a href='/blob/%s/%s'>%s</a></td>",REFNAME,$entry->name,$entry->name);
		printf("<td>%d</td>",$head->size);
		echo "</tr>";
		} else {
		echo "<tr>";
		printf("<td><a href='/tree/%s/%s'>%s/</a></td>",REFNAME,$entry->name,$entry->name);
		printf("<td>&nbsp;</td>");
		echo "</tr>";
		}
		}
		echo "</table>";
		echo "</body></html>";
});
$app->get('/blob/{reference}/{name}',function($name){
		$repo =  new Git\Repository(GIT_REPOSITORY_DIR);
		$ref = $repo->lookupRef(DEFAULT_REFERENCE);

		echo "<html><body>";
		echo "<h1><a href='/'>" . REPOSITORY_NAME . "</a></h1>";

		$commit = $repo->getCommit($ref->getId());
		$blob = resolve_filename($commit->getTree(), $name);

		if($blob) {
		echo "<pre>";
		echo $blob->data;
		echo "</pre>";
		}
		})->assert("name",".+");

$app->get("/tree/{reference}/{name}",function($name){

		$repo =  new Git\Repository(GIT_REPOSITORY_DIR);
		$ref = $repo->lookupRef(DEFAULT_REFERENCE);

		echo "<html><body>";
		echo "<h1><a href='/'>" . REPOSITORY_NAME . "</a></h1>";

		$commit = $repo->getCommit($ref->getId());
		$tree = resolve_filename($commit->getTree(), $name);
		if($tree) {
		echo "<table border='1'>";
		foreach($tree->getIterator() as $entry) {
		if($entry->isBlob()) {
		$head = $entry->toHeader();
		echo "<tr>";
		printf("<td><a href='/blob/%s/%s/%s'>%s</a></td>",REFNAME,$name,$entry->name,$entry->name);
		printf("<td>%d</td>",$head->size);
		echo "</tr>";
		} else {
		echo "<tr>";
		printf("<td><a href='/tree/%s/%s/%s'>%s/</a></td>",REFNAME,$name,$entry->name,$entry->name);
		printf("<td>&nbsp;</td>");
		echo "</tr>";
		}
		}
		echo "</table>";
		}

})->assert("name",".+");

$app->run();

$memories['end'] = memory_get_usage();
$profiler['end'] = microtime(true);

show_calculatetd_time($profiler);
show_memory_usage($memories);

/**
 * Helper Functions
 */

function show_calculatetd_time($profiler)
{
	echo "<table border='1'>";
	echo "<tr><td colspan=2>Calculated Time</td></tr>";
	echo "<tr><td>Time</td><td>";
	echo $profiler['end']-$profiler['begin'];
	echo "</td></tr>";
	echo "</table>";
}

function show_memory_usage($memories){
	echo "<table border='1'>";
	echo "<tr><td colspan=2>Memory Usage</td></tr>";
	foreach($memories as $key => $value) {
		echo "<tr><td>{$key}</td><td>{$value}</td></tr>";
	}
	echo "</table>";
}

function resolve_filename($tree,$name)
{
	$list = explode("/",$name);
	$cnt = count($list);

	$i = 1;
	while ($fname = array_shift($list)) {
		foreach ($tree->getIterator() as $entry) {
			if ($entry->name == $fname) {
				if ($i < $cnt && $entry->isTree()) {
					return resolve_filename($entry->toObject(),join("/",$list));
				} else {
					return $entry->toObject();
				}
			}
		}
		$i++;
	}

	return null;
}
