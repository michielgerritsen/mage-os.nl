<?php declare(strict_types=1);

use MageOsNl\Registry;
use MageOsNl\Website\MembershipSubmit;

require_once '../vendor/autoload.php';

Registry::getInstance()->setContentDirectory(dirname(__DIR__) . '/content');

function getRealhost(): string
{
    return 'nl.mage-os.org';
}

function isLocalhost(): bool
{
    if (empty($_SERVER['HTTP_HOST'])) {
        return true;
    }

    return str_starts_with($_SERVER['HTTP_HOST'], 'localhost');
}

function isRealhost(): bool
{
    return $_SERVER['HTTP_HOST'] === getRealhost();
}

// Redirect non-localhost and non-realhost to realhost
if (false === isRealhost() && false === isLocalhost()) {
    http_response_code(301);
    header('Location: https://' . getRealhost() . '/');
    exit;
}

// Redirect HTTP to HTTPS
if ((!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== "on") && isRealhost()) {
    http_response_code(301);
    header('Location: https://' . getRealhost() . '/');
    exit;
}

if (strstr($_SERVER['REQUEST_URI'], '/eventbrite') && !empty($_REQUEST['id'])) {
    $data = (new \MageOsNl\EventbriteExport())->getAttendeesByEventId($_REQUEST['id']);
    if (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json') {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    require_once '../templates/eventbrite-export.php';
    exit;
}

if (strstr($_SERVER['REQUEST_URI'], '/lid-worden-post') && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    try {
        (new MembershipSubmit())->execute($_POST);
    } catch (UnexpectedValueException $unexpectedValueException) {
        header('Location: /lid-worden?msg=' . $unexpectedValueException->getMessage());
        exit;
    }
}

$_ENV['image_folder'] = dirname(__DIR__) . '/pub/images/';
require_once '../templates/html.php';
