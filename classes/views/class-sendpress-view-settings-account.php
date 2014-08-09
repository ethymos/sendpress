<?php


// Prevent loading this file directly
if ( !defined( 'SENDPRESS_VERSION' ) ) {
  header( 'HTTP/1.0 403 Forbidden' );
  die;
}


class SendPress_View_Settings_Account extends SendPress_View_Settings {

    function account_setup()
    {


        $options = array();


        $options['sendmethod'] = $_POST['sendpress-sender'];
        // Provides: Hll Wrld f PHP
        $chars = array(".", ",", " ", ":", ";", "$", "%", "*", "-", "=");
        $options['emails-per-day'] = str_replace($chars, "", $_POST['emails-per-day']);
        $options['emails-per-hour'] = str_replace($chars, "", $_POST['emails-per-hour']);
        $options['emails-credits'] = str_replace($chars, "", $_POST['emails-credits']);
        $options['email-charset'] = $_POST['email-charset'];
        $options['email-encoding'] = $_POST['email-encoding'];

        $options['phpmailer_error'] = '';
        $options['last_test_debug'] = '';
        SendPress_Option::set($options);

        global $sendpress_sender_factory;

        $senders = $sendpress_sender_factory->get_all_senders();

        foreach ($senders as $key => $sender) {
            $sender->save();
        }

        SendPress_Admin::redirect('Settings_Account');


    }

  function send_test_email(){
        $options = array();
        $options['testemail'] = $_POST['testemail'];
        
        SendPress_Option::set($options);
        SendPress_Manager::send_test();
       // $this->send_test();
       // $this->redirect();
  }


