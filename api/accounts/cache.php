<?php

class cache extends api
{
  private $account_id;

  public function __construct($account_id = null)
  {
    $this->account_id = $account_id;
  }

  public function account($account_id)
  {
    return new cache($account_id);
  }

  public function Retrieve($type, $resource_id, $resolver)
  {
    $cache = db::Query("SELECT *
      FROM personal.account_cache
      WHERE account_id=$1
        AND type=$2
        AND resource_id=$3
        AND expired < now()",
        [$this->AccountID(), $type, $resource_id], true);

    if ($cache())
    {
      phoxy::SetCacheTimeoutTimestamp("session", $cache->expired);
      return $this->WorkaroundPHPSQLIssue($cache->data->__2array());
    }

    return $this->ResolveMissingCache($type, $resource_id, $resolver);
  }

  private function WorkaroundPHPSQLIssue($data)
  {
    if (!isset($data[0]))
      return (object)$data;

    foreach ($data as &$value)
      $value = $this->WorkaroundPHPSQLIssue($value);

    return $data;
  }

  private function ResolveMissingCache($type, $resource_id, $resolver)
  {
    $account_id = $this->AccountID();
    $network = phoxy::Load('networks/network')
      ->by_account_id($account_id);

    $resolved = function($data, $expiration)
      use (&$return, $type, $resource_id)
    {
      $return = $data;

      phoxy::SetCacheTimeoutTimestamp("session", $expiration);

      $this->Update($type, $resource_id, $data, $expiration);

      return true;
    };

    $result = $resolver($network, $resolved, $resource_id);

    phoxy_protected_assert($result, "Resolver returned failure on $type cache miss");

    return $return;
  }

  public function Update($type, $resource_id, $data, $expiration)
  {
    // TODO: Update if exists
    db::Query("DELETE FROM personal.account_cache WHERE expired < now()");

    db::Query("INSERT INTO personal.account_cache
        (account_id, type, resource_id, data, expired)
        VALUES ($1, $2, $3, $4, 'epoch'::timestamp + $5::int8 * '1 second'::interval)",
        [
          $this->AccountID(),
          $type,
          $resource_id,
          json_encode($data, true),
          $expiration
        ]);
  }

  public function AccountID()
  {
    phoxy_protected_assert($this->account_id, "Please set account for cache request");

    return $this->account_id;
  }
}
