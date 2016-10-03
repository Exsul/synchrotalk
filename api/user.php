<?php

class user extends api
{
  protected function Reserve()
  {
    return
    [
      'design' => 'accounts/login/index',
    ];
  }

  private function StorageShortcut()
  {
    return function &()
    {
      return $this->GetSessionStorage();
    };
  }

  private function &GetSessionStorage()
  {
    if (session_status() != PHP_SESSION_ACTIVE)
    {
      session_cache_limiter("");

      session_start();
    }

    global $USER_SENSITIVE;
      $USER_SENSITIVE = 1;

    return $_SESSION;
  }

  public function ResetSession()
  {
    $this->GetSessionStorage();
    session_destroy();
  }

  public function login($uid = null)
  {
    if (is_null($uid))
      $uid = phoxy::Load('user/store')->Register();

    $my_name = &$this->get_uid();
    $my_name = $uid;

    return $uid;
  }

  private function &get_uid()
  {
    return $this->GetSessionStorage()['username'];
  }

  protected function is_logined()
  {
    return !is_null($this->get_uid());
  }

  public function uid()
  {
    if (!$this->is_logined())
      $this->login();
    return $this->get_uid();
  }
}
