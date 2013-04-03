<?php
App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('MenuHelper', 'Common.View/Helper');

/**
 * MenuHelper Test Case
 */
class MenuHelperTest extends CakeTestCase {

/**
 * setupBeforeClass
 *
 * Manipulate the Menu Helper so that the _data property is publicallly accessible
 * This permits testing the internal state of the class without creating a test
 * double
 *
 * @return void
 */
	public static function setupBeforeClass() {
		$class = new ReflectionClass('MenuHelper');
		$property = $class->getProperty('_data');
		$property->setAccessible(true);
	}

/**
 * setUp method
 *
 * Se the request info to something innocuous but valid, link the helper to a view instance
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'action'),
			array('base' => '/', 'webroot' => '/', 'here' => '/posts/action')
		));

		$View = new View();
		$this->Menu = new MenuHelper($View);
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Menu);

		parent::tearDown();
	}

/**
 * testAdd
 *
 * @return void
 */
	public function testAdd() {
		$this->Menu->add('title', '/url');
		$expected = array (
			'default' => Array (
				'/url' => Array (
					'url' => '/url',
					'title' => 'title'
				)
			)
		);
		$this->assertSame($expected, $this->Menu->_data);
	}

/**
 * testAddMultiple
 *
 * @return void
 */
	public function testAddMultiple() {
		$this->Menu->add('title', '/url');
		$this->Menu->add('title2', '/url/2');
		$expected = array (
			'default' => Array (
				'/url' => Array (
					'url' => '/url',
					'title' => 'title'
				),
				'/url/2' => Array (
					'url' => '/url/2',
					'title' => 'title2'
				)
			)
		);
		$this->assertSame($expected, $this->Menu->_data);
	}

/**
 * testAddMultipleArray
 *
 * @return void
 */
	public function testAddMultipleArray() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = array (
			'default' => Array (
				'/url' => Array (
					'url' => '/url',
					'title' => 'title'
				),
				'/url/2' => Array (
					'url' => '/url/2',
					'title' => 'title2'
				)
			)
		);
		$this->assertSame($expected, $this->Menu->_data);
	}

/**
 * testAddMultipleIndexedArray
 *
 * @return void
 */
	public function testAddMultipleIndexedArray() {
		$this->Menu->add(array(
			array('title' => 'title', 'url' => '/url'),
			array('title' => 'title2', 'url' => '/url/2')
		));
		$expected = array (
			'default' => Array (
				'/url' => Array (
					'url' => '/url',
					'title' => 'title'
				),
				'/url/2' => Array (
					'url' => '/url/2',
					'title' => 'title2'
				)
			)
		);
		$this->assertSame($expected, $this->Menu->_data);
	}

/**
 * testAddDuplicateUrl
 *
 * @return void
 */
	public function testAddDuplicateUrl() {
		$this->Menu->add('title', '/url');
		$this->Menu->add('title', '/url');
		$expected = array (
			'default' => Array (
				'/url' => Array (
					'url' => '/url',
					'title' => 'title'
				),
				'/urltitle' => Array (
					'url' => '/url',
					'title' => 'title'
				)
			)
		);
		$this->assertSame($expected, $this->Menu->_data);
	}

