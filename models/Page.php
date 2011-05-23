<?php

// page view model
class Page extends Mustache {

	// content file extension
	public static $ext = '.html';

	// path to page content, relative to CONTENTPATH
	public $path;

	// default mustache template, relative to TEMPLATEPATH
	// template defined in page variable, allow setting this in section variable
	public $template = 'project.mustache';

	// page name and link id
	public $name;

	// page title, used in menu
	public $title;

	// page description, optional
	public $description;

	// page content
	public $content;

	// redirect URL for creating aliases
	public $redirect;

	// thumbnail for gallery display
	public $thumb;

	// parent section
	public $section;

	// page is a section index
	public $is_section = FALSE;

	// visible in menu
	public $is_visible = TRUE;

	// child pages, populated by Menu
	public $children = array();

	// constructor loads data
	public function __construct($name, $path, $section, $parent) {

		// set project name
		$this->name = $name;
		$this->path = trim($path, '/');

		// index files are created as the section parent
		if ($name == 'index') {

			$this->is_section = TRUE;

			// if deeper than root section
			if ($section !== NULL) {

				// TODO: consider changing structure to leave index files as is, create is_section files that don't have content, only vars
				// name changed from index to section name
				$this->name = $section;
				$this->section = $parent;

			}
		} else {

			// assign section name
			$this->section = $section;
		}

		// load content data
		$this->load();

	}

	// filter and return pages by properties
	public static function find_all($terms) {

		$pages = array();

		foreach (Pastefolio::$pages as $page) {

			$matched = TRUE;

			foreach ($terms as $property => $value) {

				if ($page->$property !== $value) {

					$matched = FALSE;

				}
			}

			if ($matched)
				$pages[] = $page;

		}

		return $pages;

	}

	// retrieve single page by properties
	public static function find($terms) {

		$pages = self::find_all($terms);

		return (empty($pages)) ? FALSE : $pages[0];

	}

	// TODO: allow inifinite section depth
	// TODO: caching $pages data
	// recursively load sections of content, relative to CONTENTPATH
	public static function load_path($path = '/', $section = NULL, $parent = NULL) {

		$pages = array();

		foreach (Pastefolio::list_dir($path) as $file => $name) {

			// check if file is page or section
			if (strstr($file, self::$ext) === FALSE) {

				$pages = array_merge($pages, self::load_path($file, $name, $section));

			} else {

				$page = new Page($name, $path.'/'.$file, $section, $parent);

				$pages[] = $page;

			}
		}

		return $pages;

	}

	// TODO: should this be moved to Content class?
	// TODO: strip any commments after # or //
	// load individual content page
	public function load() {

		if (FALSE !== ($html = @file_get_contents(realpath(CONTENTPATH.$this->path)))) {

			// credit to Ben Blank: http://stackoverflow.com/questions/441404/regular-expression-to-find-and-replace-the-content-of-html-comment-tags/441462#441462
			$regexp = '/<!--((?:[^-]+|-(?!->))*)-->/Ui';
			preg_match_all($regexp, $html, $comments);

			// split comments on newline
			$lines = array();
			foreach ($comments[1] as $comment) {
				$var_lines = explode("\n", trim($comment));
				$lines = array_merge($lines, $var_lines);
			}

			// split lines on colon and assign to key/value
			$vars = array();
			foreach ($lines as $line) {
				$parts = explode(":", $line, 2);
				if (count($parts) == 2) {
					$vars[trim($parts[0])] = trim($parts[1]);
				}
			}

			foreach ($vars as $key => $value) {
				if (strtolower($value) === "false" OR $value === '0') {
					$value = FALSE;
				} elseif (strtolower($value) === "true" OR $value === '1') {
					$value = TRUE;
				}
				$this->$key = $value;
			}

			// set title to name if not set otherwise
			$this->title = (empty($this->title)) ? ucwords(str_replace('_', ' ', $this->name)) : $this->title;
			$this->content = $html;
			$this->content .= "<pre>".htmlentities(print_r($vars, TRUE)).'</pre>';

		}
	}

	// check if current page or section
	public function current() {

		// get current page and section from controller
		$current_page = Pastefolio::instance()->current_page;
		$current_section = Pastefolio::instance()->current_section;

		return (($this->name == $current_page AND $this->section == $current_section) OR ($this->is_section AND $this->name == $current_section));

	}

	// convert page object to array, moving methods to properties
	// deprecated, Mustache does this fine
	public function as_array() {

		$page_array = array();

		foreach (get_class_methods(__CLASS__) as $method) {

			// ignore methods defined in exclude and those with an underscore prefix
			if (! in_array($method, $this->_exclude) AND $method[0] !== '_') {

				// convert methods to properties
				$page_array[$method] = $this->$method();

			}
		}

		foreach (get_object_vars($this) as $property => $value) {

			// ignore properties defined in exclude and those with an underscore prefix
			if (! in_array($property, $this->_exclude) AND $property[0] !== '_') {

				$page_array[$property] = $value;

			}

		}

		return $page_array;

	}
/*
	// returns array of all projects
	public static function all_projects() {

		$projects = array();

		foreach (Content::list_dir(self::$_project_path) as $name) {
			$projects[] = Project::factory($name)->load();
		}

		return $projects;
	}
	*/

	public function __get($property) {

		// avoid undefined property errors
		return '';

	}


	public function render() {

		if (! strstr($this->template, '.mustache')) {

			$this->template = $this->template.'.mustache';

		}

		return parent::render(file_get_contents(realpath(TEMPLATEPATH.$this->template)));

	}

}