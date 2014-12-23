<?php

namespace Elgg\E2E;

use SebastianBergmann\Exporter\Exception;

class BasicTest extends \PHPUnit_Extensions_Selenium2TestCase
{
	protected function setUp()
	{
		if (!getenv('E2E')) {
			$this->markTestSkipped("Must be ran in E2E testing environment");
		}
		parent::setUp();
		$this->setBrowser('firefox');
		$this->setBrowserUrl('http://localhost:8888/');
		$this->setDesiredCapabilities(array(
			'browserName' => 'firefox',
			'javascriptEnabled' => true,
			'cssSelectorsEnabled' => true,
		));
	}

	protected function waitForJsInit() {
		$self = $this;
		$this->waitUntil(function() use ($self) {
			// make sure that JS init event is fired
			$initFired = $self->execute(array(
				'script' => "return (typeof elgg.is_triggered_hook === 'function') "
					. "&& elgg.is_triggered_hook('init', 'system')",
				'args' => array()
			));
			return $initFired ? true : null;
		}, 15000);
	}

	public function testTitle()
	{
		$this->url('http://localhost:8888/');
		$this->assertEquals('Elgg Travis Site', $this->title());

		$this->cookie()->get('Elgg');

		$this->waitForJsInit();

		$releaseStr = $this->execute(array(
			'script' => "return elgg.release",
			'args' => array()
		));
		$this->assertEquals(elgg_get_version(true), $releaseStr);

		//now something not loaded immediately so wę know that main JS was loaded
		$translationOutput = $this->execute(array(
			'script' => "return elgg.echo('option:yes')",
			'args' => array()
		));
		$this->assertEquals("Yes", $translationOutput);
	}

	public function testLogin()
	{
		$this->url('http://localhost:8888/');
		$this->assertEquals('Elgg Travis Site', $this->title());

		$loggedoutCookie = $this->cookie()->get('Elgg');

		try {
			$this->byCssSelector('.elgg-page-topbar');
			$this->fail('Topbar must not be visible for logged out users.');
		} catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
			$this->assertEquals(\PHPUnit_Extensions_Selenium2TestCase_WebDriverException::NoSuchElement, $e->getCode());
		}

		$this->waitForJsInit();

		$this->byXPath("//div[@id='login-dropdown']/a")->click();
		// wait at least 400ms for popup fadein animation, we bump it a it for safety margin
		$this->timeouts()->implicitWait(600);
		$this->assertTrue($this->byXPath("//div[@id='login-dropdown-box']")->displayed());

		$usernameInput = $this->byXPath("//div[@id='login-dropdown-box']//input[@name='username']");
		$usernameInput->click();
		$usernameInput->value('admin');

		$passwordInput = $this->byXPath("//div[@id='login-dropdown-box']//input[@name='password']");
		$passwordInput->click();
		$passwordInput->value('fancypassword');

		$this->byXPath("//div[@id='login-dropdown-box']//input[@value='Log in']")->click();

		$this->waitUntil(function () {
			if ($this->byCssSelector('ul.elgg-system-messages > li.elgg-message')) {
				return true;
			}
			return null;
		}, 5000);

		$this->assertNotEquals($loggedoutCookie, $this->cookie()->get('Elgg'));

		$this->byCssSelector('.elgg-page-topbar');

		$this->assertEquals("You have been logged in.", $this->byCssSelector('ul.elgg-system-messages > li.elgg-message > p')->text());
	}
}