/**
 * testDisplay
 *
 * @return void
 */
	public function testDisplay() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<ul>' .
			'<li><a href="/url">title</a></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplaySpecifiedTag
 *
 * @return void
 */
	public function testDisplaySpecifiedTag() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<menu>' .
			'<li><a href="/url">title</a></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</menu>';
		$result = $this->Menu->display(null, array('tag' => 'menu'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayCallback
 *
 * @return void
 */
	public function testDisplayCallback() {
		$this->Menu->add(array(
			array('title', '/url'),
			array(function() { return "<span>Only <em>hippies</em> use semantic markup</span>"; })
		));
		$expected = '<ul>' .
			'<li><a href="/url">title</a></li>' .
			'<li><span>Only <em>hippies</em> use semantic markup</span></li>' .
			'</ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayCallbackNoArray
 *
 * @return void
 */
	public function testDisplayCallbackNoArray() {
		$this->Menu->add(array(
			array('title', '/url'),
			function() { return "<span>Only <em>hippies</em> use semantic markup</span>"; }
		));
		$expected = '<ul>' .
			'<li><a href="/url">title</a></li>' .
			'<li><span>Only <em>hippies</em> use semantic markup</span></li>' .
			'</ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithOptions
 *
 * @return void
 */
	public function testDisplayWithOptions() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<ul class="foo" id="bar">' .
			'<li><a href="/url">title</a></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';
		$result = $this->Menu->display('default', array('class' => 'foo', 'id' => 'bar'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithOptionsAssumeSection
 *
 * @return void
 */
	public function testDisplayWithOptionsAssumeSection() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<ul class="foo" id="bar">' .
			'<li><a href="/url">title</a></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';
		$result = $this->Menu->display(array('class' => 'foo', 'id' => 'bar'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayNested
 *
 * @return void
 */
	public function testDisplayNested() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2'),
			array('title1.1', '/url/1', array('under' => '/url')),
		));
		$expected = '<ul>' .
			'<li><a href="/url">title</a><ul>' .
				'<li><a href="/url/1">title1.1</a></li>' .
			'</ul></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayNestedChildren
 *
 * @return void
 */
	public function testDisplayNestedChildren() {
		$this->Menu->add(array(
			array('title', '/url', array(
				'children' => array(
					array('title1.1', '/url/1'),
				)
			)),
			array('title2', '/url/2'),
		));
		$expected = '<ul>' .
			'<li><a href="/url">title</a><ul>' .
				'<li><a href="/url/1">title1.1</a></li>' .
			'</ul></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayNestedChildrenOptions
 *
 * @return void
 */
	public function testDisplayNestedChildrenOptions() {
		$this->Menu->add(array(
			array('title', '/url', array(
				'children' => array(
					'class' => 'blink',
					array('title1.1', '/url/1'),
				)
			)),
			array('title2', '/url/2'),
		));
		$expected = '<ul>' .
			'<li><a href="/url">title</a><ul class="blink">' .
				'<li><a href="/url/1">title1.1</a></li>' .
			'</ul></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithHereExact
 *
 * @return void
 */
	public function testDisplayWithHereExact() {
		Router::setRequestInfo(array(
			array('controller' => 'foo', 'action' => 'bar'),
			array('base' => '/', 'webroot' => '/', 'here' => '/a/b/c')
		));
		$request = Router::getRequest(true);
		$this->Menu->setRequest($request);

		$this->Menu->add(array(
			array('Action', array('controller' => 'posts', 'action' => 'action', 'arg')),
			array('Controller', array('controller' => 'comments', 'action' => 'action')),
			array('Plugin', array('plugin' => 'cms', 'controller' => 'cms', 'action' => 'action')),
			array('Prefix', array('admin' => true, 'controller' => 'users', 'action' => 'action')),
			array('Exact', '/a/b/c')
		));

		$expected = '<ul>' .
			'<li><a href="/posts/action/arg">Action</a></li>' .
			'<li><a href="/comments/action">Controller</a></li>' .
			'<li><a href="/cms/cms/action">Plugin</a></li>' .
			'<li><a href="/admin/users/action">Prefix</a></li>' .
			'<li class="active"><a href="/a/b/c">Exact</a></li>' .
			'</ul>';

		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithHereAction
 *
 * @return void
 */
	public function testDisplayWithHereAction() {
		Router::setRequestInfo(array(
			array('controller' => 'posts', 'action' => 'action', 'foo'),
			array('base' => '/', 'webroot' => '/', 'here' => '/a/b/c')
		));
		$request = Router::getRequest(true);
		$this->Menu->setRequest($request);

		$this->Menu->add(array(
			array('Action', array('controller' => 'posts', 'action' => 'action', 'arg')),
			array('Controller', array('controller' => 'comments', 'action' => 'action')),
			array('Plugin', array('plugin' => 'cms', 'controller' => 'cms', 'action' => 'action')),
			array('Prefix', array('admin' => true, 'controller' => 'users', 'action' => 'action')),
			array('Exact', '/a/b/c')
		));

		$expected = '<ul>' .
			'<li class="active"><a href="/posts/action/arg">Action</a></li>' .
			'<li><a href="/comments/action">Controller</a></li>' .
			'<li><a href="/cms/cms/action">Plugin</a></li>' .
			'<li><a href="/admin/users/action">Prefix</a></li>' .
			'<li><a href="/a/b/c">Exact</a></li>' .
			'</ul>';

		$result = $this->Menu->display(array('active' => 'action'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithHereController
 *
 * @return void
 */
	public function testDisplayWithHereController() {
		Router::setRequestInfo(array(
			array('controller' => 'comments', 'action' => 'action'),
			array('base' => '/', 'webroot' => '/', 'here' => '/comments/action')
		));
		$request = Router::getRequest(true);
		$this->Menu->setRequest($request);

		$this->Menu->add(array(
			array('Action', array('controller' => 'posts', 'action' => 'action', 'arg')),
			array('Controller', array('controller' => 'comments', 'action' => 'action')),
			array('Plugin', array('plugin' => 'cms', 'controller' => 'cms', 'action' => 'action')),
			array('Prefix', array('admin' => true, 'controller' => 'users', 'action' => 'action')),
			array('Exact', '/a/b/c')
		));

		$expected = '<ul>' .
			'<li><a href="/posts/action/arg">Action</a></li>' .
			'<li class="active"><a href="/comments/action">Controller</a></li>' .
			'<li><a href="/cms/cms/action">Plugin</a></li>' .
			'<li><a href="/admin/users/action">Prefix</a></li>' .
			'<li><a href="/a/b/c">Exact</a></li>' .
			'</ul>';

		$result = $this->Menu->display(array('active' => 'controller'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithHerePlugin
 *
 * @return void
 */
	public function testDisplayWithHerePlugin() {
		Router::setRequestInfo(array(
			array('plugin' => 'cms', 'controller' => 'cms', 'action' => 'action'),
			array('base' => '/', 'webroot' => '/', 'here' => '/cms/cms/action')
		));
		$request = Router::getRequest(true);
		$this->Menu->setRequest($request);

		$this->Menu->add(array(
			array('Action', array('plugin' => null, 'controller' => 'posts', 'action' => 'action', 'arg')),
			array('Controller', array('plugin' => null, 'controller' => 'comments', 'action' => 'action')),
			array('Plugin', array('plugin' => 'cms', 'controller' => 'cms', 'action' => 'action')),
			array('Prefix', array('admin' => true, 'plugin' => null, 'controller' => 'users', 'action' => 'action')),
			array('Exact', '/a/b/c')
		));

		$expected = '<ul>' .
			'<li><a href="/posts/action/arg">Action</a></li>' .
			'<li><a href="/comments/action">Controller</a></li>' .
			'<li class="active"><a href="/cms/cms/action">Plugin</a></li>' .
			'<li><a href="/admin/users/action">Prefix</a></li>' .
			'<li><a href="/a/b/c">Exact</a></li>' .
			'</ul>';

		$result = $this->Menu->display(array('active' => 'plugin'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithHerePrefix
 *
 * @return void
 */
	public function testDisplayWithHerePrefix() {
		Router::setRequestInfo(array(
			array('admin' => true, 'prefix' => 'admin', 'controller' => 'users', 'action' => 'action'),
			array('base' => '/', 'webroot' => '/', 'here' => '/admin/users/action')
		));
		$request = Router::getRequest(true);
		$this->Menu->setRequest($request);

		$this->Menu->add(array(
			array('Action', array('admin' => null, 'controller' => 'posts', 'action' => 'action', 'arg')),
			array('Controller', array('admin' => null, 'controller' => 'comments', 'action' => 'action')),
			array('Plugin', array('admin' => null, 'plugin' => 'cms', 'controller' => 'cms', 'action' => 'action')),
			array('Prefix', array('admin' => true, 'controller' => 'users', 'action' => 'action')),
			array('Exact', '/a/b/c')
		));

		$expected = '<ul>' .
			'<li><a href="/posts/action/arg">Action</a></li>' .
			'<li><a href="/comments/action">Controller</a></li>' .
			'<li><a href="/cms/cms/action">Plugin</a></li>' .
			'<li class="active"><a href="/admin/users/action">Prefix</a></li>' .
			'<li><a href="/a/b/c">Exact</a></li>' .
			'</ul>';

		$result = $this->Menu->display(array('active' => 'prefix'));
		$this->assertSame($expected, $result);
	}

/**
 * testDisplayWithHereCallback
 *
 * @return void
 */
	public function testDisplayWithHereCallback() {
		$this->Menu->add(array(
			array('1', array('x' => 1)),
			array('2', array('x' => 2)),
			array('3', array('x' => 3)),
			array('4', array('x' => 4)),
			array('5', array('x' => 5)),
		));

		$expected = '<ul>' .
			'<li><a href="/posts/action/x:1">1</a></li>' .
			'<li><a href="/posts/action/x:2">2</a></li>' .
			'<li class="active"><a href="/posts/action/x:3">3</a></li>' .
			'<li><a href="/posts/action/x:4">4</a></li>' .
			'<li><a href="/posts/action/x:5">5</a></li>' .
			'</ul>';

		$result = $this->Menu->display(array(
			'active' => function($View, $item) { return $item['url']['x'] === 3; }
		));
		$this->assertSame($expected, $result);
	}

/**
 * testGenerate
 *
 * @return void
 */
	public function testGenerate() {
		$expected = '<ul>' .
			'<li><a href="/url">title</a></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';

		$result = $this->Menu->generate(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$this->assertSame($expected, $result);
	}

/**
 * testGenerateWithOptions
 *
 * @return void
 */
	public function testGenerateWithOptions() {
		$expected = '<ul class="foo" id="bar">' .
			'<li><a href="/url">title</a></li>' .
			'<li><a href="/url/2">title2</a></li>' .
			'</ul>';

		$result = $this->Menu->generate(
			array(
				array('title', '/url'),
				array('title2', '/url/2')
			),
			array('class' => 'foo', 'id' => 'bar')
		);
		$this->assertSame($expected, $result);
	}

/**
 * testSection
 *
 * @return void
 */
	public function testSection() {
		$section = $this->Menu->section();
		$this->assertSame('default', $section);

		$section = $this->Menu->section('updated');
		$this->assertSame('updated', $section);

		$this->Menu->section('changed');
		$section = $this->Menu->section();
		$this->assertSame('changed', $section);
	}

/**
 * testPrepend
 *
 * @return void
 */
	public function testPrepend() {
		$this->Menu->add('title', '/url');
		$this->Menu->prepend('first', '/first');
		$expected = array (
			'default' => Array (
				'/first' => Array (
					'url' => '/first',
					'title' => 'first'
				),
				'/url' => Array (
					'url' => '/url',
					'title' => 'title'
				)
			)
		);
		$this->assertSame($expected, $this->Menu->_data);
	}
}
