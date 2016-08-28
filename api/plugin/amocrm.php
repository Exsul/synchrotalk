<?php

class amocrm extends api
{
  protected function linked_deal($account_id, $thread_id)
  {
    return
    [
      'data' =>
      [
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
    return
    [
      'error' => 'Yahoo',
    ];
  }
}
