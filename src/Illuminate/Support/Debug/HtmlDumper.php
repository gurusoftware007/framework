<?php namespace Illuminate\Support\Debug;

use Symfony\Component\VarDumper\Dumper\HtmlDumper as SymfonyHtmlDumper;

class HtmlDumper extends SymfonyHtmlDumper {

	/**
	 * Colour definitions for output.
	 *
	 * @var array
	 */
	protected $styles = array(
		'default'   => 'background-color:#fff; color:#222; line-height:1.2em; font-weight:normal; font:12px Monaco, Consolas, monospace',
		'num' 		=> 'color:#a71d5d',
		'const' 	=> 'color:#795da3',
		'str' 		=> 'color:#df5000',
		'cchr' 		=> 'color:#222',
		'note' 		=> 'color:#a71d5d',
		'ref' 		=> 'color:#A0A0A0',
		'public' 	=> 'color:#795da3',
		'protected' => 'color:#795da3',
		'private' 	=> 'color:#795da3',
		'meta' 		=> 'color:#B729D9',
		'key' 		=> 'color:#df5000',
		'index' 	=> 'color:#a71d5d',
	);

}
