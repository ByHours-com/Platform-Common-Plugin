<?php
App::uses('CakeLog', 'Log');

/**
 * Static facade class for communicating with a background job handler
 *
 **/
class GearmanQueue {

/**
 * Holds and instance for a server client
 *
 * @var GermanClient
 **/
	protected static $_client = null;

/**
 * Holds a list of servers the worker class should connect to
 *
 * @var string
 **/
	protected static $_servers = array('127.0.0.1');

/**
 * Configures servers to connect to.
 *
 * ## Example:
 *
 * ``WorkerQueue::config(array('servers' => array('some.server.com:4444', 'otherserver')));``
 *
 * @param array $settings
 * @return void
 **/
	public static function config($settings) {
		$servers = Hash::get($settings, 'servers');
		if (!empty($servers)) {
			static::$_servers = $servers;
		}
	}

/**
 * Returns the client instance to the backgrond jobs server
 * If first param is an instance of GearmanClient it will configure the queue to use it
 * IF first param is false it will unset configured client to defaults
 *
 * @param GearmanClient|boolean $client null to return current instance, GearmanClient instance to configure it
 * to passed value, false to reset to defaults
 * @return GearmanClient
 **/
	public static function client($client = null) {
		if ($client instanceof GearmanClient) {
			static::$_client = $client;
			static::_setServers(static::$_servers);
		}

		if ($client === false) {
			return static::$_client = null;
		}

		if (empty(static::$_client)) {
			static::$_client = new GearmanClient();
			static::_setServers(static::$_servers);
		}

		return static::$_client;
	}

/**
 * Configures internal client reference to use the list of specified servers
 *
 * @param array $servers list of servers to connect to
 * @return void
 **/
	protected static function _setServers(array $servers) {
		static::$_servers = $servers;
		static::$_client->addServers(implode(',', static::$_servers));
	}

/**
 * Returns the list of configured servers
 *
 * @return array
 **/
	public static function servers() {
		return static::$_servers;
	}

/**
 * Starts a new background task by passing some data to it with a priority
 *
 * @param string $taskName name of the task to be executed
 * @param mixed $data info to be passed to background task
 * @param sting $priority null for normal or either "low" or "high"
 * @return boolean success
 **/
	public static function execute($taskName, $data = null, $priority = null) {
		if (!empty($priority)) {
			$priority = strtolower($priority);
			if (!in_array($priority, array('low', 'high'))) {
				throw new InvalidArgumentException(sprintf('%s is not a valid priority, only accepting low and high', $priority));
			}
		}

		$data = json_encode($data);

		$taskName = Nodes\Environment::getProjectName() . '_' . $taskName;
		CakeLog::debug(sprintf('Creating background job: %s (%s)', $taskName, $data));

		$job = static::client()->{'do' . ucFirst($priority) . 'Background'}($taskName,  $data);
		if (static::client()->returnCode() !== GEARMAN_SUCCESS) {
			CakeLog::error(sprintf('Could not create background job for task %s and data %s. Got %s (%s)', $taskName, $data, $job, static::client()->error()));
			return false;
		}

		return true;
	}

}

