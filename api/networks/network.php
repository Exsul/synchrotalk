<?php

class network extends api
{
  public function by_account_id($account_id)
  {
    $account = phoxy::Load('accounts/tokens')->info($account_id);
    return $this->by_account_object($account);
  }

  public function by_account_object($account_object)
  {
    $this->WaitForRequestFrame($account_object->network);

    $network = $this->get_network_object($account_object->network);
    $network->sign_in($account_object->token_data);
    return $network;
  }

  private function WaitForRequestFrame($network)
  {
    if ($this->CheckForRequestFrame($network))
      return;

    $ini = ignore_user_abort(false);

    $max_waiting = 30;
    $single_step = 0.2;

    $success = false;

    for ($i = 0; $i < $max_waiting / $single_step; $i++)
    {
      $header = sprintf("Request-%03d", $i);
      header("$header: {$network}: Frame-denied, network is busy");

      usleep(1E+6 * $single_step);

      if ($this->CheckForRequestFrame($network))
        break;
    }

    ignore_user_abort($ini);

    phoxy_protected_assert($i < $max_waiting / $single_step, "Unable to request {$netwokr}: network is busy");

    $time_spent = $i * $single_step;
    header("Network delay: {$time_spent}s network was busy");
  }

  private function CheckForRequestFrame($network)
  {
    try
    {
      $res = db::Query("UPDATE personal.frequency
          SET last_access=now()
          WHERE network=$1
          AND now() - last_access > min_interval
          RETURNING *", [$network], true);
      return $res();
    } catch (Exception $e)
    {
      return false;
    }
  }

  public function get_network_object($network)
  {
    $networks = phoxy::Load('networks');

    phoxy_protected_assert($networks->supported($network), "Social network unsupported");

    $obj = $networks->get_network_object($network);

    $this->init_network_object($obj, $network);

    return $obj;
  }

  private function init_network_object($obj, $network)
  {
    $settings = conf()->networks->init->$network;

    $obj->init($settings);
  }
}
