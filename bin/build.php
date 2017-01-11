<?php

$base_path = __DIR__ . '/..';
$build_path = $base_path . '/build'; 
require_once($base_path . '/vendor/autoload.php');

if (!setUpBuildPath($build_path))
	exit(1);

if (!copyRequiredFiles($base_path, $build_path)) 
	exit(2);

if (!runComposerInstall($build_path))
	exit(3);

if (!createCompressedFiles($build_path))
	exit(4);

function setUpBuildPath($path) 
{
	// create if doesn't exist
	if (!file_exists($path)) {
		if (!mkdir($path)) 
			return false;
	}
	// now empty it out in case this is another attempt
	$di = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
	$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::CHILD_FIRST);
	foreach ( $ri as $file ) {
	    $file->isDir() ?  rmdir($file) : unlink($file);
	}
	return true;
}

function copyDirectory($from, $to)
{
	$from_real = realpath($from);
	
	if (!mkdir($to)) return false;
	$to_real = realpath($to);

	$from_len = strlen($from_real)+1;
	$di = new RecursiveDirectoryIterator($from_real, FilesystemIterator::SKIP_DOTS);
	$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::SELF_FIRST);
	foreach ( $ri as $file ) {
		$file->isDir() 
	    	?  mkdir($to_real . '/' . substr($file->getRealPath(), $from_len)) 
	    	: copy($file->getRealPath(), $to_real . '/' . substr($file->getRealPath(), $from_len));
	}

	return true;
}

function copyRequiredFiles($base_path, $build_path)
{
	if (!copyDirectory($base_path . '/src', $build_path . '/src')) return false;
	if (!copy($base_path . '/composer.json', $build_path. '/composer.json')) return false;
	if (!copy($base_path . '/composer.lock', $build_path. '/composer.lock')) return false;
	return true;
}

function runComposerInstall($build_path)
{
	return `composer install --no-dev --no-interaction --optimize-autoloader --working-dir={$build_path}` == 0;
}

function createCompressedFiles($build_path)
{
	$real_bp = realpath($build_path);
	$bp_len = strlen($real_bp) + 1;
	$archive = new ZipArchive();
	$archive->open($real_bp . '/' . getReleaseName() . '.zip', ZIPARCHIVE::CREATE);
	foreach (['src', 'vendor'] as $dir) {
		$build_dir = $real_bp . '/' . $dir;
		
		$di = new RecursiveDirectoryIterator($build_dir, FilesystemIterator::SKIP_DOTS);
		$ri = new RecursiveIteratorIterator($di, RecursiveIteratorIterator::SELF_FIRST);	
		foreach ($ri as $file) {
			$file->isDir() 
				? $archive->addEmptyDir(substr($file->getRealPath(), $bp_len)) 
				: $archive->addFile($file, substr($file->getRealPath(), $bp_len));
		}
	}
	$archive->close();
	return true;
}

function getReleaseName()
{
	return 'interFAX-PHP-' . Interfax\Client::VERSION;
}