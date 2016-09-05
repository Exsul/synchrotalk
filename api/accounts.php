<?php

class accounts extends api
{
  protected function reserve()
  {
    return
    [
      "design" => "accounts/inbox",
    ];
  }

  protected function create()
  {
    return
    [
      "design" => "accounts/create/page",
    ];
  }

  public function save_network($network, $token, $expiration = null)
  {
    if (is_null($expiration))
      $expiration = time() + 10 * 365 * 3600;


    echo "This causing extra signin request, refactor";
    list($obj, $user) = $this->network_signin($network, $token);

    $account = db::Query("SELECT account_id
        FROM personal.tokens
        WHERE uid=$1
          AND profile_id=$2", [db::UID(), $user->id], true);

    if ($account())
    { // account known, update token
      $res = db::Query("
        UPDATE personal.tokens
          SET token_data=$2
            , expiration=timestamptz 'epoch' + $3 * interval '1 second'
          WHERE account_id=$1
          RETURNING account_id",
          [ $account->account_id, json_encode($token), $expiration ], true);
    }
    else
    { // account unknown. store
      $res = db::Query("
        INSERT INTO personal.tokens
          (uid, network, expiration, token_data)
          VALUES ($1, $2, timestamptz 'epoch' + $3 * interval '1 second', $4)
          RETURNING account_id",
          [ db::UID(), $network, $expiration, json_encode($token) ], true);
    }

    return $res->account_id;
  }

  private $loaded_accounts = [];

  private function network_signin($network, $token)
  {
    $networks = phoxy::Load('networks');

    phoxy_protected_assert($networks->supported($account->network), "Social network unsupported");

    $obj = $networks->get_network_object($network);

    echo "TODO: Check if token expired";
    $user = $obj->sign_in($token);

    phoxy_protected_assert($user, "Unable to sign in");
    echo "TODO: Make token refresh sequence automatically";

    return [$obj, $user];
  }

  private function get_account_object($account_id)
  {
    if (!empty($this->loaded_accounts[$account_id]))
      return $this->loaded_accounts[$account_id];

    $account = db::Query("SELECT *
      FROM personal.tokens
      WHERE uid=$1
        AND account_id=$2",
        [db::UID(), $account_id], true);

    phoxy_protected_assert($account(), "Account not found. Please connect again");

    list($obj, $user)
      = $this->network_signin
        (
          $account->network,
          json_decode($account->token_data, true)
        );

    db::Query("UPDATE personal.tokens
        SET profile_id=$2
        WHERE account_id=$1", [$account_id, $user->id]);

    return $this->loaded_accounts[$account_id] = $obj;
  }

  public function user($account_id)
  {
    return phoxy::Load('networks/users')->info($account_id);
  }

  protected function welcome($account)
  {
    return
    [
      "design" => "accounts/create/welcome",
      "data" =>
      [
        'user' => $this->user($account),
        'next' => 'inbox',
      ],
      "script" => "user",
      "before" => "user.login",
    ];
  }

  public function connected()
  {
    $accounts = phoxy::Load('accounts/tokens')->connected();

    foreach ($accounts as $account)
      yield $this->filter_sensitive_token_data($account);
  }

  private function filter_sensitive_token_data($account)
  {
    $public_fields =
    [
      'network',
      'account_id',
      'expiration',
    ];

    $ret = [];
    foreach ($account as $key => $value)
      if (in_array($key, $public_fields))
        $ret[$key] = $value;

    return $ret;
  }

  protected function itemize()
  {
    return
    [
      "design" => "accounts/list",
      "data" =>
      [
        "accounts" => iterator_to_array($this->connected()),
      ],
    ];
  }

  protected function info($account_id)
  {
    $account = phoxy::Load('accounts/tokens')->info($account_id);

    return
    [
      "data" => $this->filter_sensitive_token_data($account)
    ];
  }
}
