<?php

class share extends api
{
  protected function export()
  {
    return
    [
      "design" => "users/share",
      "data" =>
      [
        "hash" => $this->create_hash(),
      ],
    ];
  }

  protected function import($hash)
  {
    return
    [
      "design" => "users/share.import",
      "data" =>
      [
        "result" => $this->import_hash($hash),
      ],
    ];
  }

  protected function update($version = null, $domain_level = 2)
  {
    if (is_null($version))
      return
      [
        "design" => "utils/error/page",
        "data" =>
        [
          "error" => "No version is specified. Update canceled",
        ],
      ];

    if ($domain_level < 2)
      return
      [
        "design" => "utils/error/security.violation",
        "data" =>
        [
          "error" => "You cant switch second level domain",
        ],
      ];

    return
    [
      "design" => "users/share.update",
      "data" =>
      [
        "hash" => $this->create_hash(),
        "version" => $version,
        "domain_level" => $domain_level,
      ],
    ];
  }

  private function create_hash()
  {
    $account = phoxy::Load('user')->uid();

    $hash = db::Query("INSERT INTO
        personal.share(account)
        VALUES ($1)
        RETURNING hash",
      [$account], true);

    return $hash->hash;
  }

  private function import_hash($hash)
  {
    $account = db::Query("DELETE
      FROM personal.share
      WHERE expired > now()
        AND hash=$1
      RETURNING *",
      [$hash], true);

    phoxy_protected_assert($account(), "Access link expired");

    phoxy::Load('user')->ResetSession();
    phoxy::Load('user')->login($account->account);
  }
}
