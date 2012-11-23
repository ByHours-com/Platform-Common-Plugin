<?php

/**
 * Shell for starting up a Gearman shell.
 *
 * @package Common
 * @author <mien@nodesagency.no>
 * @copyright Nodes ApS 2010-2012 <tech@nodes.dk>
 */
abstract class GearmanShell extends AppShell {

/**
 * Lists of tasks
 *
 * @return void
 */
	public $tasks = array(
		'Common.ProccessManagement',
		'Common.GearmanWorker'
	);

/**
 * Internal stored instance of a GearmanWorker
 *
 * @var GearmanWorker
 */
	protected $_worker = null;

/**
 * Gets the option parser instance and configures it.
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::getOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();
		return $parser
			->description('Setup a Gearman server for running the subtasks')
			->addSubCommand('server', array(
				'help' => 'Starts a gearman worker server with all the function listeners found in task classes',
			));
	}

/**
 * Starts a Gearman worker long-running process
 *
 * @return void
 */
	public function server() {
		$this->ProccessManagement->mutex($this->_getLockFile());
		$this->_setupSignals();
		$this->_setupWorkers();
		$this->_setupEvents();

		$this->GearmanWorker->work($this->_worker, array('verbose' => Configure::read('debug') > 0));
	}

	/**
 * Setup Process Control signals
 *
 * Listen for SIGTERM and SIGHUP and set flag to shut down the script
 *
 * @return void
 */
	protected function _setupSignals() {
		$stopServerLoop = function() { Configure::write('Signal.shutdown', true); };
		$this->ProccessManagement->signal(SIGTERM, $stopServerLoop);
		$this->ProccessManagement->signal(SIGHUP, $stopServerLoop);
	}

/**
 * Setup events for GearmanWorker
 *
 * Check for kill signal before we start to process Gearman jobs (See _setupSignals)
 *
 * @return void
 */
	protected function _setupEvents() {
		// Kill script
		$this->GearmanWorker->getEventManager()->attach(function($event) {
			if (Configure::read('Signal.shutdown')) {
				$event->subject->log('Got kill signal, exiting....');
				$event->stopPropagation();
			}
		}, 'Gearman.beforeWork');

		// Keep-a-live
		$this->GearmanWorker->getEventManager()->attach(function($event) {
			// Make sure MySQL doesn't go away
			ClassRegistry::init('Backend.Application')->getDataSource()->getConnection()->exec('-- ping');
		}, 'Gearman.beforeWait');
	}

/**
 * Setup workers and their callbacks for Gearman
 *
 * @return void
 */
	protected function _setupWorkers() {
		$worker = $this->_worker();
		foreach (Hash::normalize($this->tasks) as $t => $conf) {
			list($plugin, $class) = pluginSplit($t);

			if (!method_exists($this->{$class}, 'workerMethods')) {
				continue;
			}

			if (method_exists($this->{$class}, 'startup')) {
				$this->{$class}->startup();
			}

			$methods = $this->{$class}->workerMethods();
			foreach ($methods as $name => $method) {
				$name = Nodes\Environment::getProjectName() .  '_' . $name;

				$callback = $this->_addWorkerFunction($class, $method);

				$worker->addFunction($name, $callback);
				$this->log("Registered function for $name", 'info');
			}
		}
	}


/**
 * Construct lambda function for GearmanWorker
 *
 * @param string $class
 * @param string $method
 * @return Closure
 */
	protected function _addWorkerFunction($class, $method) {
		return function() use($class, $method) {
			$key = Inflector::underscore($method);

			$return = call_user_method_array($method, $this->{$class}, func_get_args());

			return $return;
		};
	}

/**
 * Auxiliary function to create a Gearman worker class
 *
 * @param Gearman|boolean $worker if an instance of a worker is passed it will be used for the server startup
 * if false is passed it will result to defaults.
 * if null is passed then it will just return currently stored instance
 * @return GearmanWorker
 */
	protected function _worker($worker = null) {
		if ($worker instanceof GearmanWorker) {
			$this->_worker = $worker;
			$this->_setServers();
		}

		if ($worker === false) {
			$this->_worker = null;
			return;
		}

		if (empty($this->_worker)) {
			$this->_worker = new GearmanWorker();
			$this->_setServers();
		}

		return $this->_worker;
	}

/**
 * Auxiliary function to set the servers for the worker to be connected to.
 *
 * @return void
 */
	protected function _setServers() {
		$servers = Configure::read('Gearman.servers');
		if (empty($servers)) {
			$servers = array('127.0.0.1');
		}
		$this->_worker->addServers(implode(',', $servers));
	}

}
