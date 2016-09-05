<?php

class tokens extends api
{
  public function connected()
  {
    $accounts = db::Query("SELECT *
      FROM personal.tokens
      WHERE uid=$1", [db::UID()]);

    foreach ($accounts as $account)
      yield $this->PrepareAccount($account);
  }

  public function info($account_id)
  {
    $account = db::Query("SELECT *
      FROM personal.tokens
      WHERE uid=$1 AND account_id=$2",
      [db::UID(), $account_id], true);

    phoxy_protected_assert($account(), "Internal issue: account not found");

    return $this->PrepareAccount($account);
  }

  private function PrepareAccount($db_raw)
  {
    $ret = $db_raw->__2array();

    if (!is_array($ret['token_data']))
      $ret['token_data'] = json_decode($ret['token_data'], true);

    return new phpa2o\phpa2o($ret);
  }

  public function itemize()
  {
    return $this->connected();
  }
}
