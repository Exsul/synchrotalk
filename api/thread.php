<?php

class thread extends api
{
  protected function Reserve($account_id, $thread_id)
  {
    return
    [
      "cache" =>
      [
        "global" => "1w"
      ],
      "design" => "thread/index",
      "data" =>
      [
        'account_id' => $account_id,
        'thread_id' => $thread_id,
      ],
    ];
  }

  protected function Read($account_id, $thread_id)
  {
    $network = phoxy::Load('networks/network')
      ->by_account_id($account_id);

    $ret = $network->messages($thread_id);

    phoxy::Load('networks/network')->finish_work($account_id, $network);

    return
    [
      'data' =>
      [
        // When i spare a time phoxy have to overcome that
        'account_id' => $account_id,
        'thread_id' => $thread_id,
        'items' => $ret
      ],
    ];
  }

  protected function Send($account_id, $thread_id, $text)
  {
    $network = phoxy::Load('networks/network')
      ->by_account_id($account_id);

    phoxy::Load('networks/network')->finish_work($account_id, $network);

    return
    [
      'data' => $network->message_send($thread_id , $text),
    ];
  }
}
