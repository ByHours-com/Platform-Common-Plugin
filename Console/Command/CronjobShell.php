<?php

App::uses('AppShell', 'Console/Command');

/**
 * Helper Shell for app/Config/cronjobs.json
 *
 * @copyright Nodes ApS 2010-2013 <tech@nodes.dk>
 */
class CronjobShell extends AppShell {

/**
 * Gets the option parser instance and configures it.
 * By overriding this method you can configure the ConsoleOptionParser before returning it.
 *
 * @return ConsoleOptionParser
 * @link http://book.cakephp.org/2.0/en/console-and-shells.html#Shell::getOptionParser
 */
	public function getOptionParser() {
		$parser = parent::getOptionParser();

    $parser->addSubcommand('lint', array(
    	'help' => __('Validate syntax and basic data requirements for your cronjobs.json file'),
    	'parser' => array()
		));

		$parser->addSubcommand('show', array(
    	'help' => __('Convert cronjobs.json to a crontab compatible output. This will also lint the file to ensure correct syntax'),
    	'parser' => array()
		));

		$parser->addSubcommand('export', array(
    	'help' => __('Export all cronjobs.json cronjobs into crontab. This command will also remove project cronjobs that no longer exist in cronjobs.json'),
    	'parser' => array()
		));

		$parser->addSubcommand('clear', array(
    	'help' => __('Remove all cronjobs belonging to the current project from crontab'),
    	'parser' => array()
		));

    return $parser;
	}

/**
 * Lint the cronjobs.json file
 *
 * Check for syntax errors, missing fields and
 * potentially invalid values.
 * This script does very basic verification
 *
 * @return mixed
 */
	public function lint($silent = false) {
		$output = array();
		$output[] = '';
		$output[] = '---------------------------------';
		$output[] = 'Linting cronjobs:';
		$output[] = '---------------------------------';
		$output[] = '';

		// Check file exist
		$file = APP . 'Config/cronjobs.json';
		if (!file_exists($file)) {
			return $this->error('Sorry', 'app/Config/cronjobs.json does not exist');
		}

		// Check if file has content
		$content = file_get_contents($file);
		if (empty($content)) {
			return $this->error('Error', 'The app/Config/cronjobs.json file is empty');
		}

		// Check if we can parse the JSON file
		$cronjobs = json_decode($content, true);
		if (empty($cronjobs)) {
			return $this->error('Error', 'Failed to JSON decode app/Config/cronjobs.json file. Syntax error perhaps?');
		}

		// Validate format
		if (!is_array($cronjobs)) {
			return $this->error('Error', 'Expected an array after JSON decode. Syntax error perhaps?');
		}

		// The following keys should be present and not empty
		$basicKeys = array('name', 'mailto', 'minute', 'hour', 'day_of_month', 'month', 'day_of_week', 'command');

		$errors = 0;
		foreach ($cronjobs as $k => $cronjob) {
			$output[] = sprintf('Cronjob #%d', $k);
			$output[] = '---------------------------------';

			foreach ($basicKeys as $key) {
				$output[] = 'Key: "' . $key  . '"';
				if (!isset($cronjob[$key])) {
					$output[] = '--> [Error] Missing';
					$errors++;
				} elseif (empty($cronjob[$key])) {
					$output[] = '--> [Error] must not be empty';
					$errors++;
				} else {
					$output[] = '--> OK';
				}
			}

			// is active is special, it's value should be boolean
			$output[] = 'Key: "is_active"';
			if (!isset($cronjob['is_active'])) {
				$output[] = '--> [Error] Missing';
				$errors++;
			} elseif (!is_bool($cronjob['is_active'])) {
				$output[] = '--> [Error] Must be a boolean';
				$errors++;
			} else {
				$output[] = '--> OK';
			}
		}

		if (!$silent || $errors > 0) {
			$this->error('Validation errors', join("\n", $output));
		}

		return $cronjobs;
	}

/**
 * Run lint on the cronjobs file and display
 * a human-readable list of all cronjobs the site project
 *
 * @return void
 */
	public function show() {
		$this->out('');
		$this->out('---------------------------------');
		$this->out('Listing application cronjobs');
		$this->out('---------------------------------');
		$this->out('');

		$cronjobs = $this->_loadApplicationCronjobs();
		foreach ($cronjobs as $name => $cronjob) {
			$this->out($name);
			$this->out($cronjob);
		}

		$this->out('');
	}

/**
 * Export all cronjobs for this project into crontab
 *
 * This command will modify the crontab file for the current user
 * It should not touch cronjobs that doesn't belong to this project though
 *
 * @return void
 */
	public function export() {
		$this->out('');
		$this->out('---------------------------------');
		$this->out('Exporting cronjobs');
		$this->out('---------------------------------');
		$this->out('');

		$existingCronjobs	= $this->_loadExistingCronjobs();
		$application = $this->_loadApplicationCronjobs();

		foreach ($existingCronjobs as $name => $cronjob) {
			// Skip cronjobs that doesn't match our own project name
			$pattern = '/^# ' . Nodes\Environment::getProjectName() . ' \- /sim';
			if (false === preg_match($pattern, $name)) {
				continue;
			}

			// Remove cronjobs not defined in the current cronjobs.json file
			if (!in_array($name, $application)) {
				unset($existingCronjobs[$name]);
				continue;
			}
		}

		// Merge our cronjobs.json into the existing list
		$existingCronjobs += $application;

		$this->_writeCrontabFile($existingCronjobs);
		$this->out('Done.');
	}

/**
 * Remove all cronjobs for this project from crontab
 *
 * This command will modify the crontab file for the current user
 * It should not touch cronjobs that doesn't belong to this project though
 *
 * @return void
 */
	public function clear() {
		$existingCronjobs	= $this->_loadExistingCronjobs();
		foreach ($existingCronjobs as $name => $value) {
			// Skip cronjobs that doesn't match our own project name
			$pattern = '/^# ' . Nodes\Environment::getProjectName() . ' \- /sim';
			if (false === preg_match($pattern, $name)) {
				continue;
			}

			// Wipe cronjobs that belongs to this project
			unset($existingCronjobs[$name]);
		}

		$this->_writeCrontabFile($existingCronjobs);
	}

/**
 * Load cronjobs.json and lint the file
 *
 * Then convert the complex array to the same
 * format as `_parseExistingCronjobs` does
 *
 * @return array
 */
	protected function _loadApplicationCronjobs() {
		$cronjobs = $this->lint(true);

		$list = array();
		foreach ($cronjobs as $cronjob) {
			$name = sprintf('# %s - %s', Nodes\Environment::getProjectName(), $cronjob['name']);
			$command = sprintf('cd /var/www/%s/htdocs/ && %s', Nodes\Environment::getProjectName(), $cronjob['command']);
			$command = sprintf("%s\t%s\t%s\t%s\t%s\t%s", $cronjob['minute'], $cronjob['hour'], $cronjob['day_of_month'], $cronjob['month'], $cronjob['day_of_week'], $command);

			$list[$name] = $command;
		}

		return $list;
	}

/**
 * Load all existing cronjobs for the current user
 *
 * We are simply executing `crontab -l` and
 * passing the result to a parser that trarnsform it
 * to a array where the key is the `project name` + `cronjob name`
 *
 * @return array
 */
	protected function _loadExistingCronjobs() {
		$result = Nodes\Command::execute('crontab -l');
		return $this->_parseExistingCronjobs($result['stdout']);
	}

/**
 * Giving a string, parse out all cronjob lines
 *
 * A cronjob consist of two lines:
 *  - 1st line is a comment with project name + cronjob name
 *  - 2nd line is the actual cronjob command
 *
 * The return format is an flat array where
 * the key is the 1st line and the value is the 2nd line
 *
 * @param string $text Raw output from `crontab -l`
 * @return array
 */
	protected function _parseExistingCronjobs($text) {
		preg_match_all("/(# .*?)\n(.*?)\n/sim", $text, $r);
		return Hash::combine($r, '1.{n}', '2.{n}');
	}

/**
 * Write a crontab file and load it
 *
 * The input format is expected to be the same as
 * the output of `_parseExistingCronjobs`
 *
 * @param array $cronjobs
 * @return mixed
 */
	protected function _writeCrontabFile($cronjobs) {
		$lines = array();
		foreach ($cronjobs as $name => $command) {
			$lines[] = $name;
			$lines[] = $command;
		}

		// Crontab requires a newline before EOF
		$lines[] = "";

		$content = implode("\n", $lines);

		$tmp = tempnam(TMP, 'cron_');
		file_put_contents($tmp, $content);
		$result = Nodes\Command::execute('crontab ' . $tmp);
		unlink($tmp);
		if ($result['exit_code'] == 0) {
			return true;
		}

		$this->error('Failed to update crontab', $result['stderr']);
	}

}
