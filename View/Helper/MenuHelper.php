<?php
App::uses('AppHelper', 'View/Helper');

/**
 * Helper for generating menus as (nested) uls
 */
class MenuHelper extends AppHelper {

/**
 * helpers
 *
 * @var array
 */
	public $helpers = array(
		'Html'
	);

/**
 * _data
 *
 * Nested array of menu items
 *
 * @var array
 */
	protected $_data = array();

/**
 * _here
 *
 * Holds the url and params for the current request to use in comparison to determine
 * If the current link item should be marked active
 *
 * @var array
 */
	protected $_here = array(
		'url' => '/',
		'params' => array()
	);

/**
 * _section
 *
 * If multiple menus are being built at the same time - this holds the name of the current
 * menu section
 *
 * @var string
 */
	protected $_section = 'default';

/**
 * _sensitivity
 *
 * How specific to be when comparing urls to determin the "active" li element.
 * Possible values, in decreasing order of sensitivity:
 * 	exact
 * 	action
 * 	controller
 *  plugin
 * 	prefix
 *
 * @var string
 */
	protected $_sensitivity = 'exact';

/**
 * __construct
 *
 * Automatically set the request object when instanciated
 *
 * @param View $View
 * @param array $settings
 * @return void
 */
	public function __construct(View $View, $settings = array()) {
		parent::__construct($View, $settings);
		$this->setRequest($this->request);
	}

/**
 * add menu item(s)
 *
 * Permits either adding menu items one at a time, or in batch mode:
 *
 * e.g.:
 * 	$Menu->add('Title', '/url');
 * 	$Menu->add(array(
 * 		array(title, url),
 * 		array(title, url, options),
 *  ));
 * 	$Menu->add(array(
 * 		array('title' => x, 'url' => y),
 * 		array('title' => z, 'url' => a, 'options' => options),
 *  ));
 *
 * The "under" option can be used to create nested urls:
 *
 * 	$Menu->add(array(
 * 		array('title' => x, 'url' => '/'),
 * 		array('title' => z, 'url' => '/a', 'options' => array('under' => '/')),
 * 		array('title' => b, 'url' => c, 'options' => array('under' => '/a')),
 *  ));
 *
 * All urls can and should be specified as arrays
 *
 * @param mixed $title
 * @param mixed $url
 * @param array $options
 * @return void
 */
	public function add($title, $url = null, $options = array()) {
		if (is_array($title)) {
			foreach ($title as $row) {
				if (array_key_exists('url', $row)) {
					$row += array('options' => array());
					$this->add($row['title'], $row['url'], $row['options']);
				} else {
					$row += array(1 => null, 2 => array());
					$this->add($row[0], $row[1], $row[2]);
				}
			}
			return;
		}

		if (is_callable($title)) {
			$options['callback'] = $title;
			$title = rand();
		}

		$uniqueKey = Router::url($url);
		if (isset($this->_data[$this->_section][$uniqueKey])) {
			$uniqueKey .= $title;
		}

		if (!empty($options['under'])) {
			$parentKey = Router::normalize($options['under']);
			unset($options['under']);
			$options['url'] = $url;
			$options['title'] = $title;
			$this->_data[$this->_section][$parentKey]['children']['items'][$uniqueKey] = $options;
			return;
		}

		$options['url'] = $url;
		$options['title'] = $title;

		$children = array();
		if (!empty($options['children'])) {
			$childOptions = array();
			foreach ($options['children'] as $k => $row) {
				if (!is_numeric($k)) {
					$childOptions[$k] = $row;
				}
				if (array_key_exists('url', $row) || array_key_exists('callback', $row)) {
					$children[] = $row;
				} else {
					$row += array(1 => null, 2 => array());
					$children[] = array(
						'url' => $row[0],
						'title' => $row[1],
						'options' => $row[2],
					);
				}
			}
			$options['children'] = array(
				'options' => $childOptions,
				'items' => $children
			);
		}
		$this->_data[$this->_section][$uniqueKey] = $options;
	}

/**
 * display a menu
 *
 * Generate a menu as a ul
 *
 * @param mixed $section
 * @param array $options
 * @return string
 */
	public function display($section = null, $options = array()) {
		if (is_array($section)) {
			$options = $section;
			$section = null;
		} elseif ($section) {
			$this->_section = $section;
		}
		if (!isset($this->_data[$this->_section])) {
			return;
		}

		if (!empty($options['mode'])) {
			$this->_sensitivity = $options['mode'];
			unset($options['mode']);
		}

		$return = $this->_display($this->_data[$this->_section], $options);
		unset($this->_data[$this->_section]);
		$this->reset($this->_section);
		return $return;
	}

/**
 * reset
 *
 * Reset the helper back to a consistent state. Clears the data for the current section
 * or all sections if $all is true
 *
 * @param mixed $all
 * @return void
 */
	public function reset($all = false) {
		if ($all === true) {
			$this->_data = array();
		} else {
			unset($this->_data[$this->_section]);
		}
		$this->_section = 'default';
		$this->_sensitivity = 'exact';
	}

/**
 * Change or retrive the current menu section name
 *
 * @param string $section
 * @return string - current active section name
 */
	public function section($section = null) {
		if (!is_null($section)) {
			$this->_section = $section;
		}
		return $this->_section;
	}

/**
 * setRequest
 *
 * Set the request object and derived properties used by the helper
 *
 * @param CakeRequest $request
 * @return void
 */
	public function setRequest(CakeRequest $request) {
		$this->request = $request;

		$params = $this->request->params;
		$pass = isset($params['pass']) ? $params['pass'] : array();
		$named = isset($params['named']) ? $params['named'] : array();
		unset(
			$params['pass'], $params['named'], $params['paging'], $params['models'], $params['url'],
			$params['autoRender'], $params['bare'], $params['requested'], $params['return'],
			$params['_Token'], $params['isAjax']
		);
		$params = array_merge($params, $pass, $named);
		if (!empty($this->request->query)) {
			$params['?'] = $this->request->query;
		}

		$this->_here = array(
			'url' => Router::normalize($request->here),
			'params' => $params
		);
	}

/**
 * _display
 *
 * Return the inner content for the menu - the li items (nested if appropraite)
 *
 * @param array $items
 * @param array $options
 * @return string
 */
	protected function _display($items, $options = array()) {
		$return = '';
		foreach ($items as $item) {
			$return .= $this->_displayItem($item);
		}

		$options['escape'] = false;
		$return = $this->Html->tag('ul', $return, $options);
		return $return;
	}

/**
 * _displayItem
 *
 * Return an li item for one item
 *
 * @param mixed $item
 * @return string
 */
	protected function _displayItem($item) {
		$options = $item;
		unset($options['title'], $options['url'], $options['children'], $options['callback']);

		if (!empty($item['callback']) && is_callable($item['callback'])) {
			$callback = $item['callback'];
			unset($item['callback']);
			$contents = $callback($this, $item);
		} else {
			if (empty($item['url'])) {
				$contents = $item['title'];
			} else {
				$contents = $this->Html->link($item['title'], $item['url']);
				$options['escape'] = false;
			}
			if (!empty($item['children'])) {
				$childOptions = isset($item['children']['options']) ?: array();
				$contents .= $this->_display($item['children']['items'], $childOptions);
			}

			if ($this->_isActive($item)) {
				if (empty($options['class'])) {
					$options['class'] = 'active';
				} else {
					$options['class'] .= ' active';
				}
			}
		}

		return $this->Html->tag('li', $contents, $options);
	}

/**
 * _isActive
 *
 * Determine if the current link should have the class active
 *
 * @param mixed $item
 * @return bool
 */
	protected function _isActive($item) {
		if ($this->_sensitivity === 'exact') {
			$url = Router::normalize($item['url']);
			return $this->_here['url'] === $url;
		}

		$url = $item['url'];
		if (is_string($url)) {
			$url = Router::parse($url);
			if (!$url) {
				return false;
			}
		}
		$url += array(
			'prefix' => isset($this->request->params['prefix']) ? $this->request->params['prefix'] : null,
			'plugin' => $this->request->params['plugin'],
			'controller' => $this->request->params['controller'],
			'action' => $this->request->params['action']
		);

		$keys = array(
			'plugin',
			'controller',
			'action'
		);

		$keys = array_slice($keys, 0, array_search($this->_sensitivity, $keys) + 1);
		$keys = array_merge($keys, Router::prefixes());
		$keys = array_flip($keys);

		$here = array_filter(array_intersect_key($this->_here['params'], $keys));
		$link = array_filter(array_intersect_key($url, $keys));

		ksort($here);
		ksort($link);

		return $link === $here;
	}
}
