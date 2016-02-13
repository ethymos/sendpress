<?php


// Prevent loading this file directly
if (!defined('SENDPRESS_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    die;
}

if (!class_exists('SendPress_Sender_Website')) {

    class SendPress_Sender_Website extends SendPress_Sender
    {

        function label()
        {
            return __('Your Website', 'sendpress');
        }

        function save()
        {
            if (isset($_POST['smtpauth'])) {
                SendPress_Option::set('smtpauth', true);
            } else {
                SendPress_Option::set('smtpauth', false);
            }

            if (isset($_POST['smtpssl'])) {
                SendPress_Option::set('smtpssl', true);
            } else {
                SendPress_Option::set('smtpssl', false);
            }

            SendPress_Option::set('smtpuser', $_POST['smtpuser']);
            SendPress_Option::set('smtppass', $_POST['smtppass']);
            SendPress_Option::set('smtphost', $_POST['smtphost']);
            SendPress_Option::set('smtpport', $_POST['smtpport']);
        }

        function settings()
        {
            ?>
            <p><label><?php _e('Use authentication', 'sendpress'); ?>
                    <input name="smtpauth" type="checkbox"
                           value="1" <?php echo(SendPress_Option::get('smtpauth') == "1" ? "checked" : ""); ?>  /></label>
            </p>
            <p><label><?php _e('Use SSL', 'sendpress'); ?>
                    <input name="smtpssl" type="checkbox"
                           value="1" <?php echo(SendPress_Option::get('smtpssl') == "1" ? "checked" : ""); ?>  /></label>
            </p>
            <?php _e('Username', 'sendpress'); ?>
            <p><input name="smtpuser" type="text" value="<?php echo SendPress_Option::get('smtpuser'); ?>"
                      style="width:100%;"/></p>
            <?php _e('Password', 'sendpress'); ?>
            <p><input name="smtppass" type="password" value="<?php echo SendPress_Option::get('smtppass'); ?>"
                      style="width:100%;"/></p>
            <?php _e('Host', 'sendpress'); ?>
            <p><input name="smtphost" type="text" value="<?php echo SendPress_Option::get('smtphost'); ?>"
                      style="width:100%;"/></p>
            <?php _e('Port', 'sendpress'); ?>
            <p><input name="smtpport" type="text" value="<?php echo SendPress_Option::get('smtpport'); ?>"
                      style="width:100%;"/></p>
        <?php

        }

        function send_email($to, $subject, $html, $text, $istest = false, $sid, $list_id, $report_id)
        {
            global $phpmailer;

            // (Re)create it, if it's gone missing
            if (!is_object($phpmailer) || !is_a($phpmailer, 'PHPMailer')) {
                require_once ABSPATH . WPINC . '/class-phpmailer.php';
                require_once ABSPATH . WPINC . '/class-smtp.php';
                $phpmailer = new PHPMailer();
            }
            /*
             * Make sure the mailer thingy is clean before we start,  should not
             * be necessary, but who knows what others are doing to our mailer
             */
            $phpmailer->ClearAddresses();
            $phpmailer->ClearAllRecipients();
            $phpmailer->ClearAttachments();
            $phpmailer->ClearBCCs();
            $phpmailer->ClearCCs();
            $phpmailer->ClearCustomHeaders();
            $phpmailer->ClearReplyTos();

            $charset = SendPress_Option::get('email-charset', 'UTF-8');
            $encoding = SendPress_Option::get('email-encoding', '8bit');

            $phpmailer->CharSet = $charset;
            $phpmailer->Encoding = $encoding;

            if ($charset != 'UTF-8') {
                $html = $this->change($html, 'UTF-8', $charset);
                $text = $this->change($text, 'UTF-8', $charset);
                $subject = $this->change($subject, 'UTF-8', $charset);

                $subject = str_replace(array('â€™','â€œ','â€�','â€“'),array("'",'"','"','-'),$subject);
                $html = str_replace(chr(194),chr(32),$html);
                $text = str_replace(chr(194),chr(32),$text);
            }

            $phpmailer->AddAddress(trim($to));
            $phpmailer->AltBody = $text;
            $phpmailer->Subject = $subject;
            $content_type = 'text/html';
            $phpmailer->MsgHTML($html);
            $phpmailer->ContentType = $content_type;
            // Set whether it's plaintext, depending on $content_type
            if ( 'text/html' == $content_type ) {
                $phpmailer->IsHTML(true);
            }

            /**
             * We'll let php init mess with the message body and headers.  But then
             * we stomp all over it.  Sorry, my plug-inis more important than yours :)
             */
            do_action_ref_array('phpmailer_init', array(&$phpmailer));
            
            $phpmailer->Mailer = 'smtp';
            // We are sending SMTP mail
            $phpmailer->IsSMTP();
            // Set the other options
            $phpmailer->Host = SendPress_Option::get('smtphost');
            $phpmailer->SMTPAuth = (SendPress_Option::get('smtpauth') == "1" ? true : false); // authentication enabled
            $phpmailer->SMTPSecure = (SendPress_Option::get('smtpssl') == "1" ? 'ssl' : '');

            $phpmailer->Port = SendPress_Option::get('smtpport');;
            // If we're using smtp auth, set the username & password
            $phpmailer->Username = SendPress_Option::get('smtpuser');
            $phpmailer->Password = SendPress_Option::get('smtppass');

            // If we don't have a charset from the input headers
            if ( !isset( $charset ) ) {
                $charset = get_bloginfo( 'charset' );
                // Set the content-type and charset
            }

            $from_email = SendPress_Option::get('fromemail');
            $phpmailer->From = $from_email;
            $phpmailer->FromName = SendPress_Option::get('fromname');
            $phpmailer->Sender = $from_email;
            $phpmailer->ReturnPath = SendPress_Option::get('bounceemail');

            $hdr = new SendPress_SendGrid_SMTP_API();
            $hdr->addFilterSetting('dkim', 'domain', SendPress_Manager::get_domain_from_email($from_email));
            //$phpmailer->AddCustomHeader( sprintf( 'X-SP-MID: %s',$email->messageID ) );
            $phpmailer->AddCustomHeader(sprintf('X-SMTPAPI: %s', $hdr->asJSON()));
            $phpmailer->AddCustomHeader('X-SP-METHOD: website');
            // Set SMTPDebug to 2 will collect dialogue between us and the mail server
            $phpmailer->AddCustomHeader('X-SP-LIST: ' . $list_id);
            $phpmailer->AddCustomHeader('X-SP-REPORT: ' . $report_id);
            $phpmailer->AddCustomHeader('X-SP-SUBSCRIBER: ' . $sid);
            if ($istest == true) {
                $phpmailer->SMTPDebug = 2;

                // Start output buffering to grab smtp output
                ob_start();
            }

            // Send!
            $result = true; // start with true, meaning no error
            $result = @$phpmailer->Send();

            $phpmailer->SMTPClose();
            if ($istest == true) {
                // Grab the smtp debugging output
                $smtp_debug = ob_get_clean();
                SendPress_Option::set('phpmailer_error', $phpmailer->ErrorInfo);
                SendPress_Option::set('last_test_debug', $smtp_debug);
                $this->last_send_smtp_debug = $smtp_debug;

            }

            if ($result != true && $istest == true) {
                $hostmsg = 'host: ' . ($phpmailer->Host) . '  port: ' . ($phpmailer->Port) . '  secure: ' . ($phpmailer->SMTPSecure) . '  auth: ' . ($phpmailer->SMTPAuth) . '  user: ' . ($phpmailer->Username) . "  pass: *******\n";
                $msg = '';
                $msg .= __('The result was: ', 'sendpress') . $result . "\n";
                $msg .= __('The mailer error info: ', 'sendpress') . $phpmailer->ErrorInfo . "\n";
                $msg .= $hostmsg;
                $msg .= __("The SMTP debugging output is shown below:\n", "sendpress");
                $msg .= $smtp_debug . "\n";
                $msg .= 'The full debugging output(exported mailer) is shown below:\n';
                $msg .= var_export($phpmailer,true)."\n";
                $this->append_log($msg);
            }

            return $result;

        }

    }
}