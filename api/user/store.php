<?php

class store extends api
{
  public function MyUID()
  {
    return phoxy::Load("user")->get_uid();
  }

  private $db;

  public function GetDB()
  {
    if (isset($db))
      return $db;

    $lib_dir = "vendor/enelar/phpsql";

    require_once("{$lib_dir}/phpsql.php");
    require_once("{$lib_dir}/wrapper.php");

    list($scheme) = explode(":", $conf);
    require_once("{$lib_dir}/{$scheme}.php");

    $phpsql = new \phpsql();
    $connection = $phpsql->Connect(conf()->db->connection_string);

    $this->db = new \phpsql\utils\wrapper($connection);

    return $this->db;
  }
}
