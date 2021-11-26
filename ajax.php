<?php if (!defined('ABSPATH')) exit;

// -- user register
add_action('wp_ajax_user_registration', 'bhi_user_registration');
add_action('wp_ajax_nopriv_user_registration', 'bhi_user_registration');
function bhi_user_registration()
{
  $mail = (isset($_POST['mail']) ? $_POST['mail'] : '');
  $pass = (isset($_POST['pass']) ? $_POST['pass'] : '');
  $first_name = (isset($_POST['first_name']) ? $_POST['first_name'] : '');
  $last_name = (isset($_POST['last_name']) ? $_POST['last_name'] : '');
  $mailing_list = (isset($_POST['mailing_list']) ? $_POST['mailing_list'] : '');
  $company = (isset($_POST['company']) ? $_POST['company'] : '');
  $role = (isset($_POST['role']) ? $_POST['role'] : '');

  if (!empty($mail) && !empty($pass)) {

    if (email_exists($mail)) {
      echo json_encode(array('success' => false, 'message' => __('User with such email exists', 'bhi')));
      die();
    } else {

      $userdata = array(
        'user_login' => $mail,
        'user_pass'  => $pass,
        'user_email' => $mail,
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'role'       => $role
      );

      $user_id = wp_insert_user($userdata);

      //file_put_contents(__DIR__ . '/userdata_res.txt', print_r($userdata, true));

      update_user_meta($user_id, 'billing_company', $company);

      //add to MailChimp subscribe list
      if ($mailing_list && $mailing_list != '') add_mailchimp_list($role, $mail, $first_name, $last_name);


      if (!is_wp_error($user_id)) {
        echo json_encode(array('success' => true));
      } else {
        echo json_encode(array('success' => false, 'message' => $user_id->get_error_message()));
      }
    }
  } else {
    echo json_encode(array('success' => false, 'message' => __('Invalid username or pass', 'bhi')));
  }

  die();
}

// -- user login
add_action('wp_ajax_user_login', 'bhi_user_login');
add_action('wp_ajax_nopriv_user_login', 'bhi_user_login');
function bhi_user_login()
{

  $mail = (isset($_POST['mail']) ? $_POST['mail'] : '');
  $pass = (isset($_POST['pass']) ? $_POST['pass'] : '');
  $remember_me = (isset($_POST['remember_me']) ? $_POST['first_name'] : '');

  $creds = array();
  $creds['user_login'] = $mail;
  $creds['user_password'] = $pass;
  $creds['remember'] = $remember_me;

  $user = wp_signon($creds, false);
  if (is_wp_error($user)) {
    echo json_encode(array('success' => false, 'message' => __('Invalid username or pass', 'bhi')));
  } else {

    $cookie_quiz_lic = $_COOKIE['quiz_license'];
    if ($cookie_quiz_lic) {
      $cookie_quiz_lic_str = explode("_", $cookie_quiz_lic);
      $quiz_id = $cookie_quiz_lic_str[0];
      $total = $cookie_quiz_lic_str[1];
      $exam_result = $cookie_quiz_lic_str[2];
      bhi_add_user_license($user->ID, $quiz_id, $total, $exam_result);
    }

    $redirect = user_redirect($user);

    echo json_encode(array('success' => true, 'redirect' => $redirect));
  }
  die();
}

