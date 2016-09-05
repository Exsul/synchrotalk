<?php

class main extends api
{
  protected function Reserve()
  {
    unset($this->addons['result']);
    return
    [
      'design' => 'main/body',
      'script' =>
      [
      ]
    ];
  }

  protected function home()
  {
    return
    [
      'design' => 'main/home',
      'data' =>
      [
        'version' => $this->version(),
      ],
      'cache' =>
      [
        'global' => '1w',
      ],
    ];
  }

  private function version()
  {
    return exec('git describe --tags --abbrev=0');
  }
}
