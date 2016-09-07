<?php
require_once('vendor/autoload.php');
ini_set('xdebug.var_display_max_depth', 10);

date_default_timezone_set("Europe/Moscow");

if (!PRODUCTION)
{
  error_reporting(E_ALL);
  ini_set('display_errors','On');
}

function phoxy_conf()
{
  $ret = phoxy_default_conf();
  $ret["api_csrf_prevent"] = PRODUCTION;
  $ret["autostart"] = false;
  $ret["cache"] =
  [
    "global" => "no",
    "session" => "1w",
  ];
  $ret["sync_cascade"] = !PRODUCTION;
  $ret["ga"] = conf()->ga;

  return $ret;
}

function default_addons()
{
  $ret =
  [
    "cache" => "no",
    "result" => "canvas",
  ];

  return $ret;
}

ob_start();
function append_warnings_to_object($that)
{
  if (phoxy_conf()["debug_api"] && !phoxy_conf()["is_ajax_request"])
    return;

  $buffer = ob_get_contents();
  ob_end_clean();

  if (!empty($buffer))
    $that->obj["warnings"] = $buffer;
}

include('phoxy/server/phoxy_return_worker.php');
phoxy_return_worker::$add_hook_cb = function($that)
{
  global $USER_SENSITIVE;

  var_dump($that);

  if ($USER_SENSITIVE)
    $that->NewCache(['global' => 'no']);

  if (!isset($that->obj["data"]) && !isset($that->obj["error"]))
    $that->NewCache(['global' => '1w']);

  $that->hooks[] = append_warnings_to_object;
};

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
include('phoxy/load.php');

phoxy::Load("user/store/db");

try
{
  \phoxy::Start();
} catch (Exception $e)
{
  $message =
  [
    "error" => $e->getMessage(),
    "warnings" => ob_get_contents(),
  ];

  ob_end_clean();

  die (json_encode($message, true));
}