// -- customer update
add_action('wp_ajax_customer_update', 'bhi_customer_update');
add_action('wp_ajax_nopriv_customer_update', 'bhi_customer_update');
function bhi_customer_update()
{
  $user_id = (isset($_POST['user_id']) ? $_POST['user_id'] : '');
  $first_name = (isset($_POST['first_name']) ? $_POST['first_name'] : '');
  $last_name = (isset($_POST['last_name']) ? $_POST['last_name'] : '');
  $phone = (isset($_POST['phone']) ? $_POST['phone'] : '');
  $address = (isset($_POST['address']) ? $_POST['address'] : '');
  //$role = (isset($_POST['role']) ? $_POST['role'] : '');

  if (empty($user_id)) {
    echo json_encode(array('success' => false, 'message' => __('Error updating user', 'bhi')));
    die();
  } else {
    $userdata = array(
      'ID'         => $user_id,
      'first_name' => $first_name,
      'last_name'  => $last_name,
    );

    $user_id = wp_update_user($userdata);

    //file_put_contents(__DIR__ . '/userdata_res.txt', print_r($userdata, true));

    update_user_meta($user_id, 'billing_address_1', $address);
    update_user_meta($user_id, 'billing_phone', $phone);

    if (!is_wp_error($user_id)) {
      echo json_encode(array('success' => true));
    } else {
      echo json_encode(array('success' => false, 'message' => __('Error updating user', 'bhi')));
    }

    die();
  }
}

//--Lost Password
add_action('wp_ajax_user_lost_password', 'bhi_user_lost_password');
add_action('wp_ajax_nopriv_user_lost_password', 'bhi_user_lost_password');
function bhi_user_lost_password()
{

  $user_mail = (isset($_POST['user_mail']) ? $_POST['user_mail'] : '');

  if (strpos($user_mail, '@')) {
    $user = get_user_by('email', trim(wp_unslash($user_mail)));
    if (empty($user)) {
      echo json_encode(array('success' => false, 'message' => __('The user is not registered with this email.', 'bhi')));
      die();
    }
  } else {
    $user = get_user_by('login', trim($user_mail));
  }

  if (!$user) {
    echo json_encode(array('success' => false, 'message' => __('Incorrect username or email.', 'bhi')));
    die();
  }

  $key = get_password_reset_key($user);
  if (is_wp_error($key)) {
    echo json_encode(array('success' => false, 'message' => $key->get_error_message()));
    die();
  }

  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
  $title = __('Password recovery', 'bhi');

  $reset_page = get_field('option_reset_pass', 'option');
  if (empty($reset_page)) $reset_page = wp_login_url();

  $link = '<a href="' . $reset_page . '?action=rp&key=' . $key . '&login=' . rawurlencode($user->user_login) . '">' . __('link', 'bhi') . '</a>';
  // $link = get_the_permalink(PAGE_LOST_PASSWORD_ID) . '?key=' . $key . '&login=' . rawurlencode($user->user_login);
  $message = __('Password recovery', 'bhi') . '<br><br>' .
    __('We were prompted for a password reset on the site', 'bhi') . ' - <a href="' . home_url('/') . '">' . $blogname . '</a>.<br>' .
    __('If you did not submit this request, please ignore this email.', 'bhi') . '<br>' .
    __('To change your password, go to ', 'bhi') . ' - ' . $link;

  //file_put_contents(__DIR__ . '\reset_pas.txt', print_r($reset_page . '?action=rp&key=' . $key . '&login=' . rawurlencode($user->user_login), true));

  if (bhi_send_email($user->user_email, $title, $message)) {
    //file_put_contents(__DIR__ . '\Aresult.txt', print_r($blogname . $title . $link . $message, true));
    echo json_encode(array('success' => true));
    die();
  } else {
    echo json_encode(array('success' => false, 'message' => __('Failed to send email.', 'bhi')));
    die();
  }
}

//--User reset pass
add_action('wp_ajax_user_reset_pass', 'bhi_user_reset_pass');
add_action('wp_ajax_nopriv_user_reset_pass', 'bhi_user_reset_pass');
function bhi_user_reset_pass()
{

  $pass = (isset($_POST['pass']) ? $_POST['pass'] : '');
  $user_login = (isset($_POST['user_login']) ? trim($_POST['user_login']) : '');

  $user = get_user_by('login', $user_login);
  reset_password($user, $pass);

  $signon = array(
    'user_login'    => $user_login,
    'user_password' => $pass,
    'remember'      => true
  );
  wp_signon($signon);

  if (is_wp_error($signon)) {
    echo json_encode(array('success' => false, 'message' => '<p>' . __('Failed to reset password.', 'bhi') . '</p>'));
    die();
  }

  wc_setcookie('reset-password', 1);

  $redirect = user_redirect($user);

  echo json_encode(array('success' => true, 'redirect' => $redirect));
  die();
}

