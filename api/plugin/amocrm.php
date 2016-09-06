<?php

class amocrm extends api
{
  protected function linked_deals($account_id, $thread_id)
  {
    phoxy::SetCacheTimeout('session', 0);
    $contact = $this->linked_contact($account_id, $thread_id);

    return
    [
      'result' => 'amocrm',
      'design' => 'thread/plugin/amocrm.index',
      'data' =>
      [
        'account_id' => $account_id,
        'thread_id' => $thread_id,
        'deals' => $contact ? $contact->deals : [],
      ],
    ];
  }

  protected function deal_info($deal_id)
  {
    phoxy::SetCacheTimeout('session', 0);
    $amocrm = $this->LoginedAmoCRM();

    return
    [
      'design' => 'thread/plugin/amocrm.deal.preview',
      'data' => $amocrm->deal_info($deal_id),
    ];
  }

  protected function stage_info($stage_id)
  {
    foreach ($this->stages() as $stage)
      if ($stage['id'] == $stage_id)
        return
        [
          'design' => 'thread/plugin/amocrm.deal.stage.explain',
          'data' => $stage
        ];

    phoxy_protected_assert(false, "AmoCRM plugin: stage not found");
  }

  private function stages()
  {
    $resolver = function($network, $cb, $uid)
    {
      $stages = $network->stages();

      if (!$stages)
        return false;

      return $cb($stages, time() + 3600 * 24);
    };

    $stages = phoxy::Load('accounts/cache')
      ->account($this->RequireAccount())
      ->Retrieve
      (
        'stages',
        0,
        $resolver
      );

    return $stages;
  }

  private function linked_contact($account_id, $thread_id)
  {
    phoxy::SetCacheTimeout('session', 0);
    $resolver = function($network, $cb, $salt)
    {
      $contact = $network->find_contact_by_query($salt);

      if (!$contact)
        return $cb($contact, time());

      return $cb($contact, time() + 3600 * 24);
    };

    $contact = phoxy::Load('accounts/cache')
      ->account($this->RequireAccount())
      ->Retrieve
      (
        'linked_contact',
        $this->contact_salt($account_id, $thread_id),
        $resolver
      );

    return $contact;
  }

  private function contact_salt($account_id, $thread_id)
  {
    $account = phoxy::Load('accounts')->info($account_id);

    return md5($account->network.$thread_id);
  }

  protected function create_form($account_id, $thread_id)
  {
    unset($this->addons['result']);

    return
    [
      'design' => 'thread/plugin/amocrm.deal.create',
      'data' =>
      [
        'id' => 0,
        'account_id' => $account_id,
        'thread_id' => $thread_id,
      ],
    ];
  }

  protected function create($account_id, $thread_id, $params)
  {
    $user = (array)$this->UserFromThread($account_id, $thread_id);

    $account = phoxy::Load('accounts')->info($account_id);
    $user['network'] = $account['network'];
    $user['chat_link'] = $this->ThreadLink($account_id, $thread_id);
    $user['profile_link'] = "http://TODO.{$account['network']}/{$user['nickname']}";

    $amocrm = $this->LoginedAmoCRM();

    $deal = $this->create_deal($amocrm, (array)$params, $user);

    $user['deal'] = $deal;
    $user['salt'] = $this->contact_salt($account_id, $thread_id);

    var_dump($user);

    $this->create_contact($amocrm, $user);

    unset($this->addons['result']);

    return $this->linked_deals($account_id, $thread_id);
  }

  private function UserIDFromThread($account_id, $thread_id)
  {
    $threads = phoxy::Load('inbox')->threads($account_id);

    foreach ($threads as $thread)
      if ($thread->id == $thread_id)
        foreach ($thread->users as $user)
          if ($user->id)
            return $user->id;

    return 0;
  }

  private function UserFromThread($account_id, $thread_id)
  {
    $user_id = $this->UserIDFromThread($account_id, $thread_id);

    phoxy_protected_assert($user_id, "Ошибка, обновите страницу, и сообщите в техподдежку - мы исправим");

    return (array)phoxy::Load('networks/users')->info($account_id, $user_id);
  }

  private function create_deal($amocrm, $params, $user)
  {
    $deal =
    [
      'name' => $params['name'],
      'price' => $params['price'],
      'network' => $user['network'],
      'chat_link' => $user['chat_link'],
      'profile_link' => $user['profile_link'],
    ];

    return $amocrm->create_deal($deal);
  }

  private function create_contact($amocrm, $user)
  {
    $user['name'] = implode(" ", $user['name']);

    return $amocrm->create_contact($user);
  }

  private function ThreadLink($account_id, $thread_id)
  {
    $obj = [ $account_id, $thread_id ];
    $json = json_encode($obj, true);
    return phoxy_conf()["site"]."plugin/amocrm/to_thread({$json})";
  }

  protected function Thread($obj)
  {
    return phoxy::Load('thread', true)->Reserve($obj[0], $obj[1]);
  }

  private function RequireAccount()
  {
    $tokens = phoxy::Load('accounts/tokens')->connected();

    foreach ($tokens as $token)
      if ($token->network == 'amocrm')
        return $token->account_id;

    phoxy_protected_assert(false,
      [
        'design' => 'thread/plugin/amocrm.index',
        'data' =>
        [
          'not_connected' => 1,
        ],
      ]);
  }

  private function LoginedAmoCRM()
  {
    $amo_account = $this->RequireAccount();
    $token = phoxy::Load('accounts/tokens')->info($amo_account);

    $amocrm = phoxy::Load('networks')->get_network_object('amocrm');
    $amocrm->sign_in($token->token_data);

    return $amocrm;
  }
}
