<?php

App::uses('GearmanQueue', 'Common.Gearman');

/**
 * Tests GearmanQueue class
 *
 **/
class GearmanQueueTest extends CakeTestCase {

/**
 * Temporary storage for the real root folder
 *
 * @var string
 */
	protected $_root;

/**
 * Sets up a mocked logger stream
 *
 * @return void
 **/
	public function setUp() {
		parent::setUp();

		$this->_root = Nodes\Environment::getRoot();
		Nodes\Environment::setRoot('/var/www/test');

		$class = $this->getMockClass('BaseLog');
		CakeLog::config('queuetest', array(
			'engine' => $class,
			'types' => array('error'),
		));

		$this->logger = CakeLog::stream('queuetest');
	}

/**
 * Restores everything back to normal
 *
 * @return void
 **/
	public function tearDown() {
		parent::tearDown();

		Nodes\Environment::setRoot($this->_root);

		 GearmanQueue::config(array('servers' => array('127.0.0.1')));
		 GearmanQueue::client(false);
		CakeLog::enable('stderr');
		CakeLog::drop('queuetest');

		unset($this->logger);
	}

/**
 * Tests servers config setting
 *
 * @return void
 **/
	public function testConfigAndServers() {
		$this->assertEquals(array('127.0.0.1'), GearmanQueue::servers());

		 GearmanQueue::config(array('foo' => 'bar'));
		$this->assertEquals(array('127.0.0.1'), GearmanQueue::servers());

		 GearmanQueue::config(array('servers' => array('localhost', 'otherhost')));
		$this->assertEquals(array('localhost', 'otherhost'), GearmanQueue::servers());
	}

/**
 * Tests that calling client() generate a new GearmanClient
 *
 * @return void
 **/
	public function testGenerateClient() {
		$class = $this->getMockClass('GearmanQueue', array('_setServers'));
		$class::config(array('servers' => array('foo', 'bar')));
		$class::staticExpects($this->once())->method('_setServers')->with(array('foo', 'bar'));
		$client = $class::client();
		$this->assertInstanceOf('GearmanClient', $client);
	}

/**
 * Tests that correct server lists string is passed to the client
 *
 * @return void
 **/
	public function testSetCorrectServersList() {
		$client = $this->getMock('GearmanClient', array('addServers'));
		$client->expects($this->once())->method('addServers')->with('foo,bar');
		 GearmanQueue::config(array('servers' => array('foo', 'bar')));
		 GearmanQueue::client($client);
		$this->assertSame($client, GearmanQueue::client());
	}

/**
 * Tests that it is possible to execute background jobs in normal priority
 *
 * @return void
 **/
	public function testExecuteNoPriority() {
		CakeLog::disable('stderr');

		$client = $this->getMock('GearmanClient', array('doBackground'));
		 GearmanQueue::client($client);

		$client->expects($this->any())
			->method('returnCode')
			->will($this->returnValue(GEARMAN_SUCCESS));

		$client->expects($this->at(0))
			->method('doBackground')
			->with('test_foo', json_encode('data'))
			->will($this->returnValue(GEARMAN_SUCCESS));

		$data = array('bar' => 'baz');
		$client->expects($this->at(1))
			->method('doBackground')
			->with('test_bar', json_encode($data))
			->will($this->returnValue(GEARMAN_SUCCESS));

		 GearmanQueue::execute('foo', 'data');
		 GearmanQueue::execute('bar', $data);
	}

/**
 * Tests that calling execute with an incorrect priority raises an exception
 *
 * @expectedException InvalidArgumentException
 * @expectedExceptionMessage foo is not a valid priority, only accepting low and high
 * @return void
 **/
	public function testExecuteWrongPriority() {
		 GearmanQueue::execute('bar', 'baz', 'foo');
	}

/**
 * Tests that if server returns an status other than success it will return false
 *
 * @return void
 **/
	public function testExecuteBadReturn() {
		CakeLog::disable('stderr');

		$client = $this->getMock('GearmanClient', array('doBackground', 'returnCode'));
		 GearmanQueue::client($client);

		$client->expects($this->any())
			->method('returnCode')
			->will($this->returnValue(-1));

		$client->expects($this->at(0))
			->method('doBackground')
			->with('test_foo', json_encode('data'))
			->will($this->returnValue(-1));

		$this->logger->expects($this->at(1))
			->method('write')
			->with('debug', 'Creating background job: test_foo ("data")');

		$this->logger->expects($this->at(2))
			->method('write')
			->with('debug', 'Could not create background job for task foo and data "data". Got -1');

		$this->assertFalse( GearmanQueue::execute('foo', 'data'));
	}

/**
 * Tests you can set priorities on the jobs to be run
 *
 * @return void
 **/
	public function testExecutePriorities() {
		CakeLog::disable('stderr');

		$client = $this->getMock('GearmanClient', array('doLowBackground', 'doHighBackground'));
		 GearmanQueue::client($client);

		$client->expects($this->any())
			->method('returnCode')
			->will($this->returnValue(GEARMAN_SUCCESS));

		$client->expects($this->at(0))
			->method('doLowBackground')
			->with('test_foo', json_encode('data'))
			->will($this->returnValue(GEARMAN_SUCCESS));

		$client->expects($this->at(1))
			->method('doHighBackground')
			->with('test_foo', json_encode('data'))
			->will($this->returnValue(GEARMAN_SUCCESS));

		 GearmanQueue::execute('foo', 'data', 'low');
		 GearmanQueue::execute('foo', 'data', 'high');
	}
}