//--Employer post job
add_action('wp_ajax_post_job', 'bhi_post_job');
add_action('wp_ajax_nopriv_post_job', 'bhi_post_job');
function bhi_post_job()
{
  $user_id = get_current_user_id();
  $title = (isset($_POST['job']) ? $_POST['job'] : '');
  $company = (isset($_POST['company']) ? sanitize_text_field(trim($_POST['company'])) : '');
  $address = (isset($_POST['address']) ? sanitize_text_field(trim($_POST['address'])) : '');
  $salary = (isset($_POST['salary']) ? esc_html(trim($_POST['salary'])) : '');
  $desc = (isset($_POST['description']) ? trim($_POST['description']) : '');
  $job_id = (isset($_POST['job_id']) ? sanitize_text_field(trim($_POST['job_id'])) : '');

  $my_post = array(
    'post_title'    => wp_strip_all_tags($title),
    'post_status'   => 'publish',
    'post_type'     => 'jobs-posting',
    'post_content'  => $desc
  );

  // Insert the post into the database
  if ($job_id) {
    $id = $job_id;
    $my_post['ID'] = $id;
    wp_update_post($my_post);
    $mess = '<p>' . __('Your job has been successfully updated', 'bhi') . '</p>';
  } else {
    $my_post['post_author'] = $user_id;
    $id = wp_insert_post($my_post);
    $mess = '<p>' . __('Your job has been successfully published', 'bhi') . '</p>';
  }

  if (!is_wp_error($id)) {
    update_field('job_company_name', $company, $id);
    update_field('job_address', $address, $id);
    update_field('job_salary', $salary, $id);

    //echo $id;

    echo json_encode(array('success' => true, 'id' => $id, 'content' => $mess));
  } else {
    $err_mess = '';
    $err_mess = '<p>' . $id->get_error_message() . '</p>';
    if ($err_mess == '') $err_mess = '<p>' . __('An error occurred while creating the job. Please try again', 'bhi') . '</p>';
    echo json_encode(array('success' => false, 'content' => $err_mess));
  }

  die();
}

//--Employer edit job
add_action('wp_ajax_edit_job', 'bhi_edit_job');
add_action('wp_ajax_nopriv_edit_job', 'bhi_edit_job');
function bhi_edit_job()
{
  $job_post_id = (isset($_POST['job_post_id']) ? $_POST['job_post_id'] : '');

  $the_job_post = get_post($job_post_id);

  if (!empty($the_job_post)) {
    $title = $the_job_post->post_title;
    $descr = $the_job_post->post_content;
    $company  = get_field('job_company_name', $job_post_id);
    $address  = get_field('job_address', $job_post_id);
    $salary  = get_field('job_salary', $job_post_id);

    echo json_encode(array('success' => true, 'job_id' => $job_post_id, 'title' => $title, 'descr' => $descr, 'company' => $company, 'address' => $address, 'salary' => $salary));
  } else {
    echo json_encode(array('success' => false, 'job_id' => $job_post_id, 'content' => $the_job_post));
  }

  die();
}

//--Employer delete job
add_action('wp_ajax_delete_job', 'bhi_delete_job');
add_action('wp_ajax_nopriv_delete_job', 'bhi_delete_job');
function bhi_delete_job()
{
  $job_post_id = (isset($_POST['job_post_id']) ? $_POST['job_post_id'] : '');

  if (!empty($job_post_id)) {
    wp_delete_post($job_post_id);
    $mess = '<p>' . __('Vacancy successfully deleted', 'bhi') . '</p>';
    echo json_encode(array('success' => true, 'mess' => $mess));
  } else {
    $err_mess = '<p>' . __('An error occurred while deleting. Please try again', 'bhi') . '</p>';
    echo json_encode(array('success' => false, 'mess' => $err_mess));
  }

  die();
}

