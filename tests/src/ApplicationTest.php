<?php

namespace Deploid;

class ApplicationTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var Application
	 */
	protected $application;

	/**
	 * @var string
	 */
	protected $path;

	protected function setUp() {
		$this->application = new Application();
		$this->path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . strtolower(__NAMESPACE__) . DIRECTORY_SEPARATOR . uniqid();
	}

	protected function tearDown() {
		$this->application = null;
		$this->path = null;
	}

	/**
	 * @before
	 * @param string $path
	 */
	protected function resetWorkDir() {
		$this->removeWorkDir($this->path);
		$this->createWorkDir($this->path);
	}

	/**
	 * @param string $path
	 */
	private function createWorkDir($path) {
		$process = new \Symfony\Component\Process\Process('mkdir ' . $path);
		$process->run();
	}

	/**
	 * @param string $path
	 */
	private function removeWorkDir($path) {
		$process = new \Symfony\Component\Process\Process('rm -rf ' . $path);
		$process->run();
	}

	/**
	 * @covers \Deploid\Application::deploidStructureValidate
	 */
	public function testFailDeploidStructureValidate() {
		$releasesDir = 'releases';
		$deploidFile = 'deploid.log';
		$currentLink = 'current';

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['files'][] = $deploidFile;
		$structure['links'][] = $currentLink . ':' . $releasesDir;
		$this->application->setStructure($structure);

		$structureInvalid = [];
		$this->application->makeStructure($this->path, $structureInvalid);

		$payload = $this->application->deploidStructureValidate($this->path);

		$this->assertNotEquals(0, $payload->getCode());
		$this->assertEquals(count($structure), count($payload->getMessage()));
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir);
		$this->assertFileNotExists($this->path . DIRECTORY_SEPARATOR . $deploidFile);
		$this->assertFalse(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
	}

	/**
	 * @covers \Deploid\Application::deploidStructureValidate
	 */
	public function testSuccessDeploidStructureValidate() {
		$releasesDir = 'releases';
		$deploidFile = 'deploid.log';
		$currentLink = 'current';

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['files'][] = $deploidFile;
		$structure['links'][] = $currentLink . ':' . $releasesDir;
		$this->application->setStructure($structure);

		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidStructureValidate($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir);
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $deploidFile);
		$this->assertTrue(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
	}

	/**
	 * @covers \Deploid\Application::deploidStructureInit
	 */
	public function testDeploidStructureInit() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());
		$sharedDir = 'shared';
		$deploidFile = 'deploid.log';
		$currentLink = 'current';

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$structure['dirs'][] = $sharedDir;
		$structure['files'][] = $deploidFile;
		$structure['links'][] = $currentLink . ':' . $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$this->application->setStructure($structure);

		$payload = $this->application->deploidStructureInit($this->path);

		$structureScaned = $this->application->scanStructure($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $sharedDir);
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $deploidFile);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertTrue(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
		$this->assertEquals(realpath($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName), realpath(readlink($this->path . DIRECTORY_SEPARATOR . $currentLink)));
		$this->assertEquals($this->application->sortStructure($structure), $this->application->sortStructure($structureScaned));
	}

	/**
	 * @covers \Deploid\Application::deploidStructureClean
	 */
	public function testDeploidStructureClean() {
		$releasesDir = 'releases';
		$sharedDir = 'shared';
		$needlessDir = 'needless';
		$deploidFile = 'deploid.log';
		$needlessFile = 'needless.log';
		$currentLink = 'current';
		$needlessLink = 'needlesslink';

		$structureClean = [];
		$structureClean['dirs'][] = $releasesDir;
		$structureClean['dirs'][] = $sharedDir;
		$structureClean['files'][] = $deploidFile;
		$structureClean['links'][] = $currentLink . ':' . $releasesDir;
		$this->application->setStructure($structureClean);

		$structureDirty = [];
		$structureDirty['dirs'][] = $releasesDir;
		$structureDirty['dirs'][] = $sharedDir;
		$structureDirty['dirs'][] = $needlessDir;
		$structureDirty['files'][] = $deploidFile;
		$structureDirty['files'][] = $needlessFile;
		$structureDirty['links'][] = $currentLink . ':' . $releasesDir;
		$structureDirty['links'][] = $needlessLink . ':' . $needlessDir;
		$this->application->makeStructure($this->path, $structureDirty);

		$payload = $this->application->deploidStructureClean($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $sharedDir);
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $needlessDir);
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $deploidFile);
		$this->assertFileNotExists($this->path . DIRECTORY_SEPARATOR . $needlessFile);
		$this->assertFileNotExists($this->path . DIRECTORY_SEPARATOR . $needlessLink);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertTrue(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
		$this->assertEquals(realpath($this->path . DIRECTORY_SEPARATOR . $releasesDir), realpath(readlink($this->path . DIRECTORY_SEPARATOR . $currentLink)));
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseExist
	 */
	public function testSuccessDeploidReleaseExist() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseExist($releaseName, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseExist
	 */
	public function testFailDeploidReleaseExist() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseExist($releaseName, $this->path);

		$this->assertNotEquals(0, $payload->getCode());
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseCreate
	 */
	public function testDeploidReleaseCreate() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseCreate($releaseName, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseRemove
	 */
	public function testDeploidReleaseRemove() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseRemove($releaseName, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseList
	 */
	public function testDeploidReleaseList() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseList($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertEquals([$releaseName], $payload->getMessage());
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseLatest
	 */
	public function testDeploidReleaseLatest() {
		$releasesDir = 'releases';
		$releaseNameFirst = date($this->application->getReleaseNameFormat());
		$releaseNameLast = date($this->application->getReleaseNameFormat(), time() + 3600);

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseLatest($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertEquals($releaseNameLast, $payload->getMessage());
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseCurrent
	 */
	public function testDeploidReleaseCurrent() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());
		$currentLink = 'current';

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$structure['links'][] = $currentLink . ':' . $releasesDir . DIRECTORY_SEPARATOR . $releaseName;

		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseCurrent($this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertEquals($releaseName, $payload->getMessage());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
		$this->assertTrue(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
		$this->assertEquals(realpath($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName), realpath(readlink($this->path . DIRECTORY_SEPARATOR . $currentLink)));
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseSetup
	 */
	public function testDeploidReleaseSetup() {
		$releasesDir = 'releases';
		$releaseNameFirst = date($this->application->getReleaseNameFormat());
		$releaseNameLast = date($this->application->getReleaseNameFormat(), time() + 3600);
		$currentLink = 'current';


		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast;
		$structure['links'][] = $currentLink . ':' . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseSetup($releaseNameLast, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertContains($releaseNameLast, $payload->getMessage());
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast);
		$this->assertTrue(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
		$this->assertEquals(realpath($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast), realpath(readlink($this->path . DIRECTORY_SEPARATOR . $currentLink)));
	}

	/**
	 * @covers \Deploid\Application::deploidReleaseRotate
	 */
	public function testDeploidReleaseRotate() {
		$releasesDir = 'releases';
		$releaseNameFirst = date($this->application->getReleaseNameFormat());
		$releaseNameLast = date($this->application->getReleaseNameFormat(), time() + 3600);
		$quantity = 1;

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast;
		$this->application->makeStructure($this->path, $structure);

		$payload = $this->application->deploidReleaseRotate($quantity, $this->path);

		$this->assertEquals(0, $payload->getCode());
		$this->assertDirectoryNotExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameFirst);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseNameLast);
		$this->assertCount($quantity, glob($this->path . DIRECTORY_SEPARATOR . $releasesDir));
	}

	/**
	 * @covers \Deploid\Application::absolutePath
	 */
	public function testImmutableAbsolutePath() {
		$cwd = getcwd();
		$absPath = $cwd . DIRECTORY_SEPARATOR . 'dirname';

		$path = $this->application->absolutePath($absPath, $cwd);

		$this->assertEquals($absPath, $path);
	}

	/**
	 * @covers \Deploid\Application::absolutePath
	 */
	public function testMutableAbsolutePath() {
		$cwd = getcwd();
		$relPath = '.' . DIRECTORY_SEPARATOR . 'dirname';

		$path = $this->application->absolutePath($relPath, $cwd);

		$this->assertEquals($cwd . DIRECTORY_SEPARATOR . $relPath, $path);
	}

	/**
	 * @covers \Deploid\Application::makeStructure
	 */
	public function testMakeStructure() {
		$releasesDir = 'releases';
		$releaseName = date($this->application->getReleaseNameFormat());
		$logsDir = 'logs';
		$historyFile = 'history.txt';
		$deploidFile = 'deploid.log';
		$currentLink = 'current';

		$structure = [];
		$structure['dirs'][] = $releasesDir;
		$structure['dirs'][] = $releasesDir . DIRECTORY_SEPARATOR . $releaseName;
		$structure['dirs'][] = $logsDir;
		$structure['files'][] = $historyFile;
		$structure['files'][] = $logsDir . DIRECTORY_SEPARATOR . $deploidFile;
		$structure['links'][] = $currentLink . ':' . $releasesDir . DIRECTORY_SEPARATOR . $releaseName;

		$this->application->makeStructure($this->path, $structure);

		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $logsDir);
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $historyFile);
		$this->assertFileExists($this->path . DIRECTORY_SEPARATOR . $logsDir . DIRECTORY_SEPARATOR . $deploidFile);
		$this->assertDirectoryExists($this->path . DIRECTORY_SEPARATOR . $currentLink);
		$this->assertTrue(is_link($this->path . DIRECTORY_SEPARATOR . $currentLink));
		$this->assertEquals(realpath($this->path . DIRECTORY_SEPARATOR . $releasesDir . DIRECTORY_SEPARATOR . $releaseName), realpath(readlink($this->path . DIRECTORY_SEPARATOR . $currentLink)));
	}

}