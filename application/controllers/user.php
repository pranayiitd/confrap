<?php

/**
 * The user model in Confrap
 */
class User extends CI_Controller {
  public function __construct() {
    parent::__construct();
    $this->load->model('facebook_model');
    $this->load->model('user_model');
    $this->load->helper('url');
    $this->load->helper('html');
    $this->load->library('javascript');
  }
  
  /**
   * welcome page to a possible first-time or repeat non-logged in user
   */
  public function index() {
    $fb = $this->session->userdata('fb');
    if ($fb['me'] and $fb['fbid']) {
      redirect('user/home');
    } else {
      echo 'You are not logged in!';
      echo anchor($fb['loginUrl'], 'Login');
    }
  }
  
  /**
   * Displays the home page of the user, provided already authorized. If not,
   * redirect to index page
   */
  public function home($id = null, $use_fb = "0") {
    $fb = $this->session->userdata('fb');
    $signed_in = ($fb['me'] != null) and $fb['fbid'];
    if ( ! $signed_in and $id === null) { /* neither signed in nor asking for a user - REDIRECT */
      redirect('user/index');
    } else if ($signed_in and $id === null) { /* signed in and asking for own page */
      $id = $fb['me']['id'];
      $use_fb = "1";
    }
    if ($use_fb === "0") {
      $user = $this->user_model->get_by_url($id);
    } else if ($use_fb === "1") {
      $user = $this->user_model->get_by_fbid($id);
    } else {
      $user = $this->user_model->get_by_uid($id);
    }
    if (empty($user)) {
      if ($signed_in) { /* add entry to the db */
        $user = $this->user_model->add_user($fb);
        
      } else {
        redirect('user/index');
      }
    }
    $data['interests'] = $this->user_model->get_interests($user['id']);
    $data['followers'] = $this->user_model->get_followers($user['fbid']);
    $data['followees'] = $this->user_model->get_followees($user['fbid']);
    $data['myfbid'] = $fb['fbid'];
    $data['name'] = $user['name'];
    $data['fb'] = $fb;
    $data['signed_in'] = $signed_in;
    $data['me'] = ($signed_in and ($fb['fbid'] === $user['fbid']));
    $data['user_profile'] = $user;
    
    $this->load->view('templates/prologue', $data);
    $this->load->view('templates/header', $data);
    $this->load->view('user/home', $data);
    $this->load->view('user/home_js', $data);
    $this->load->view('templates/footer', $data);
  }
  
  /**
   * Follow the user derived from the input post data
   */
  public function follow() {
    $follower = $this->input->get('follower');
    $followee = $this->input->get('followee');
    $fb = $this->session->userdata('fb');
    if ($fb['fbid'] === $follower) {
      $this->user_model->set_follower($follower, $followee);
    }
  }
  
  public function unfollow() {
    $follower = $this->input->get('follower');
    $followee = $this->input->get('followee');
    $fb = $this->session->userdata('fb');
    if ($fb['fbid'] === $follower) {
      $this->user_model->rem_follower($follower, $followee);
    }
  }
  
  public function all() {
    $users = $this->user_model->get_users();
    $response = array();
    foreach ($users as $user) {
      array_push($response, array(
                              'name'    =>  $user['name'],
                              'uid'     =>  $user['id'],
                              'ltype'   =>  'u'
                            )
                );
    }
    echo json_encode($response);
  }
  
  public function add_interest() {
    $uid = $this->input->get('uid');
    $val = $this->input->get('val');
    $fb = $this->session->userdata('fb');
    echo $this->user_model->get_fbid_from_uid($uid);
    if ($fb['fbid'] === $this->user_model->get_fbid_from_uid($uid)) {
      $this->user_model->set_interest($uid, $val);
    }
  }
  
  public function rem_interest() {
    $uid = $this->input->get('uid');
    $val = $this->input->get('val');
    $fb = $this->session->userdata('fb');
    if ($fb['fbid'] === $this->user_model->get_fbid_from_uid($uid)) {
      $this->user_model->unset_interest($uid, $val);
    }
  }
  
  /**
   * Redirects the page to facebook login
   */
  public function login() {
    $fb = $this->session->userdata('fb');
    redirect($fb['loginUrl']);
  }
  
  /**
   * Logout of the web app
   */
  public function logout() {
    $fb = $this->session->userdata('fb');
    session_start();
    session_unset();
    session_destroy();
    session_write_close();
    session_regenerate_id(true);
    $this->session->sess_destroy();
    redirect($fb['logoutUrl']);
  }
}