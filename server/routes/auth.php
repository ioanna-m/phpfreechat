<?php

class Route_auth {

  /**
   * Used to authenticate a user
   */
  public function get($req) {

    // check if a user session already exists
    session_start();
    if (isset($_SESSION['userdata']) and isset($_SESSION['userdata']['id'])) {
      header("HTTP/1.1 200 User authenticated");
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($_SESSION['userdata']);
      return;
    } 

    // check if a login/password has been set
    // allow Pfc-Authorization header because Authorization can be disabled by reverse proxy
    $auth = isset($req['headers']['Authorization']) ?
      $req['headers']['Authorization'] :
      (isset($req['headers']['Pfc-Authorization']) ? $req['headers']['Pfc-Authorization'] : '');
    if (!$auth) {
      header('HTTP/1.1 403 Need authentication');
      header('Pfc-WWW-Authenticate: Basic realm="Authentication"');
      return;
    }

    // decode basic http auth header
    $auth = @explode(':', @base64_decode(@array_pop(@explode(' ', $auth))));
    if (!isset($auth[0]) && !$auth[0]) {
      header("HTTP/1.1 400 Login is missing");
      return;
    }
    $login    = $auth[0];
    $password = isset($auth[1]) ? $auth[1] : '';
    
    // check login/password
    if ($login) {
      include_once 'routes/users.php';
      $uid = Users_utils::generateUid();
      $udata = array(
        'id'       => $uid,
        'name'     => $login,
        'email'    => (isset($req['params']['email']) and $req['params']['email']) ? $req['params']['email'] : (string)rand(1,10000),
        'role'     => 'user',
      );
      Users_utils::setUserInfo($udata);
      $_SESSION['userdata'] = $udata;

      header("HTTP/1.1 200");
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode($_SESSION['userdata']);
      return;
    } else {
      header('HTTP/1.1 403 Wrong credentials');
      header('Pfc-WWW-Authenticate: Basic realm="Authentication"');         
      return;
    }

  }

  /**
   * Used to logout
   */
  public function delete($req) {
    // check if session exists
    session_start();
    if (!isset($_SESSION['userdata']) or !isset($_SESSION['userdata']['id'])) {
      header("HTTP/1.1 200 Already disconnected");
      return;
    }
    
    // store userdata in a cache in order to return it later
    $cache = $_SESSION['userdata'];
    
    // logout
    $_SESSION['userdata'] = array();
    session_destroy();
    
    // return ok and the user data
    header("HTTP/1.1 204 Disconnected");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($userdata);
  }
}