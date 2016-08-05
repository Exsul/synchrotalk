<?php

class auth extends api
{
  public function __construct($network_name = null)
  {
    $this->load($network_name);
  }

  private $obj;
  public function load($network_name = null)
  {
    if (isset($network_name))
      return $this->obj;

    $network = phoxy::Load('networks/network')->get_network_object($network_name);

    return $this->obj = $network->auth();
  }

  protected function add($network_name)
  {
    $auth = $this->load($network_name);

    $auth_type = $auth->preferred_authtype();


    return
    [
      'design' => 'networks/auth/sequence.play',
      'data' =>
      [
        'network' => $network_name,
        'type' => $auth_type,
        'sequence' => $this->get_sequence($auth_type),
      ],
    ];
  }

  private function require_known_instruction($sequence_type, $instruction)
  {
    $sequence = $this->get_sequence($sequence_type);

    phoxy_protected_assert(in_array($instruction, $sequence), "Sorry action invalid");
  }

  public function get_sequence($sequence_type)
  {
    $sequences =
    [
      'direct'   =>
      [

      ],

      'callback' =>
      [
      ],

      'redirect' =>
      [
        'redirect_auth_requirments',
        'redirect_auth_question',
        'redirect_auth_answer',
      ],
    ];

    return $sequences[$sequence_type];
  }

  protected function make_step($sequence_type, $instruction, $data)
  {
    $this->require_known_instruction($sequence_type, $instruction);

    return
    [
      'design' => 'networks/auth/sequence.step',
      'data' =>
      [
        'commands' =>
      ],
    ];
  }

  private function RefactorDirectAnswer()
  { // This code is not working and has to be re-factored
    $user = $network->log_in($login, $password);
    phoxy_protected_assert($user, "Login/password invalid");

    $user['network'] = $network;

    $accounts[$network] =
    [
      "network" => $network,
      "login" => $login,
      "password" => $password,
      "user" => $user,
    ];

    return
    [
      "design" => "accounts/create/welcome",
      "data" =>
      [
        'user' => $user,
        'next' => 'inbox',
      ],
      "script" => "user",
      "before" => "user.login",
    ];
  }


}
