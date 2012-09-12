<?php
App::uses('View', 'View');
App::uses('Helper', 'View');
App::uses('MenuHelper', 'Common.View/Helper');

/**
 * MenuHelper Test Case
 */
class MenuHelperTest extends CakeTestCase {

	public static function setupBeforeClass() {
		$class = new ReflectionClass('MenuHelper');
		$property = $class->getProperty('_data');
		$property->setAccessible(true);

		require APP . 'Config/routes.php';
	}

/**
 * setUp method
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

	public function testDisplay() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<ul><li><a href="/url">title</a></li><li><a href="/url/2">title2</a></li></ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

	public function testDisplayWithOptions() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<ul class="foo" id="bar"><li><a href="/url">title</a></li><li><a href="/url/2">title2</a></li></ul>';
		$result = $this->Menu->display('default', array('class' => 'foo', 'id' => 'bar'));
		$this->assertSame($expected, $result);
	}

	public function testDisplayWithOptionsAssumeSection() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2')
		));
		$expected = '<ul class="foo" id="bar"><li><a href="/url">title</a></li><li><a href="/url/2">title2</a></li></ul>';
		$result = $this->Menu->display(array('class' => 'foo', 'id' => 'bar'));
		$this->assertSame($expected, $result);
	}

	public function testDisplayNested() {
		$this->Menu->add(array(
			array('title', '/url'),
			array('title2', '/url/2'),
			array('title1.1', '/url/1', array('under' => '/url')),
		));
		$expected = '<ul><li><a href="/url">title</a><li><a href="/url/1">title1.1</a></li></li><li><a href="/url/2">title2</a></li></ul>';
		$result = $this->Menu->display();
		$this->assertSame($expected, $result);
	}

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

		$result = $this->Menu->display(array('mode' => 'action'));
		$this->assertSame($expected, $result);
	}

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

		$result = $this->Menu->display(array('mode' => 'controller'));
		$this->assertSame($expected, $result);
	}

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

		$result = $this->Menu->display(array('mode' => 'plugin'));
		$this->assertSame($expected, $result);
	}

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

		$result = $this->Menu->display(array('mode' => 'prefix'));
		$this->assertSame($expected, $result);
	}

	public function testSection() {
		$section = $this->Menu->section();
		$this->assertSame('default', $section);

		$section = $this->Menu->section('updated');
		$this->assertSame('updated', $section);

		$this->Menu->section('changed');
		$section = $this->Menu->section();
		$this->assertSame('changed', $section);
	}
}
