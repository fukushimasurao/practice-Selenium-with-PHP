<?php
require './vendor/autoload.php';
 
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
 
$word = 'amazon';

$title = "$word - Buy $word at Best Price in Philippines | www.lazada.com.ph";

$options = new ChromeOptions();
$options->addArguments(['--headless']);
$host = 'http://localhost:4444/wd/hub';
$capabilities = Facebook\WebDriver\Remote\DesiredCapabilities::chrome();
$capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
$driver = Facebook\WebDriver\Remote\RemoteWebDriver::create($host, $capabilities);
$driver->get('https://www.lazada.com.ph/');
$browserLogs = $driver->manage()->getLog('browser');
$element = $driver->findElement(WebDriverBy::name('q'));
$element->sendKeys($word);
$element->submit();
$driver->wait(15)->until(
    WebDriverExpectedCondition::titleIs($title)
);

if ($driver->getTitle() !== "$title") {
    throw new Exception('fail');
}

// $file = "サンプル_chrome.png";
// $driver->takeScreenshot($file);

$testId = $driver->findElement(WebDriverBy::className('c16H9d'));
$text = $testId->getText();
echo $text;

// $a = $driver->findElemenet(WebDriverBy::className("lzd-logo-content"));
// foreach ($a as $v) {
//     echo  $v; 
// }

$driver->close();