// -- job_popup
add_action('wp_ajax_job_popup', 'bhi_job_popup');
add_action('wp_ajax_nopriv_job_popup', 'bhi_job_popup');
function bhi_job_popup()
{

  $id = (isset($_POST['job_id']) ? $_POST['job_id'] : '');
  $html = '';

  if (!isset($id) && !empty($id)) {
    echo json_encode(array('success' => false, 'message' => esc_attr__('ID cannot be empty.', 'bhi')));
    die();
  }

  $title = get_the_title($id);
  $date = get_the_date("M d, Y", $id);
  $company = get_field('job_company_name', $id);
  $address = get_field('job_address', $id);
  $salary = get_field('job_salary', $id);

  $content_post = get_post($id);
  $content = $content_post->post_content;
  $content = apply_filters('the_content', $content);
  $content = str_replace(']]>', ']]&gt;', $content);

  $html .= '<div class="job-description-title">';
  $html .=  $date . '<br>' . $title . '<br>' . $company . ' | ' . $salary . '<br>' . $address . '<br><br>' . __('Job description', 'bhi');
  $html .= '</div>';
  $html .= '<div class="job-description-text article text-md">' . $content . '</div>';

  echo json_encode(array('success' => true, 'message' => esc_attr__('Success get data.', 'bhi'), 'contents' => $html));
  die();
}

//-- sent_event_request
add_action('wp_ajax_sent_event_request', 'bhi_sent_event_request');
add_action('wp_ajax_nopriv_sent_event_request', 'bhi_sent_event_request');
function bhi_sent_event_request()
{
  $event_id = (isset($_POST['event_id']) ? $_POST['event_id'] : '');
  $date = (isset($_POST['date']) ? $_POST['date'] : '');
  $reserved_num = (isset($_POST['num']) ? $_POST['num'] : '');
  $reserved_time = (isset($_POST['hours']) ? $_POST['hours'] : '');
  $phone = (isset($_POST['phone']) ? $_POST['phone'] : '');
  $terms = (isset($_POST['terms_res']) ? $_POST['terms_res'] : '');
  $created_after = (isset($_POST['created_after']) ? $_POST['created_after'] : '');

  $event_post = get_post($event_id);
  $user = wp_get_current_user();
  if (!empty($event_post) && $user && $user->ID != 0) {

    $name = $user->first_name . ' ' . $user->last_name;
    $username = $user->user_email;
    //$author_id = $event_post->post_author;
    $attendees_max = intval(get_field('attendees_max', $event_id));
    $attendees_available = intval(get_field('attendees_available', $event_id));
    $attendees_list_count = count(get_field('attendees_list', $event_id));

    $row = array(
      'name'          => !empty($name) ? $name : '',
      'username'      => !empty($username) ? $username : '',
      'phone'         => $phone,
      'reserved_num'  => $reserved_num,
      'reserved_time' => $reserved_time,
      'created_after' => $created_after,
    );

    if ($attendees_available != 0 && $attendees_list_count != $attendees_max) {
      add_row('attendees_list', $row, $event_id);
      update_field('attendees_available', $attendees_available - 1, $event_id);

      /*if ($attendees_available == 1) {
        $my_event = array(
          'post_status' => 'draft',
          'post_type'   => 'events',
          'post_author' => $author_id,
          'ID'          => $event_id
        );
        wp_update_post($my_event);
      }*/
    }

    $redirect_url = get_field('option_ty_page', 'option');
    if (!$redirect_url) $redirect_url = home_url();

    echo json_encode(array('success' => true, 'redirect' => $redirect_url));
  } else {
    echo json_encode(array('success' => false));
  }

  die();
}

