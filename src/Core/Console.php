<?php

namespace Friendica\Core;

/**
 * Description of Console
 *
 * @author Hypolite Petovan <mrpetovan@gmail.com>
 */
class Console extends \Asika\SimpleConsole\Console
{
	// Disables the default help handling
	protected $helpOptions = [];
	protected $customHelpOptions = ['h', 'help', '?'];

	protected function getHelp()
	{


		$help = <<<HELP
Usage: bin/console [--version] [-h|--help|-?] <command> [<args>] [-v]

Commands:
	config                 Edit site config
	createdoxygen          Generate Doxygen headers
	docbloxerrorchecker    Check the file tree for DocBlox errors
	extract                Generate translation string file for the Friendica project (deprecated)
	globalcommunityblock   Block remote profile from interacting with this node
	globalcommunitysilence Silence remote profile from global community page
	help                   Show help about a command, e.g (bin/console help config)
	maintenance            Set maintenance mode for this node
	php2po                 Generate a messages.po file from a strings.php file

Options:
	-h|--help|-? Show help information
	-v           Show more debug information.
HELP;
		return $help;
	}

	protected function doExecute()
	{
		if ($this->getOption('v')) {
			$this->out('Executable: ' . $this->executable);
			$this->out('Arguments: ' . var_export($this->args, true));
			$this->out('Options: ' . var_export($this->options, true));
		}

		$showHelp = false;
		$subHelp = false;
		$command = null;

		if ($this->getOption('version')) {
			$this->out('Friendica Console version ' . FRIENDICA_VERSION);

			return 0;
		} elseif ((count($this->options) === 0 || $this->getOption($this->customHelpOptions) === true || $this->getOption($this->customHelpOptions) === 1) && count($this->args) === 0
		) {
			$showHelp = true;
		} elseif (count($this->args) >= 2 && $this->getArgument(0) == 'help') {
			$command = $this->getArgument(1);
			$subHelp = true;
			array_shift($this->args);
			array_shift($this->args);
		} elseif (count($this->args) >= 1) {
			$command = $this->getArgument(0);
			array_shift($this->args);
		}

		if (is_null($command)) {
			$this->out($this->getHelp());
			return 0;
		}

		$console = $this->getSubConsole($command);

		if ($subHelp) {
			$console->setOption($this->customHelpOptions, true);
		}

		return $console->execute();
	}

	private function getSubConsole($command)
	{
		if ($this->getOption('v')) {
			$this->out('Command: ' . $command);
		}

		$subargs = $this->args;
		array_unshift($subargs, $this->executable);

		$subconsole = null;

		switch ($command) {
			case 'config' : $subconsole = new Console\Config($subargs);
				break;
			case 'createdoxygen' : $subconsole = new Console\CreateDoxygen($subargs);
				break;
			case 'docbloxerrorchecker' : $subconsole = new Console\DocBloxErrorChecker($subargs);
				break;
			case 'extract' : $subconsole = new Console\Extract($subargs);
				break;
			case 'globalcommunityblock': $subconsole = new Console\GlobalCommunityBlock($subargs);
				break;
			case 'globalcommunitysilence': $subconsole = new Console\GlobalCommunitySilence($subargs);
				break;
			case 'maintenance': $subconsole = new Console\Maintenance($subargs);
				break;
			case 'php2po': $subconsole = new Console\PhpToPo($subargs);
				break;
			default:
				throw new \Asika\SimpleConsole\CommandArgsException('Command ' . $command . ' doesn\'t exist');
		}

		foreach ($this->options as $name => $value) {
			$subconsole->setOption($name, $value);
		}

		return $subconsole;
	}

}
