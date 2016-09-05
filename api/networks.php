<?php

class networks extends api
{
  protected function supported($name = null)
  {
    $plugins = conf()->plugins->available->__2array();

    if (!is_null($name))
      return[ "data" => in_array($name, $plugins) ];

    $supported =
      array_diff
      (
        $plugins,
        conf()->plugins->hide->__2array()
      );

    return
    [
      "design" => "networks/list",
      "data" =>
      [
        "networks" => array_map(function($plugin)
        {
          return['name' => $plugin];
        }, $supported),
      ],
    ];
  }

  public function __get($name)
  {
    return $this->get_network_object($name);
  }

  public function get_network_object($name)
  {
    $classname = "$name\\$name";
    return $this->hub()->$classname;
  }

  private $hub;
  private function hub()
  {
    if (isset($this->hub))
      return $this->hub;

    require_once(conf()->plugins->lib_path.'/rpc/hub.php');

    $paths = [];
    foreach (conf()->plugins->available as $plugin)
    {
      $path = conf()->plugins->root_path;
      $path .= "/{$plugin}.plugin/";
      $paths[] = $path;
    }

    return $this->hub = new hub($paths);
  }
}
