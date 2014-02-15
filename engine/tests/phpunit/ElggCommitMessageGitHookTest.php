<?php
/**
 * Tests the commit message validation shell script used by the git hook and travis
 */

class ElggCommitMessageGitHookTest extends PHPUnit_Framework_TestCase {
	protected $scriptsDir;
	protected $filesDir;
	protected $validateScript;

	protected $validTypes = array(
		'feature',
		'fix',
		'docs',
		'chore',
		'perf',
		'security'
	);

	public function setUp() {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$this->markTestSkipped('Can only test in *nix envs.');
		}

		$this->scriptsDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/.scripts/';
		$this->filesDir = dirname(__FILE__) . '/test_files/commit_messages/';
		$this->validateScript = "php {$this->scriptsDir}validate_commit_msg.php";

		parent::setUp();
	}
	
	/**
	 * Test failures for missing input
	 */
	public function testInvalidInputs() {
		// have to pass an empty arg because it looks for stdin
		$cmd = "$this->validateScript ''";
		$result = $this->runCmd($cmd, $output);
		$this->assertFalse($result, $output);

		$cmd = "$this->validateScript /dev/null";
		$result = $this->runCmd($cmd, $output);
		$this->assertFalse($result, $output);

		$cmd = "echo '' | $this->validateScript";
		$result = $this->runCmd($cmd, $output);
		$this->assertFalse($result, $output);
	}

	public function testInvalidMessage() {
		$cmd = "$this->validateScript {$this->filesDir}invalid_format.txt";
		$result = $this->runCmd($cmd, $output);
		$this->assertFalse($result, $output);
	}

	public function testFile() {
		$cmd = "$this->validateScript {$this->filesDir}valid.txt";
		$result = $this->runCmd($cmd, $output);
		$this->assertTrue($result, $output);
	}
	
	public function testPipe() {
		$msg = escapeshellarg(file_get_contents("{$this->filesDir}valid.txt"));
		$cmd = "echo $msg | $this->validateScript";
		$result = $this->runCmd($cmd, $output);
		$this->assertTrue($result, $output);
	}

	public function testArg() {
		$msg = escapeshellarg(file_get_contents("{$this->filesDir}valid.txt"));
		$cmd = "$this->validateScript $msg";
		$result = $this->runCmd($cmd, $output);
		$this->assertTrue($result, $output);
	}

	/**
	 * Executes a command and returns true if the cmd
	 * exited with 0.
	 * 
	 * @param string $cmd
	 */
	protected function runCmd($cmd, &$output) {
		$output = array();
		$exit = 0;
		exec($cmd, $output, $exit);

		$output = implode("\n", $output);

		return $exit > 0 ? false : true;
	}
}