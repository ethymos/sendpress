<?php

// Prevent loading this file directly
if ( !defined('SENDPRESS_VERSION') ) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

class SendPress_View_Settings_Advanced extends SendPress_View_Settings {
	
	function save($post, $sp){

	if(isset( $post['allow_tracking'] )){
		SendPress_Option::set('allow_tracking', 'yes' );
		SendPress_Option::set('feedback', 'yes' );
	} else {
		SendPress_Option::set('allow_tracking', 'no' );
		SendPress_Option::set('feedback', 'no' );
	}
	if(isset( $post['old_permalink'] )){
		SendPress_Option::set('old_permalink', true );
	} else {
		SendPress_Option::set('old_permalink', false );
	}

	SendPress_Option::set('sync-per-call', $post['sync-per-call'] );
	SendPress_Option::set('autocron-per-call', $post['autocron-per-call'] );
	SendPress_Option::set('wpcron-per-call', $post['wpcron-per-call'] );
	SendPress_Option::set('queue-history', $post['queue-history'] );
	$widget_options =  array();

        $widget_options['widget_options']['load_css'] = 0;
        $widget_options['widget_options']['load_ajax'] = 0;
        $widget_options['widget_options']['load_scripts_in_footer'] = 0;
        if(isset($_POST['load_css'])){
            $widget_options['widget_options']['load_css'] = $_POST['load_css'];
        }
        if(isset($_POST['load_ajax'])){
            $widget_options['widget_options']['load_ajax'] = $_POST['load_ajax'];
        }
        if(isset($_POST['load_scripts_in_footer'])){
            $widget_options['widget_options']['load_scripts_in_footer'] = $_POST['load_scripts_in_footer'];
        }

        SendPress_Option::set($widget_options); 

        SendPress_Admin::redirect('Settings_Advanced');
	}



	function reset_transients(){
		delete_transient('sendpress_weekly_post_notification_check');
		delete_transient('sendpress_daily_post_notification_check');
		delete_transient('sendpress_post_notification_check');
		wp_clear_scheduled_hook('sendpress_post_notification_check');
	}

