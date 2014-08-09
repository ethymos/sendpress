<?php

// Prevent loading this file directly
if (!defined('SENDPRESS_VERSION')) {
    header('HTTP/1.0 403 Forbidden');
    die;
}

/**
 * SendPress_View_Queue
 *
 * @uses     SendPress_View
 *
 */
class SendPress_View_Queue extends SendPress_View
{


    function admin_init()
    {
        add_action('load-sendpress_page_sp-queue', array($this, 'screen_options'));

        SendPress_Data::clean_queue_table();


    }


    function sub_menu($sp = false)
    {
        ?>
        <div class="navbar navbar-default">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse"
                        data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>

                </button>
                <a class="navbar-brand" href="#"><?php _e('Queues', 'sendpress'); ?></a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li <?php if (!isset($_GET['view'])){ ?>class="active"<?php } ?> >
                        <a href="<?php echo SendPress_Admin::link('Queue'); ?>"><span
                                class="glyphicon glyphicon-transfer"></span>  <?php _e('Active', 'sendpress'); ?></a>
                    </li>
                    <li <?php if (isset($_GET['view']) && $_GET['view'] === 'all'){ ?>class="active"<?php } ?> >
                        <a href="<?php echo SendPress_Admin::link('Queue_All'); ?>"><span
                                class="glyphicon glyphicon-time"></span>  <?php _e('Send History', 'sendpress'); ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <?php

        do_action('sendpress-queue-sub-menu');
    }

    function screen_options()
    {

        $screen = get_current_screen();


        $args = array(
            'label' => __('Emails per page', 'sendpress'),
            'default' => 10,
            'option' => 'sendpress_queue_per_page'
        );
        add_screen_option('per_page', $args);
    }

    function empty_queue($get, $sp)
    {
        SendPress_Data::delete_queue_emails();
        SendPress_Admin::redirect('Queue');
    }

    function reset_queue()
    {
        SendPress_Data::requeue_emails();
        SendPress_Admin::redirect('Queue');
    }

    function reset_counters()
    {
        SendPress_Manager::reset_counters();
        SendPress_Admin::redirect('Queue');
    }

    function html($sp)
    {

        SendPress_Tracking::event('Queue Tab');
        if (isset($_GET['cron'])) {
            $sp->fetch_mail_from_queue();
        }

        //Create an instance of our package class...
        $testListTable = new SendPress_Queue_Table();
        //Fetch, prepare, sort, and filter our data...
        $testListTable->prepare_items();
        SendPress_Option::set('no_cron_send', 'false');
        $sp->fetch_mail_from_queue();
        $sp->cron_start(); ?>
        <div id="taskbar" class="lists-dashboard rounded group">

            <div id="button-area">
                <a id="send-now" class="btn btn-primary btn-large " data-toggle="modal" href="#sendpress-sending"><i
                        class="icon-white icon-refresh"></i> <?php _e('Send Emails Now', 'sendpress'); ?></a>
            </div>
            <?php
            $emails_per_day = SendPress_Option::get('emails-per-day');
            if ($emails_per_day == 0) {
                $emails_per_day = __('Unlimited', 'sendpress');
            }
            $emails_per_hour = SendPress_Option::get('emails-per-hour');
            $credits = SendPress_Option::get('emails-credits');
            $hourly_emails = SendPress_Data::emails_sent_in_queue("hour");
            $emails_so_far = SendPress_Data::emails_sent_in_queue("day");
            $autocron = SendPress_Option::get('autocron', 'no');
            //print_r(SendPress_Data::emails_stuck_in_queue());
            ?>


            <h2><?php _e('You have', 'sendpress'); ?>
                <strong><?php echo $credits; ?></strong> <?php _e('credits', 'sendpress'); ?>.</h2>
            <?php if ($credits <= 0) { ?>
                <small
                    style="color:red;"><?php _e('You don\'t have any credits. To send the emails in your queue or send new emails, you need to get more credits.', 'sendpress'); ?></small>
            <?php } ?>
            <h2><strong><?php echo $emails_so_far; ?></strong> <?php _e('of a possible', 'sendpress'); ?>
                <strong><?php echo $emails_per_day; ?></strong> <?php _e('emails sent in the last 24 hours', 'sendpress'); ?>
                .</h2>

            <h2><strong><?php echo $hourly_emails; ?></strong> <?php _e('of a possible', 'sendpress'); ?>
                <strong><?php echo $emails_per_hour; ?></strong> <?php _e('emails sent in the last hour', 'sendpress'); ?>
                .</h2>
            <?php if ((is_multisite() && is_super_admin()) || !is_multisite()) { ?>
                <small> <?php _e('You can adjust these settings here:', 'sendpress'); ?> <a
                        href="<?php echo SendPress_Admin::link('Settings_Advanced'); ?>"><?php _e('Settings > Advanced', 'sendpress'); ?></a>.
                </small>
            <?php } ?>
            <?php
            if ($autocron == 'no') {
                $offset = get_option('gmt_offset') * 60 * 60; // Time offset in seconds
                $local_timestamp = wp_next_scheduled('sendpress_cron_action') + $offset;

                ?><br>
                <small><?php _e('The cron will run again around:', 'sendpress'); ?> <?php
                    echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $local_timestamp);
                    ?></small>
            <?php } ?>
            <br><br>
        </div>
        <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
        <form id="email-filter" action="<?php echo SendPress_Admin::link('Queue'); ?>" method="get">
            <!-- For plugins, we also need to ensure that the form posts back to our current page -->
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
            <!-- Now we can render the completed list table -->
            <?php $testListTable->display() ?>
            <?php wp_nonce_field($sp->_nonce_value); ?>
        </form>
        <br>
        <a class="btn btn-large btn-success " href="<?php echo SendPress_Admin::link('Queue'); ?>&action=reset-queue"><i
                class="icon-repeat icon-white "></i> <?php _e('Re-queue All Emails', 'sendpress'); ?></a><br><br>
        <form method='get'>
            <input type='hidden' value="<?php echo $_GET['page']; ?>" name="page"/>

