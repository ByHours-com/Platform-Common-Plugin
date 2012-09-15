<?php
App::uses('WorkerTask', 'Console/Command');

/**
 * ProccessManagement Task
 *
 * Helps with CLI handling of mutex and signals
 *
 * IMPORTANT:
 *
 * Remember to add the following code at the top of Config/bootstrap.php
 * if you do not, signal handling will fail silently
 *
 * ```
 * if (php_sapi_name() === 'cli') {
 *	declare(ticks = 1);
 * }
 * ```
 */
class ProccessManagementTask extends AppShell {

/**
 * We need our lock file to be stored as a class property so it
 * won't get free()'d because there is no reference to it.
 *
 * That would free the lock on the file too :)
 *
 * @var resource
 */
	protected $_lockHandle;

/**
 * List of valid UNIX signals
 *
 * Used to map between the signal number in the handler
 * and its real name
 *
 * @var array
 */
	protected static $_signals = array (
		 1 => 'SIGHUP',
		 2 => 'SIGINT',
		 3 => 'SIGQUIT',
		 4 => 'SIGILL',
		 5 => 'SIGTRAP',
		 6 => 'SIGABRT',
		 7 => 'SIGBUS',
		 8 => 'SIGFPE',
		 9 => 'SIGKILL',
		10 => 'SIGUSR1',
		11 => 'SIGSEGV',
		12 => 'SIGUSR2',
		13 => 'SIGPIPE',
		14 => 'SIGALRM',
		15 => 'SIGTERM',
		16 => 'SIG16',
		17 => 'SIGCHLD',
		18 => 'SIGCONT',
		19 => 'SIGSTOP',
		20 => 'SIGTSTP',
		21 => 'SIGTTIN',
		22 => 'SIGTTOU',
		23 => 'SIGURG',
		24 => 'SIGXCPU',
		25 => 'SIGXFSZ',
		26 => 'SIGVTALRM',
		27 => 'SIGPROF',
		28 => 'SIGWINCH',
		29 => 'SIGIO',
		30 => 'SIGPWR',
		31 => 'SIGSYS',
		32 => 'SIG32',
		33 => 'SIG33',
		34 => 'SIGRTMIN',
		35 => 'SIGRTMIN+1',
		36 => 'SIGRTMIN+2',
		37 => 'SIGRTMIN+3',
		38 => 'SIGRTMIN+4',
		39 => 'SIGRTMIN+5',
		40 => 'SIGRTMIN+6',
		41 => 'SIGRTMIN+7',
		42 => 'SIGRTMIN+8',
		43 => 'SIGRTMIN+9',
		44 => 'SIGRTMIN+10',
		45 => 'SIGRTMIN+11',
		46 => 'SIGRTMIN+12',
		47 => 'SIGRTMIN+13',
		48 => 'SIGRTMIN+14',
		49 => 'SIGRTMIN+15',
		50 => 'SIGRTMAX-14',
		51 => 'SIGRTMAX-13',
		52 => 'SIGRTMAX-12',
		53 => 'SIGRTMAX-11',
		54 => 'SIGRTMAX-10',
		55 => 'SIGRTMAX-9',
		56 => 'SIGRTMAX-8',
		57 => 'SIGRTMAX-7',
		58 => 'SIGRTMAX-6',
		59 => 'SIGRTMAX-5',
		60 => 'SIGRTMAX-4',
		61 => 'SIGRTMAX-3',
		62 => 'SIGRTMAX-2',
		63 => 'SIGRTMAX-1',
		64 => 'SIGRTMAX'
	);

/**
 * Make sure to get exclusive right to run this process, based on a file
 *
 * Should be used together with ProccessManagement->getLockFile()
 *
 * @param string $lockFile
 * @return void
 */
	public function mutex($lockFile) {
		$this->log(sprintf('Using lock file: %s', $lockFile), 'info');

		$this->_lockHandle = fopen($lockFile, 'w+');
		if (!flock($this->_lockHandle, LOCK_EX | LOCK_NB)) {
			$this->log(sprintf('Could not get exclusive lock, other process must be running'), 'warning');
			$this->_stop(-1);
		}

		$this->log(sprintf('My PID: %s', posix_getpid()), 'info');
	}

/**
 * Register signals and when they are triggered, write a Signal.shutdown property
 *
 * @param mixed $signal Can be any of the SIG* constants or its human name
 * @param mixed $callback Can be anything PHP callable
 * @return void
 */
	public function signal($signal, $callback) {
		if (is_numeric($signal)) {
			if (!array_key_exists($signal, static::$_signals)) {
				throw new Exception('Unknown signal: %s', $signal);
			}
			$signalId = $signal;
			$signalName = static::$_signals[$signal];
		} else {
			if (false === ($pos = array_search($signal, static::$_signals))) {
				throw new Exception('Unknown signal: %s', $signal);
			}
			$signalId = $pos;
			$signalName = $signal;
		}

		if (!is_callable($callback)) {
			throw new Exception('Argument provided as callable is not callable');
		}

		if (!pcntl_signal($signalId, $callback)) {
			throw new Exception(sprintf('Could not subscribe to signal %s (%d)', $signalName, $signalId));
		}

		$this->log(sprintf('Successfully subscribed to signal %s (%d)', $signalName, $signalId), 'debug');
	}
}

