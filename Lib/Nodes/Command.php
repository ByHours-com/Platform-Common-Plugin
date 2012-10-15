<?php
namespace Nodes;

class Command {

	public static function execute($command, $cwd = null, $env = null, $allowSudo = true) {
		$descriptorspec = array(
			0 => array("pipe", "r"), // stdin  is a pipe that the child will read from
			1 => array("pipe", "w"), // stdout is a pipe that the child will write to
			2 => array("pipe", "w")  // stderr is a pipe that the child will write to
		);

		CakeLog::debug("Executing command: $command");
		if (!empty($cwd)) {
			CakeLog::debug("--> cwd = $cwd");
		}

		// Execute command
		$process = proc_open($command, $descriptorspec, $pipes, $cwd, $env);
		if (!is_resource($process)) {
			CakeLog::err("Could not execute command: $command");
			throw new Exception("Could not execute command: $command");
		}

		// close stdin
		fclose($pipes[0]);

		$stdout = $stderr = $buffer = $errbuf = "";
		while (($buffer = fgets($pipes[1], 1024)) != NULL || ($errbuf = fgets($pipes[2], 1024)) != NULL) {
			if (!empty($buffer)) {
				$stdout .= $buffer;
				CakeLog::debug('--> stdout: ' . trim($buffer));
			}

			if (!empty($errbuf)) {
				$stderr .= $errbuf;
				CakeLog::err('--> stderr: ' . trim($errbuf));
			}
		}

		fclose($pipes[1]);
		fclose($pipes[2]);

		$exit_code = proc_close($process);
		CakeLog::debug("--> exit_code: $exit_code");

		unset($pipes[0], $pipes[1], $pipes[2], $pipes);
		unset($descriptorspec[0], $descriptorspec[1], $descriptorspec[2], $descriptorspec);

		return compact('stdout', 'stderr', 'exit_code', 'command', 'cwd', 'env');
	}
}
