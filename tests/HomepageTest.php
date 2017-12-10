<?php

require_once 'include/init.php';
require_once 'include/test.php';

use PHPUnit\Framework\TestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;

class HomepageTestCase extends TestCase
{
	protected $webDriver;

    protected $url = 'http://localhost:8081';

    public function setUp()
    {
		$this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub', DesiredCapabilities::chrome());
    }

    public function tearDown()
    {
    	$this->webDriver->quit();
    }

    public function testHomepage()
    {
    	$this->webDriver->get($this->url);

    	$this->assertContains('Cover', $this->webDriver->getTitle());
    }
}