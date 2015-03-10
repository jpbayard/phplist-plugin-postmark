<?php
/**
 * 
 * This file is a part of PostMarkPlugin.
 *
 * PostMarkPlugin is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * PostMarkPlugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * @category  phplist
 * @package   PostMarkPlugin
 * @author    Jean-Philippe Bayard
 * @copyright 2015 Jean-Philippe Bayard
 * @license   http://www.gnu.org/licenses/gpl.html GNU General Public License, Version 3
 *
 * The plugin shows dmarc analyse made by Postmark using their API.
 * version history:
 * 
 * Version 1.0 - Bayard Jean-Philippe, 2015-03-04
 */
 
defined('PHPLISTINIT') || die;

class PostMarkPlugin extends phplistPlugin {
	const VERSION_FILE = 'version.txt';
	
	public $name = "Postmark results";
    public $pluginroot ;
    public $description = 'Get readable DMARC informations about your domain';
	
	public $topMenuLinks = array(
      'main' => array('category' => 'statistics'),
    ); 
	
	public $postmark_private_key;
	public $postmark_timeout;
	
	/* Parameters */
	public $settings = array(
        'postmark_private_key' => array (
          'value' => 'your_private_key',
          'description' => 'Postmark private key',
          'type' => 'text',
          'allowempty' => 0,
          'category'=> 'postmark',
        ),
		'postmark_timeout' => array (
          'value' => '30',
          'description' => 'Request timeout ( x seconds )',
          'type' => 'integer',
		  'min' => 0,
		  'max' => 1000,
          'allowempty' => 0,
          'category'=> 'postmark',
        )
    );

    function PostMark() {
      parent::phplistplugin();
      $this->coderoot = dirname(__FILE__) . '/PostMarkPlugin/';
    }

	 public $pageTitles = array(
        'main' => 'Postmark Statistics',
    );
	
    function adminmenu() {
        return array(
            "main" => "PostMarkPlugin"
        );
    }
	
	 public function __construct()
    {
        $this->coderoot = dirname(__FILE__) . '/' . __CLASS__ . '/';
        $this->version = (is_file($f = $this->coderoot . self::VERSION_FILE))
            ? file_get_contents($f)
            : '';
        parent::__construct();
		
		$this->postmark_private_key = getConfig('postmark_private_key');
		$this->postmark_timeout = getConfig('postmark_timeout');
		$this->pluginroot =  './'. PLUGIN_ROOTDIR . '/PostMarkPlugin/';
	
    }
	
}