            <input type='hidden' value="empty-queue" name="action"/>
            <a class="btn btn-large  btn-danger" data-toggle="modal" href="#sendpress-empty-queue"><i
                    class="icon-warning-sign "></i> <?php _e('Delete All Emails in the Queue', 'sendpress'); ?></a>
            <?php wp_nonce_field($sp->_nonce_value); ?>
        </form>
        <div class="modal fade" id="sendpress-empty-queue" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">×</button>
                        <h3><?php _e('Really? Delete All Emails in the Queue.', 'sendpress'); ?></h3>
                    </div>
                    <div class="modal-body">
                        <p><?php _e('This will remove all emails from the queue without attempting to send them', 'sendpress'); ?>
                            .</p>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary"
                           data-dismiss="modal"><?php _e('No! I was Joking', 'sendpress'); ?></a><a
                            href="<?php echo SendPress_Admin::link('Queue'); ?>&action=empty-queue" id="confirm-delete"
                            class="btn btn-danger"><?php _e('Yes! Delete All Emails', 'sendpress'); ?></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="sendpress-sending" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
             aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">×</button>
                        <h3><?php _e('Sending Emails', 'sendpress'); ?></h3>
                    </div>
                    <div class="modal-body">
                        <div id="sendbar" class="progress progress-striped
     active">
                            <div id="sendbar-inner" class="progress-bar"
                                 style="width: 40%;"></div>
                        </div>
                        Sent <span id="queue-sent">-</span> <?php _e('of', 'sendpress'); ?> <span
                            id="queue-total">-</span> emails.<br>
                        You are currently sending 1 email approximately every <?php
                        $hour = SendPress_Option::get('emails-per-hour');
                        if ($hour != 0) {
                            $rate = 3600 / $hour;
                            if ($rate > 8) {
                                $rate = 8;
                            }
                        } else {
                            $rate = "0.25";
                        }

                        echo $rate;

                        ?> seconds.<br>
                        You are also limited to <?php echo $hour; ?> emails per hour.<br>
                        To change these settings go to <a
                            href="<?php echo SendPress_Admin::link('Settings_Account'); ?>">Settings > Sending
                            Account</a>.
                    </div>
                    <div class="modal-footer">
                        <?php _e('If you close this window sending will stop. ', 'sendpress'); ?><a href="#"
                                                                                                    class="btn btn-primary"
                                                                                                    data-dismiss="modal"><?php _e('Close', 'sendpress'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

}

SendPress_Admin::add_cap('Queue', 'sendpress_queue');