<?php

class users extends api
{
  protected function info($account_id, $uid = null)
  {
    $resolver = function($network, $cb, $uid)
    {
      $user = $network->user($uid);

      var_dump($user);

      if (!$user)
        return false;

      return $cb($user, time() + 3600 * 24);
    };

    $user = phoxy::Load('accounts/cache')
      ->account($account_id)
      ->Retrieve
      (
        'user',
        $uid,
        $resolver
      );

    return
    [
      'design' => 'thread/users/one',
      'data' => $user,
    ];
  }
}
