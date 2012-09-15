<?php

App::uses('AppShell', 'Console/Command');

/**
 * GearmanD Shell task
 *
 * Helps set up blocking and non-blocking Gearman worker
 *
 */
class GearmanWorkerTask extends AppShell {

/**
 * Internal reference to the GearmanWorker
 *
 * @var GearmanWorker
 */
	protected $_worker;

/**
 * The settings array for GearmanDTask
 *
 * @var array
 */
	protected $_settings = array(
		'name' => 'Gearman',
		'verbose' => true
	);

/**
 * Internal reference to the CakeEventManager
 *
 * @var CakeEventManager
 */
	protected $_eventManager;

/**
 * A wrapper for the normal GearmanWorker::work() method, with some additional settings
 *
 * The options are:
 *  - name: Just a human name of the worker that will be logged out (default: Gearman)
 *  - verbose: True to make non-blocking worker output (default: true)
 *
 * @param GearmanWorker $worker
 * @param array $settings
 * @return void
 */
	public function work(GearmanWorker $worker, $settings = array()) {
		$this->setWorker($worker);
		$this->setSettings($settings);

		$this->_setupEvents();

		$this->log(sprintf("Starting %s worker (non-blocking)", $this->_settings['name']), 'info');
		return $this->_work();
	}

/**
 * Get the GearmanWorker object
 *
 * @return GearmanWorker
 */
	public function getWorker() {
		return $this->_worker;
	}

/**
 * Change the worker object
 *
 * Really only make sense to do in Unit Testing :)
 *
 * @param GearmanWorker $worker
 * @return void
 */
	public function setWorker(GearmanWorker $worker) {
		$this->_worker = $worker;
	}

/**
 * Get all or a specific settings key
 *
 * @param string $path Hash::get() compatible path, NULL for everything
 * @return mixed
 */
	public function getSettings($path = null) {
		if (empty($path)) {
			return $this->_settings;
		}

		return Hash::get($this->_settings, $path);
	}

/**
 * Override or change a settings key
 *
 * If $key is an array, it will be merged with the existing settings
 * If $key is a string, the settings $key will be changed to $value
 *
 * @param mixed $key
 * @param mixed $value
 * @return void
 */
	public function setSettings($key, $value = null) {
		if (is_array($key)) {
			$this->_settings = $key + $this->_settings;
			return;
		}

		$this->_settings = Hash::insert($this->_settings, $key, $value);
	}

/**
 * Get the Event Manager
 *
 * If none exist, it uses the default CakeEventManager instance
 *
 * @return CakeEventManager
 */
	public function getEventManager() {
		if (is_null($this->_eventManager)) {
			$this->setEventManager(CakeEventManager::instance());
		}

		return $this->_eventManager;
	}

/**
 * Change the EventManager
 *
 * @param CakeEventManager $manager
 * @return void
 */
	public function setEventManager(CakeEventManager $manager) {
		$this->_eventManager = $manager;
	}

/**
 * Set up the GearmanWorker to run in non-blocking mode
 *
 * This allows you to do work "in between" jobs when its idle
 *
 * Events emitted:
 *  - Gearman.beforeWork: Called before GearmanWorker::work()
 *  - Gearman.afterWork: Called after GearmanWorker::work() actually processed a job
 *  - Gearman.beforeWait: Called before Gearman::wait() is called
 *  - Gearman.afterWait: Called after Gearman::wait() is called
 *
 * N.B: Gearman.beforeWork will be called even if there is no jobs to be processed,
 * it's meant as a simple wrapper around GearmanWorker::work() and GearmanWorker::wait()
 *
 * All events can abort the infinite loop by calling $event->stopPropagation();
 *
 * @return void
 */
	protected function _work() {
		$this->_worker->addOptions(GEARMAN_WORKER_NON_BLOCKING);

		// Infinite loop of doom until we die!
		while (true) {
			$this->hr();

			if (!$this->_triggerEvent('Gearman.beforeWork')) { break; }
			$this->_worker->work();
			if (!$this->_triggerEvent('Gearman.afterWork')) { break; }

			// If we processed a job, don't bother to wait
			if ($this->_worker->returnCode() == GEARMAN_SUCCESS) {
				continue;
    		}

			if (!$this->_triggerEvent('Gearman.beforeWait')) { break; }
			$this->_worker->wait();
			if (!$this->_triggerEvent('Gearman.afterWait')) { break; }
		}
	}

/**
 * Setup some internal event listeners
 *
 * @return void
 */
	protected function _setupEvents() {
		$this->_checkForNoActiveConnectionsEvent();

		if ($this->getSettings('verbose')) {
			$this->getEventManager()->attach(function($event) { $event->subject->log('Gearman.beforeWork', 'debug'); }, 'Gearman.beforeWork');
			$this->getEventManager()->attach(function($event) { $event->subject->log('Gearman.afterWork', 'debug'); }, 'Gearman.afterWork');

			$this->getEventManager()->attach(function($event) { $event->subject->log('Gearman.beforeWait', 'debug'); }, 'Gearman.beforeWait');
			$this->getEventManager()->attach(function($event) { $event->subject->log('Gearman.afterWait', 'debug'); }, 'Gearman.afterWait');
		}
	}

/**
 * Trigger a Gearman event
 *
 * @param string $name The event name
 * @param mixed $data The event data
 * @return boolean If the event was stopped or not
 */
	protected function _triggerEvent($name, $data = null) {
		$event = $this->_getEvent($name, $data);
		$this->getEventManager()->dispatch($event);
		return !$event->isStopped();
	}

/**
 * Creates a CakeEvent with $this as subject
 *
 * @param string $name The event name
 * @param mixed $data The event data
 * @return CakeEvent
 */
	protected function _getEvent($name, $data = null) {
		return new CakeEvent($name, $this, $data);
	}

/**
 * Check if the worker have no active connections to a Gearman server
 *
 * @return boolean
 */
	protected function _checkForNoActiveConnectionsEvent() {
		$this->getEventManager()->attach(function($event) {
			if ($event->subject->getWorker()->returnCode() == GEARMAN_NO_ACTIVE_FDS) {
				$event->subject->log('We are not connected to any servers, so wait a bit before trying to reconnect.', 'warning');
				sleep(1);
  			}
		}, 'Gearman.afterWait');
	}
}