  function html( $sp ) {

      if (is_multisite() && !is_super_admin()) {
          return;
      }

    global  $sendpress_sender_factory;
    $senders = $sendpress_sender_factory->get_all_senders();
    ksort($senders);
    $method = SendPress_Option::get( 'sendmethod' );
?>
<div style="float:right;" >
  <a href="" class="btn btn-large btn-default" ><i class="icon-remove"></i> <?php _e( 'Cancel', 'sendpress' ); ?></a> <a href="#" id="save-update" class="btn btn-primary btn-large"><i class="icon-white icon-ok"></i> <?php _e( 'Save', 'sendpress' ); ?></a>
</div>
<br class="clear"><br class="clear">
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title"><?php _e('Sending Account Setup', 'sendpress'); ?></h3>
  </div>
  <div class="panel-body">

<form method="post" id="post">
  <input type="hidden" name="action" value="account-setup" />

  <?php if( count($senders) < 3 ){
      $c= 0;
     foreach ( $senders as $key => $sender ) {
      $class ='';
      if ( $c >= 1 ) { $class = "margin-left: 4%"; }
      echo "<div style=' float:left; width: 48%; $class' id='$key'>";
      ?>      
        <p>&nbsp;<input name="sendpress-sender" type="radio"  <?php if ( $method == $key || strpos(strtolower($key) , $method) > 0 ) { ?>checked="checked"<?php } ?> id="website" value="<?php echo $key; ?>" /> <?php _e('Send Emails via', 'sendpress'); ?>
        <?php
        echo $sender->label();
        echo "</p><div class='well'>";
        echo $sender->settings();
      echo "</div></div>";
      $c++;
    }  



  } else { ?>
  <div class="tabbable tabs-left">
    <ul class="nav nav-tabs">
    <?php
    foreach ( $senders as $key => $sender ) {
      $class ='';
      if ( $method == $key || strpos(strtolower($key) , $method) > 0 ) { $class = "class='active'"; }
      echo "<li $class><a href='#$key' data-toggle='tab'>";
      if ( $method == $key || strpos(strtolower($key) , $method) > 0 ) { echo '<span class="glyphicon glyphicon-ok-sign"></span> '; }
      echo $sender->label();
      echo "</a></li>";
    }
?>
    </ul>
    <div class="tab-content">
      <?php
    foreach ( $senders as $key => $sender ) {
      $class ='';
      if ( $method == $key || strpos(strtolower($key) , $method) > 0 ) { $class = "active"; }
      echo "<div class='tab-pane $class' id='$key'>";
?>      
        <p>&nbsp;<input name="sendpress-sender" type="radio"  <?php if ( $method == $key || strpos(strtolower($key) , $method) > 0 ) { ?>checked="checked"<?php } ?> id="website" value="<?php echo $key; ?>" /> Activate
        <?php
        echo $sender->label();
        echo "</p><div class='well'>";
        echo $sender->settings();
      echo "</div></div>";
    }
?>

    </div>
</div>


<p > <span class="glyphicon glyphicon-ok-sign"></span> = Currently Active</p>
<?php } ?>

</div>
</div>
<br class="clear">
<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title"><?php _e('Advanced Sending Options', 'sendpress'); ?></h3>
  </div>
  <div class="panel-body">
<div class="boxer form-box">
  <div>
    <h2><?php _e('Email Sending Limits', 'sendpress'); ?></h2>
    
<?php
  $emails_per_day = SendPress_Option::get('emails-per-day');
  $emails_per_hour =  SendPress_Option::get('emails-per-hour');
  $credits = SendPress_Option::get('emails-credits');
 
  $emails_so_far = SendPress_Data::emails_sent_in_queue("day");
?><?php
$offset = get_option( 'gmt_offset' ) * 60 * 60; // Time offset in seconds
$local_timestamp = wp_next_scheduled('sendpress_cron_action') + $offset;
//print_r(wp_get_schedules());
?>
<?php sprintf(__('You have sent <strong>%s</strong> emails so far today and you have <strong>%s</strong> credits remaining.', 'sendpress'), $emails_so_far, $credits); ?><br><br>
<input type="text" size="6" name="emails-per-day" value="<?php echo $emails_per_day; ?>" /> <?php _e('Emails Per Day', 'sendpress'); ?><br><br>
<input type="text" size="6" name="emails-per-hour" value="<?php echo $emails_per_hour; ?>" /> <?php _e('Emails Per Hour', 'sendpress'); ?><br><br>
<input type="text" size="6" name="emails-credits" value="<?php echo $credits; ?>" /> <?php _e('Available credits', 'sendpress'); ?>
<br><br>
<h2><?php _e('Email Encoding', 'sendpress'); ?></h2>
<?php
  $charset = SendPress_Option::get('email-charset','UTF-8');
 ?><?php _e('Charset: ', 'sendpress'); ?>
<select name="email-charset" id="">

<?php
$charsete = SendPress_Data::get_charset_types();
  foreach ( $charsete as $type) {
     $select="";
    if($type == $charset){
      $select = " selected ";
    }
    echo "<option $select value=$type>$type</option>";

  }
?>
</select><br>
      <?php _e('Squares or weird characters displaying in your emails select the charset for your language.', 'sendpress'); ?>
<br><br>
      <?php _e('Encoding:', 'sendpress'); ?> <select name="email-encoding" id="">
<?php
 $charset = SendPress_Option::get('email-encoding','8bit');
$charsete = SendPress_Data::get_encoding_types();
  foreach ( $charsete as $type) {
     $select="";
    if($type == $charset){
      $select = " selected ";
    }
    echo "<option $select value=$type>$type</option>";

  }
?>
</select>
  </div>  
  
</div>
</div>
</div>


<?php 
//Page Nonce
//wp_nonce_field(  basename(__FILE__) ,'_spnonce' );
wp_nonce_field( $sp->_nonce_value );
?>
<input type="submit" class="btn btn-primary" value="<?php _e('Save', 'sendpress'); ?>"/> <a href="" class="btn btn-default"><i class="icon-remove"></i> <?php _e('Cancel', 'sendpress'); ?></a>
</form>
<form method="post" id="post" class="form-inline">
<input type="hidden" name="action" value="send-test-email" />
<br class="clear">
<div class="alert alert-success">
  <?php _e( '<b>NOTE: </b>Remember to check your Spam folder if you do not seem to be receiving emails', 'sendpress' ); ?>.
</div>

<div class="panel panel-default">
  <div class="panel-heading">
    <h3 class="panel-title"><?php _e( 'Send Test Email', 'sendpress' ); ?></h3>
  </div>
  <div class="panel-body">
<div class="form-group">
<input name="testemail" type="text" id="appendedInputButton" value="<?php echo SendPress_Option::get( 'testemail' ); ?>" class="form-control"/>
</div>
<button class="btn btn-primary" type="submit"><?php _e( 'Send Test!', 'sendpress' ); ?></button><button class="btn btn-danger" data-toggle="modal" data-target="#debugModal" type="button"><?php _e( 'Debug Info', 'sendpress' ); ?></button>
<br class="clear">

</div></div>

<?php 
//Page Nonce
//wp_nonce_field(  basename(__FILE__) ,'_spnonce' );
//SendPress General Nonce
wp_nonce_field( $sp->_nonce_value );
?>
</form>
<?php
    $error=  SendPress_Option::get( 'phpmailer_error' );
    $hide = 'hide';
    if ( !empty( $error ) ) {
      $hide = '';
      $phpmailer_error = '<pre>'.$error.'</pre>';
?>
  <script type="text/javascript">
  jQuery(document).ready(function($) {
    $('#debugModal').modal('show');
  });
  </script>

  <?php
    }


?>


<div class="modal fade" id="debugModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
  <div class="modal-content">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3><?php _e( 'SMTP Debug Info', 'sendpress' ); ?></h3>
  </div>
  <div class="modal-body">
    <?php
    if ( !empty( $phpmailer_error ) ) {
      $server  = "smtp.sendgrid.net";
      $port   = "25";
      $port2   = "465";
      $port3   = "587";
      $timeout = "1";

      if ( $server and $port and $timeout ) {
        $port25 =  @fsockopen( "$server", $port, $errno, $errstr, $timeout );
        $port465 =  @fsockopen( "$server", $port2, $errno, $errstr, $timeout );
        $port587 =  @fsockopen( "$server", $port3, $errno, $errstr, $timeout );
      }
      if ( !$port25 ) {
        echo '<div class="alert alert-error">';
        _e( 'Port 25 seems to be blocked.', 'sendpress' );
        echo '</div>';

      }
      if ( !$port465 ) {
        echo '<div class="alert alert-error">';
        _e( 'Port 465 seems to be blocked. Gmail may have trouble', 'sendpress' );
        echo '</div>';

      }
      if ( !$port587 ) {
        echo '<div class="alert alert-error">';
        _e( 'Port 587 seems to be blocked.', 'sendpress' );
        echo '</div>';

      }

      echo $phpmailer_error;
    } ?>


    <pre>
<?php




    $whoops = SendPress_Option::get( 'last_test_debug' );
    if ( empty( $whoops ) ) {
      _e( 'No Debug info saved.', 'sendpress' );
    } else {
      echo $whoops;
    }
?>
</pre>
  </div>
  <div class="modal-footer">
    <a href="#" class="btn" data-dismiss="modal"><?php _e( 'Close', 'sendpress' ); ?></a>
  </div>
</div>
</div></div>
<?php
  }

}
