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

$photos = $driver->findElements(WebDriverBy::cssSelector('.c5TXIP .c2iYAv .cRjKsc .c1ZEkM')); //写真！
$name = [];
foreach ($photos as $k => $v) {
    if($k === 10) {
        break;
    }
    $name[] = $v->getAttribute('src');

}

// 空白のvalurがあれば、削除
function myFilter($val) {
	return !is_null($val);
}

// TODO:keyの再配列が必要かも。
$name = array_filter($name, 'myFilter');
var_dump($name);
echo "\n";

$driver->close();