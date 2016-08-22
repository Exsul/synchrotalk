<?php

class inbox extends api
{
  protected function reserve()
  {
    return
    [
      'design' => 'inbox/index',
    ];
  }

  private function ReqursiveRemoveCandidates($media)
  {
    if (!is_array($media))
      return $media;

    $ret = [];
    foreach ($media as $key => $val)
      if ($key === 'candidates')
        return $val[0];
      else
        $ret[$key] = $this->ReqursiveRemoveCandidates($val);

    return $ret;
  }

  private function MarkWithAccount($account, $inbox)
  {
    foreach ($inbox as $thread)
    {
      $ret = get_object_vars($thread);
      $ret['account'] = $account;

      yield $ret;

      // TODO: Please return this code later
      // return $this->ReqursiveRemoveCandidates($thread);
    };
  }

  protected function itemize()
  {
    $accounts = phoxy::Load('accounts/tokens')->connected();

    $networks = phoxy::Load('networks');

    $inbox = [];
    foreach ($accounts as $account)
    {
      $connection = $networks->get_network_object($account->network);
      $connection->sign_in($account->token_data);
      $threads = $connection->threads();

      $marked_threads =
        $this->MarkWithAccount($account->account_id, $threads);

      $inbox = array_merge
      (
        $inbox
        , iterator_to_array($marked_threads)
      );
    }

    return
    [
      "design" => "inbox/itemize",
      "data" =>
      [
        "inbox" => $inbox,
      ],
    ];
  }
}
