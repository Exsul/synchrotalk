<?php

class amocrm extends api
{
  protected function linked_deal($account_id, $thread_id)
  {
    $amo_account = $this->RequireAccount();

    return
    [
      'data' =>
      [
        'amo_account' => $amo_account,
        'id' => 0,
        'account_id' => $account_id,
        'thread_id' => $thread_id,
      ],
    ];
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
    $user['profile_link'] = "http://TO.DO";

    $amocrm = phoxy::Load('networks')->get_network_object('amocrm');

    $token = phoxy::Load('accounts/tokens')->info('amocrm');

    $amocrm->sign_in($token->token_data);

    $deal = $this->create_deal($amocrm, (array)$params, $user);
    $user['deal'] = $deal;

    $this->create_contact($amocrm, $user);

    unset($this->addons['result']);

    return
    [
      'replace' => 'amocrm',
      'design' => 'thread/plugin/amocrm',
    ];
  }

  private function UserIDFromThread($account_id, $thread_id)
  {
    $threads = phoxy::Load('inbox')->threads($account_id);

    var_dump($threads);

    foreach ($threads as $thread)
      if ($thread['id'] == $thread_id)
        foreach ($thread['users'] as $user)
          if ($user['id'])
            return $user['id'];

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
    $user =
    [
      'name' => $user['name'],
      'deal' => $user['deal'],
      'network' => $user['network'],
      'chat_link' => $user['chat_link'],
      'profile_link' => $user['profile_link'],
    ];

    return $amocrm->create_contact($deal);
  }

  private function ThreadLink($account_id, $thread_id)
  {
    $obj = [ $account_id, $thread_id ];
    $json = json_encode($obj, true);
    return conf()->domain."plugin/amocrm/to_thread({$json})";
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
}