	function html($sp) {
		?><form method="post" id="post">
<div style="float:right;" >
	<a href="<?php echo SendPress_Admin::link('Settings_Advanced'); ?>" class="btn btn-large btn-default" ><i class="icon-remove"></i> <?php _e('Cancel','sendpress'); ?></a> <a href="#" id="save-update" class="btn btn-primary btn-large"><i class="icon-white icon-ok"></i> <?php _e('Save','sendpress'); ?></a>
</div>
<br class="clear">
		<br class="clear">
<div class="sp-row">
	<div class="sp-50 sp-first">
	<div class="panel panel-default">
	  <div class="panel-heading">
	    <h3 class="panel-title"><?php _e('Javascript & CSS', 'sendpress'); ?></h3>
	  </div>
	  <div class="panel-body">

		<?php 
				$widget_options = SendPress_Option::get('widget_options');
				//print_r($widget_options);
			?>
			<input class="turnoff-css-checkbox sendpress_checkbox" value="<?php echo $widget_options['load_css']; ?>" type="checkbox" <?php if( $widget_options['load_css'] == 1 ){ echo 'checked'; } ?> id="load_css" name="load_css"/>  <?php _e('Disable front end CSS','sendpress'); ?><br>
			
			<input class="turnoff-ajax-checkbox sendpress_checkbox" value="<?php echo $widget_options['load_ajax']; ?>" type="checkbox" <?php if( $widget_options['load_ajax'] == 1 ){ echo 'checked'; } ?> id="load_ajax" name="load_ajax"/>  <?php  _e('Disable signup form ajax','sendpress'); ?><br>

			
			<input class="footer-scripts-checkbox sendpress_checkbox" value="<?php echo $widget_options['load_scripts_in_footer']; ?>" type="checkbox" <?php if( $widget_options['load_scripts_in_footer'] == 1 ){ echo 'checked'; } ?> id="load_scripts_in_footer" name="load_scripts_in_footer"/>  <?php _e('Load Javascript in Footer','sendpress'); ?> 
		
		</div>
	</div>
	<div class="panel panel-default">
	  <div class="panel-heading">
			<h3 class="panel-title"><?php _e('Permalink Settings', 'sendpress'); ?></h3>
	  </div>
		<div class="panel-body">
		<?php $ctype = SendPress_Option::get('old_permalink'); ?>
		<input type="checkbox" name="old_permalink" value="true" <?php if($ctype){echo "checked='checked'"; } ?> /> <?php _e('Use old permalink with ?sendpress=.'); ?>
		
		

		</div>
	</div>


	<h2><?php _e('Table Info', 'sendpress'); ?></h2>
		<pre><?php echo SendPress_DB_Tables::check_setup_support(); ?></pre>
		<a class="btn btn-danger" href="<? echo SendPress_Admin::link('Settings_Install'); ?>"><?php _e('Install Missing Tables', 'sendpress'); ?></a>
		<a class="btn btn-warning" href="<? echo SendPress_Admin::link('Settings_Install',array('action'=>'events-repair')); ?>"><?php _e('Repair Events Tables', 'sendpress'); ?></a>

	</div>	
	<div class="sp-50">
		<div class="panel panel-default">
	  <div class="panel-heading">
			<h3 class="panel-title"><?php _e('Tracking', 'sendpress'); ?></h3>
	  </div>
		<div class="panel-body">
		<?php $ctype = SendPress_Option::get('allow_tracking'); ?>
	<input type="checkbox" name="allow_tracking" value="yes" <?php if($ctype=='yes'){echo "checked='checked'"; } ?> /> <?php _e('Allow tracking of this WordPress installs anonymous data.', 'sendpress'); ?>
		<p>
            <?php _e('To maintain a plugin as big as SendPress, we need to know what we\'re dealing: what kinds of other plugins our users are using, what themes, etc. Please allow us to track that data from your install. It will not track any user details, so your security and privacy are safe with us.', 'sendpress'); ?></p>
</div></div>
	<div class="panel panel-default">
	  <div class="panel-heading">
			<h3 class="panel-title"><?php _e('System Limits', 'sendpress'); ?></h3>
	  </div>
		<div class="panel-body">
			<p><?php _e('Please take care when changing these settings. We have attempted to set these to work on almost all servers. If you have a faster server you may be able to increase the limits below or if you are having troubles you may need to decrease the settings.', 'sendpress'); ?></p>
			<hr>
        <?php _e('Users to Sync per ajax call:', 'sendpress'); ?> <?php $this->select('sync-per-call',SendPress_Option::get('sync-per-call',250) ); ?> <?php _e('Default:', 'sendpress'); ?> 250
			<hr>
        <?php _e('Emails sent per AutoCron execution:', 'sendpress'); ?> <?php $this->select('autocron-per-call',SendPress_Option::get('autocron-per-call',25), array(15,25,30,35,40,45,50,55,60,65,70,75,80,85,90,95,100) ); ?> <?php _e('Default:', 'sendpress'); ?> 25<br>
			<small><?php _e('If AutoCron errors it will shut itself off.', 'sendpress'); ?></small>
			<hr>
        <?php _e('WordPress cron emails sent per execution:','sendpress');?> <?php $this->select('wpcron-per-call',SendPress_Option::get('wpcron-per-call',25), array(15,25,30,35,40,45,50,100,250,500,1000) ); ?> <?php _e('Default:', 'sendpress'); ?> 25<br>
			<hr>
        <?php _e('Queue History:', 'sendpress'); ?>  <?php $this->select('queue-history',SendPress_Option::get('queue-history',7), array(7,14,21,28,35,42,49) ); ?> Days <br><small><?php _e('Default:', 'sendpress'); ?> 7 Days<small>
			
		</div>
	</div>
	<?php do_action('sendpress_advanced_settings'); ?>
		
	


	
	
	</div>

</div>
<?php wp_nonce_field($sp->_nonce_value); ?>
</form>
<?php
	}

}