// -- profession popup
add_action('wp_ajax_prof_popup', 'bhi_prof_popup');
add_action('wp_ajax_nopriv_prof_popup', 'bhi_prof_popup');
function bhi_prof_popup()
{

  $id = (isset($_POST['prof_id']) ? $_POST['prof_id'] : '');
  $html_title = $html_content = '';

  if (!isset($id) && !empty($id)) {
    echo json_encode(array('success' => false, 'message' => esc_attr__('ID cannot be empty.', 'bhi')));
    die();
  }

  $this_tax = get_term($id);
  $title = $this_tax->name;
  /*$content_post = get_post($id);
  $content = $content_post->post_content;
  $content = apply_filters('the_content', $content);
  $content = str_replace(']]>', ']]&gt;', $content);*/
  //$content = get_field('profession_popup', $id);
  $content = get_field('profession_popup', 'profession_' . $id);

  echo json_encode(array('success' => true, 'message' => esc_attr__('Success get data.', 'bhi'), 'title' => $title, 'content' => $content));
  die();
}

// -- -- Exam filter by Profession and state
add_action('wp_ajax_exam_filter', 'bhi_exam_filter');
add_action('wp_ajax_nopriv_exam_filter', 'bhi_exam_filter');
function bhi_exam_filter()
{
  $prof_select = (isset($_POST['prof_select']) ? $_POST['prof_select'] : '');
  $state_select = (isset($_POST['state_select']) ? $_POST['state_select'] : '');

  $examArgs = array(
    'post_type'     => 'exam',
    'numberposts'   => -1,
    'order'         => 'ASC',
    'orderby'       => 'name',
    'tax_query' => array(
      'relation' => 'AND',
      array(
        'taxonomy' => 'profession',
        'field'    => 'slug',
        'terms'    => $prof_select,
      ),
      array(
        'taxonomy' => 'states',
        'field'    => 'slug',
        'terms'    => $state_select,
      ),
    ),
  );

  $examPosts = get_posts($examArgs);
  $html_title = $html_content = '';
  if (!$examPosts) :

    echo json_encode(array('success' => false, 'message' => esc_attr__('No posts found.', 'bhi')));
    die();

  else : ?>

    <table class="job-table type3">
      <thead>
        <tr>
          <th><?php _e('Exam', 'bhi') ?></th>
          <th><?php _e('Hours', 'bhi') ?></th>
          <th><?php _e('Price', 'bhi') ?></th>
          <th><?php _e('Study Guide', 'bhi') ?></th>
          <th></th>
        </tr>
      </thead>
      <tbody>

        <?php foreach ($examPosts as $post) :
              setup_postdata($post);
              $exam_id = $post->ID;
              $exam_title = get_the_title($exam_id);
              $exam_hours = get_field('exam_hours', $exam_id);
              $exam_price =  get_field('exam_price', $exam_id);
              $exam_study_guide =  get_field('exam_study_guide', $exam_id);  ?>
          <tr>
            <td><?php echo esc_html($exam_title) ?></td>
            <td><?php echo esc_html($exam_hours) ?></td>
            <td>$<?php echo esc_html($exam_price) ?></td>
            <td><?php echo esc_html($exam_study_guide) ?></td>
            <td><a href="#<?php echo esc_html($exam_title) ?>" data-id="<?php echo esc_attr($exam_id) ?>" class="btn btn-primary exam_start"><?php _e('Take exam', 'bhi') ?></a>
            </td>
          </tr>
        <?php endforeach;
            wp_reset_postdata(); ?>
      </tbody>
    </table>

  <?php endif;
    $content = ob_get_contents();
    ob_end_clean();
    echo json_encode(array('success' => true, 'message' => esc_attr__('Success get post.', 'bhi'), 'content' => $content));
    die();
  }

  // -- Quiz result
  add_action('wp_ajax_quiz_result', 'bhi_quiz_result');
  add_action('wp_ajax_nopriv_quiz_result', 'bhi_quiz_result');
  function bhi_quiz_result()
  {
    $exam_accepted = (isset($_POST['exam_accepted']) ? $_POST['exam_accepted'] : '');
    $quiz_id = (isset($_POST['quiz_id']) ? $_POST['quiz_id'] : '');
    $quiz_answers = [];
    $total = 0;

    if ($exam_accepted != '' && $quiz_id != '') {

      $quiz_questions = get_field('quiz_questions', $quiz_id);
      $passing_score = get_field('quiz_passing_score', $quiz_id);
      if (empty($passing_score)) $passing_score = 0;
      $quiz_count = count($quiz_questions);

      foreach ($quiz_questions as $quiz_item) {
        $answer = $quiz_item['answers'];
        $quiz_answers[] = $answer;
      }

      for ($i = 0; $i <= 1; $i++) {
        if (strtolower($exam_accepted[$i]) == strtolower($quiz_answers[$i])) $total++;
      }

      if ($total != 0) $total = ($total * 100) / $quiz_count;

      if ($total >= $passing_score) $result = 1;
      else $result = 0;

      //file_put_contents(__DIR__ . '/result_res.txt', print_r($result, true));

      $cookie_name = 'exam_res';
      $exam_title = get_the_title($quiz_id);
      $cookie_value = $exam_title . '_' . $passing_score . '_' . $total . '_' . $result;
      setcookie($cookie_name, $cookie_value, time() + (3600), "/"); // 86400 = 1 day

      $redirect = get_field('option_quiz_ty', 'option');
      if (!$redirect) $redirect = home_url('/');
      $user_id = get_current_user_id();
      $license_cpt = bhi_add_user_license($user_id, $quiz_id, $total, $result);

      echo json_encode(array('success' => true, 'redirect' => $redirect, 'create' => $license_cpt));
    } else {
      echo json_encode(array('success' => false));
    }
    die();
  }

  // -- Save Quiz result - user license
  function bhi_add_user_license($user_id, $quiz_id, $total, $exam_result)
  {
    //$user_id = get_current_user_id();
    //file_put_contents(__DIR__ . '/user_id_res.txt', print_r($user_id, true));

    $cookie_name = 'quiz_license';
    $cookie_value = $quiz_id . '_' . $total . '_' . $exam_result;

    $user_meta = get_userdata($user_id);
    $user_roles = $user_meta->roles;

    if ($user_id != '' && $quiz_id != '' && in_array('customer', (array) $user_roles)) {

      $licenseArgs = array(
        'post_type'     => 'licenses',
        'numberposts'   => -1,
        'order'         => 'ASC',
        'orderby'       => 'name',
        'meta_query'  => array(
          'relation'    => 'AND',
          array(
            'key'     => 'license_user_id',
            'value'      => $user_id
          ),
          array(
            'key'      => 'license_quiz_id',
            'value'      => $quiz_id,
          ),
        ),
      );

      $licensePosts = get_posts($licenseArgs);
      $license_id = $licensePosts[0]->ID;
      $exam_title = get_the_title($quiz_id);
      $license_post = array(
        'post_title'    => wp_strip_all_tags('User_' . $user_id . '_' . $exam_title),
        'post_status'   => 'publish',
        'post_type'     => 'licenses',
      );

      // Insert the post into the database
      if ($license_id) {
        $id = $license_id;
        $license_post['ID'] = $id;
        wp_update_post($license_post);
        $mess = '<p>' . __('Your license has been successfully updated', 'bhi') . '</p>';
      } else {
        $license_post['post_author'] = $user_id;
        $id = wp_insert_post($license_post);
        $mess = '<p>' . __('Your license has been successfully published', 'bhi') . '</p>';
      }

      if ($exam_result != false) $license_result = $total . '%';
      else $license_result = 'Not pass';

      $license_cost = get_field('exam_price', $quiz_id);

      if (!is_wp_error($id)) {
        update_field('license_user_id', $user_id, $id);
        update_field('license_quiz_id', $quiz_id, $id);
        update_field('license_name', $exam_title, $id);
        update_field('license_result', $license_result, $id);
        update_field('license_cost', $license_cost, $id);
        //update_field('license_number', $address, $id);
        update_field('license_payment_status', 0, $id); // true-1 / false-0
      }
      //setcookie($cookie_name, '', time() - 3600); // delete cookies
      setcookie($cookie_name, null, -1, '/');
      return true;
    } else {
      setcookie($cookie_name, $cookie_value, time() + (3600), "/"); // 86400 = 1 day
      return false;
    }
  }

  // -- Update my license key
  add_action('wp_ajax_update_lic_key', 'bhi_update_lic_key');
  add_action('wp_ajax_nopriv_update_lic_key', 'bhi_update_lic_key');
  function bhi_update_lic_key()
  {
    $license_number = (isset($_POST['input_lic_number']) ? $_POST['input_lic_number'] : '');
    $license_id = (isset($_POST['lic_id']) ? $_POST['lic_id'] : '');

    if ($license_number != '' && $license_id != '') {
      update_field('license_number', $license_number, $license_id);
      //wp_set_post_terms($post_id, 'название метки', 'post_tag', true );
      echo json_encode(array('success' => true));
    } else {
      echo json_encode(array('success' => false));
    }
    die();
  }

  // -- license payment by Merchat One
  add_action('wp_ajax_license_payment', 'bhi_license_payment');
  add_action('wp_ajax_nopriv_license_payment', 'bhi_license_payment');
  function bhi_license_payment()
  {

    $username = get_field('merchant_login', 'option');
    $password = get_field('merchant_pass', 'option');

    $amount = (isset($_POST['amount']) ? $_POST['amount'] : '');
    $ccnumber = (isset($_POST['ccnumber']) ? $_POST['ccnumber'] : '');
    $ccexp = (isset($_POST['ccexp']) ? $_POST['ccexp'] : '');
    $cvv = (isset($_POST['cvv']) ? $_POST['cvv'] : '');
    $lic_post_id = (isset($_POST['lic_post_id']) ? $_POST['lic_post_id'] : '');


    if ($username != '' && $password != '') {
      $query  = "";

      $query .= "username=" . urlencode($username) . "&";
      $query .= "password=" . urlencode($password) . "&";

      $query .= "ccnumber=" . urlencode($ccnumber) . "&";
      $query .= "ccexp=" . urlencode($ccexp) . "&";
      $query .= "amount=" . urlencode(number_format($amount, 2, ".", "")) . "&";
      $query .= "cvv=" . urlencode($cvv) . "&";
      $query .= "type=sale";

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://secure.merchantonegateway.com/api/transact.php");
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
      curl_setopt($ch, CURLOPT_TIMEOUT, 15);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

      curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
      curl_setopt($ch, CURLOPT_POST, 1);

      if (!($data = curl_exec($ch))) {
        echo json_encode(array('success' => false));
      }
      curl_close($ch);
      unset($ch);
      //print "\n$data\n";
      $data = explode("&", $data);

      $rdata = explode("=", $data[8]);
      $res_pay = $rdata[1];

      switch ($res_pay[0]) {
        case '1':
          $result_payment = 'Transaction Approved';
          update_field('license_payment_status', 1, $lic_post_id); // TRUE-1 / FALSE-0
          break;
        case '2':
          $result_payment = 'Transaction Declined';
          break;
        case '3':
          $result_payment = 'Transaction was Rejected by Gateway';
          break;
        case '4':
          $result_payment = 'Transaction Error';
          break;
      }
      echo json_encode(array('success' => true, 'result' => $result_payment));
    }
    die();
  }


  function bhi_get_cart_total()
  {
    $total = $cart_total  = '';
    if (WC()->cart && !WC()->cart->is_empty()) {
      $total = WC()->cart->get_cart_contents_count();


      if (!empty($total) && $total != 0) $cart_total = $total;
      else $cart_total = '';
    }

    return $cart_total;
  }