<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 

register_nav_menus( array(
	'wpforo-menu' => esc_html__( 'wpForo Menu', 'wpforo' ),
) );


function wpforo_login_url(){
	if(isset(WPF()->member->options['login_url']) && WPF()->member->options['login_url']){
		$wp_login_url = trim(get_bloginfo('url') , '/') . '/' . ltrim(WPF()->member->options['login_url'] , '/');
	}else{
		$request_uri = preg_replace( '#/?\?.*$#isu', '', wpforo_get_request_uri() );
		$wp_login_url = (!is_wpforo_page() ? wpforo_home_url('?wpforo=signin') : wpforo_home_url( $request_uri . '?wpforo=signin' ) );
	}

	return esc_url($wp_login_url);
}


function wpforo_register_url(){
	if(isset(WPF()->member->options['register_url']) && WPF()->member->options['register_url']){
		$wp_register_url = trim(get_bloginfo('url') , '/') . '/' . ltrim(WPF()->member->options['register_url'] , '/');
	}
	else{
		$wp_register_url = wpforo_home_url('?wpforo=signup');
	}
	return esc_url($wp_register_url);
}


function wpforo_lostpass_url(){
	if(isset(WPF()->member->options['lost_password_url']) && WPF()->member->options['lost_password_url']){
		$wp_lostpass_url = trim(get_bloginfo('url') , '/') . '/' . ltrim(WPF()->member->options['lost_password_url'] , '/');
	}
	else{
		$wp_lostpass_url = wp_lostpassword_url( wpforo_get_request_uri() );
	}
	return esc_url($wp_lostpass_url);
}


function wpforo_menu_filter( $items, $menu ) {
	if ( !wpforo_is_admin() ) {
		foreach ( $items as $key => $item ) {
			if(isset($item->url)){
				if( strpos($item->url, '%wpforo-') !== FALSE ){
					$shortcode = trim(str_replace(array('https://', 'http://', '/', '%'), '', $item->url));
					if(isset(WPF()->menu) && isset(WPF()->menu[$shortcode])){
						if(isset(WPF()->menu[$shortcode]['href'])) $item->url = WPF()->menu[$shortcode]['href'];
						if(isset(WPF()->menu[$shortcode]['attr']) && strpos(WPF()->menu[$shortcode]['attr'], 'wpforo-active') !== FALSE ) $item->classes[] = 'wpforo-active';
					}
					else{
						unset($items[$key]);
					}	
				}
			}
		}
	}
    return $items;
}
add_filter( 'wp_get_nav_menu_items', 'wpforo_menu_filter', 1, 2 );

function wpforo_menu_nofollow_items($item_output, $item, $depth, $args) {
	//if( isset($item->url) && strpos($item->url, '?wpforo') !== FALSE ) {
		//$item_output = str_replace('<a ', '<a rel="nofollow" ', $item_output);
	//}
	return $item_output;
}
add_filter('walker_nav_menu_start_el', 'wpforo_menu_nofollow_items', 1, 4);

function wpforo_profile_plugin_menu( $userid = 0 ){
	
	$menu_html = '<div class="wpf-profile-plugin-menu">';
	
    $forum_profile = false;
	if($url = wpforo_has_shop_plugin($userid)){
        $forum_profile = true;
		$menu_html .= '<div id="wpf-pp-shop-menu" class="wpf-pp-menu">
                <a class="wpf-pp-menu-item" href="' . esc_url($url) . '">
                    <i class="fa fa-shopping-cart" title="'.wpforo_phrase('Shop Account', false).'"></i> <span>'.wpforo_phrase('Shop Account', false).'</span>
                </a>
			</div>';
	}
	if($url = wpforo_has_profile_plugin($userid)){
        $forum_profile = true;
        $menu_html .= '<div id="wpf-pp-site-menu" class="wpf-pp-menu">
            <a class="wpf-pp-menu-item" href="' . esc_url($url) . '">
                <i class="fa fa-user" title="'.wpforo_phrase('Site Profile', false).'"></i> <span>'.wpforo_phrase('Site Profile', false).'</span>
            </a>
        </div>';
	}
	if( $forum_profile ) {
        $menu_html .= '<div id="wpf-pp-forum-menu" class="wpf-pp-menu">
            <div class="wpf-pp-menu-item">
                <i class="fa fa-comments" title="' . wpforo_phrase('Forum Profile', false) . '"></i> <span>' . wpforo_phrase('Forum Profile', false) . '</span>
            </div>
        </div>';
        $menu_html .= "\r\n<div class=\"wpf-clear\"></div>\r\n</div>";
        $menu_html = apply_filters( 'wpforo_profile_plugin_menu_filter', $menu_html, $userid );
        echo $menu_html; //This is a HTML content//
    }
}
add_action( 'wpforo_profile_plugin_menu_action', 'wpforo_profile_plugin_menu', 1 );

class wpforo_menu_walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"sub-menu\">\n";
	}
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		$args = apply_filters( 'wpforo_nav_menu_item_args', $args, $item, $depth );
		$class_names = join( ' ', apply_filters( 'wpforo_nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		$id = apply_filters( 'wpforo_nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		$output .= $indent . '<li' . $id . $class_names .'>';
		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';
		$atts = apply_filters( 'wpforo_nav_menu_link_attributes', $atts, $item, $args, $depth );
		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}
		$title = apply_filters( 'wpforo_the_title', $item->title, $item->ID );
		$title = apply_filters( 'wpforo_nav_menu_item_title', $title, $item, $args, $depth );
		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;
		$output .= apply_filters( 'wpforo_walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= "</li>";
	}
}

function wpforo_widgets_init() {
	register_sidebar(array(
		'name' => __('wpForo Sidebar', 'wpforo'),
		'description' => __("NOTE: If you're going to add widgets in this sidebar, please use 'Full Width' template for wpForo index page to avoid sidebar duplication.", 'wpforo'),
		'id' => 'forum-sidebar',
		'before_widget' => '<aside id="%1$s" class="footer-widget-col %2$s clearfix">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	));
}
add_action('widgets_init', 'wpforo_widgets_init', 11);

class wpForo_Widget_search extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_search', // Base ID
			'wpForo Search',        // Name
			array( 'description' => 'wpForo search form' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		echo $args['before_widget']; //This is a HTML content//
		echo '<div id="wpf-widget-search" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title']; //This is a HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		?>
        <form action="<?php echo wpforo_home_url() ?>" method="get">
        	<?php wpforo_make_hidden_fields_from_url( wpforo_home_url() ) ?>
            <input type="text" placeholder="<?php wpforo_phrase('Search...') ?>" name="wpfs" class="wpfw-70" value="<?php echo isset($_GET['wpfs']) ? esc_attr(sanitize_text_field($_GET['wpfs'])) : '' ?>" ><input type="submit" class="wpfw-20" value="&raquo;">
        </form>
		<?php
		echo '</div></div>';
		echo $args['after_widget']; //This is a HTML content//
	}
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Forum Search';
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // widget wpforo search

class wpForo_Widget_login_form extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_login_form', // Base ID
			'wpForo Login Form',        // Name
			array( 'description' => 'wpForo login form' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		echo $args['before_widget']; //This is a HTML content//
		echo '<div id="wpf-widget-login" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title']; //This is a HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		?>
		<?php if( is_user_logged_in() && !empty(WPF()->current_user) ) : ?>
			<?php extract(WPF()->current_object, EXTR_OVERWRITE); extract(WPF()->current_user, EXTR_OVERWRITE); ?>
			<div class="wpforo-profile-wrap">
			<div class="wpforo-profile-head">
			<div class="h-header">
	      	<?php if( WPF()->perm->usergroup_can('va') && wpforo_feature('avatars') ): $rsz =''; ?>
	        	<div class="h-left"><?php echo WPF()->member->get_avatar($userid, 'alt="'.esc_attr($display_name).'"', 150); ?></div>
	        <?php else: $rsz = ' style="margin-left:10px;"'; endif; ?>
	        <div class="h-right" <?php echo $rsz; ?>>
	             <div class="h-top">
	                <div class="profile-display-name">
	                	<?php WPF()->member->show_online_indicator($userid) ?>
	                    <?php echo $display_name ? esc_html($display_name) : esc_html(urldecode($user_nicename)) ?>
	                </div>
	                <div class="profile-stat-data">
	                    <div class="profile-stat-data-item"><?php wpforo_phrase('Group') ?>: <?php wpforo_phrase($groupname) ?></div>
	                    <div class="profile-stat-data-item"><?php wpforo_phrase('Joined') ?>: <?php esc_html(wpforo_date($user_registered, 'Y/m/d')) ?></div>
	                </div>
	            </div>
	        </div>
	      <div class="wpf-clear"></div>
	      </div>
	      <div class="h-footer wpfbg-2">
	      
	        <div class="h-bottom">
	            <?php WPF()->tpl->member_menu($userid) ?>
	            <a href="?wpforo=logout"><?php wpforo_phrase('logout') ?></a>
	            <div class="wpf-clear"></div>
	        </div>
	      </div>
	    </div>
	      </div>
	      
		<?php else : ?>
		
	        <form name="wpflogin" action="" method="POST">
			  <div class="wpforo-login-wrap">
			    <div class="wpforo-login-content">
			     <table class="wpforo-login-table wpfcl-1" width="100%" border="0" cellspacing="0" cellpadding="0">
			          <tr class="wpfbg-9">
			            <td class="wpf-login-label">
			            	<p class="wpf-label wpfcl-1"><?php wpforo_phrase('Username') ?>:</p>
			            </td>
			            <td class="wpf-login-field"><input autofocus required="TRUE" type="text" name="log" class="wpf-login-text wpfw-60" /></td>
			          </tr>
			          <tr class="wpfbg-9">
			            <td class="wpf-login-label">
			            	<p class="wpf-label wpfcl-1"><?php wpforo_phrase('Password') ?>:</p>
			            </td>
			            <td class="wpf-login-field"><input required="TRUE" type="password" name="pwd" class="wpf-login-text wpfw-60" /></td>
			          </tr>
			          <tr class="wpfbg-9"><td colspan="2" style="text-align: center;"><?php do_action('login_form') ?></td></tr>
			          <tr class="wpfbg-9">
			            <td class="wpf-login-label">&nbsp;</td>
			            <td class="wpf-login-field">
			            <p class="wpf-extra wpfcl-1">
			            <input type="checkbox" value="1" name="rememberme" id="wpf-login-remember"> 
			            <label for="wpf-login-remember"><?php wpforo_phrase('Remember Me') ?> |</label>
			            <a href="<?php echo esc_url(wp_lostpassword_url(wpforo_get_request_uri())); ?>" class="wpf-forgot-pass"><?php wpforo_phrase('Lost your password?') ?></a> 
			            <a href="<?php echo esc_url( wpforo_home_url('?wpforo=register') ) ?>"><?php wpforo_phrase('register') ?></a>
			            </p>
			            <input type="submit" name="wpforologin" value="<?php wpforo_phrase('Sign In') ?>" />
			            </td>
			          </tr>
			       </table>
			  	</div>
			  </div>
			</form>
			
		<?php endif ?>
		<?php
		echo '</div></div>';
		echo $args['after_widget'];
	}
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Account';
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // widget wpforo login


class wpForo_Widget_online_members extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_online_members', // Base ID
			'wpForo Online Members',        // Name
			array( 'description' => 'Online members.' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		echo $args['before_widget']; //This is a HTML content//
		echo '<div id="wpf-widget-online-users" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		// widget content from front end
		$online_members = WPF()->member->get_online_members($instance['count']);
		echo '<div class="wpforo-widget-content">';
		if(!empty($online_members)){
			echo '<ul>
					 <li>
						<div class="wpforo-list-item">';
			foreach( $online_members as $member ){
				if( $instance['display_avatar'] ): ?>
						<a href="<?php echo esc_url(WPF()->member->get_profile_url( $member['ID'] )) ?>" class="onlineavatar">
							<?php echo WPF()->member->get_avatar( $member['ID'], 'style="width:95%;" class="avatar" title="'.esc_attr($member['display_name']).'"'); ?>
						</a>
					<?php else: ?>
						<a href="<?php echo esc_url(WPF()->member->get_profile_url( $member['ID'] )) ?>" class="onlineuser"><?php echo esc_html($member['display_name']) ?></a>
					<?php endif; ?>
				<?php
			}
			echo '<div class="wpf-clear"></div>
							</div>
						</li>
					</ul>
				</div>';
		}
		else{
			echo '<p class="wpf-widget-note">&nbsp;'.wpforo_phrase('No online members at the moment', false).'</p>';
		}
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Online Members';
		$count = ! empty( $instance['count'] ) ? $instance['count'] : '15';
		$display_avatar = isset( $instance['display_avatar'] ) ? (bool) $instance['display_avatar'] : false;
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p><p>
			<label><?php _e('Number of Items', 'wpforo'); ?></label>&nbsp;
			<input type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>" value="<?php echo esc_attr( $count ) ; ?>">
		</p><p>
			<label>
            	<input<?php checked( $display_avatar ); ?> type="checkbox" value="1" name="<?php echo esc_attr( $this->get_field_name( 'display_avatar' )); ?>"/>
			 	<?php _e('Display Avatars', 'wpforo'); ?>
            </label>
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : '';
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : false;
		return $instance;
	}
} // widget online members

class wpForo_Widget_recent_topics extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_recent_topics', // Base ID
			'wpForo Recent Topics',        // Name
			array( 'description' => 'Your forum\'s recent topics.' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		echo $args['before_widget'];//This is a HTML content//
		echo '<div id="wpf-widget-recent-replies" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];//This is a HTML content//
		}
		// widget content from front end
		$private = (!is_user_logged_in() || !WPF()->perm->usergroup_can('em')) ? 0 : NULL;
		$status = (!is_user_logged_in() || !WPF()->perm->usergroup_can('em')) ? 0 : NULL;
		$topic_args = array(  	// forumid, order, parentid
		  'orderby'		=> 'created',
		  'order'		=> 'DESC', 		// ASC DESC
		  'row_count'	=> $instance['count'], 		// 4 or 1 ...
		  'private'		=> $private,
		  'status'		=> $status
		);
		$topics = WPF()->topic->get_topics_filtered($topic_args);
		$ug_can_va = WPF()->perm->usergroup_can('va');
		$is_avatar = wpforo_feature('avatars');
		echo '<div class="wpforo-widget-content"><ul>';
		foreach( $topics as $topic ){
			$topic_url = wpforo_topic($topic['topicid'], 'url');
			$member = wpforo_member($topic);
			?>
            <li>
                <div class="wpforo-list-item">
                    <?php if( $instance['display_avatar'] ): ?>
                    	<?php if( $ug_can_va && $is_avatar ): ?>
                            <div class="wpforo-list-item-left">
                                <?php echo WPF()->member->get_avatar( $topic['userid']); ?>
                            </div>
                    	<?php endif; ?>
					<?php endif; ?>
                    <div class="wpforo-list-item-right" <?php if( !$instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                        <p class="posttitle"><a href="<?php echo esc_url($topic_url) ?>"><?php echo esc_html($topic['title']) ?></a></p>
                        <p class="postuser"><?php wpforo_phrase('by') ?> <?php wpforo_member_link($member) ?>, <span style="white-space:nowrap;"><?php esc_html(wpforo_date($topic['created'])) ?></span></p>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
            </li>
            <?php
		}
		echo '</ul></div>';
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Recent Topics';
		$count = ! empty( $instance['count'] ) ? $instance['count'] : '9';
		$display_avatar = isset( $instance['display_avatar'] ) ? (bool) $instance['display_avatar'] : false;
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p><p>
			<label><?php _e('Number of Items', 'wpforo'); ?></label>&nbsp;
			<input type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>"   value="<?php echo esc_attr($count) ; ?>">
		</p><p>
			<label><input <?php checked( $display_avatar ); ?> type="checkbox"  name="<?php echo esc_attr($this->get_field_name( 'display_avatar' )); ?>" >
			<?php _e('Display with Avatars', 'wpforo'); ?></label>
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : '';
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : false;
		return $instance;
	}
} // Recent topics


class wpForo_Widget_recent_replies extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_recent_replies', // Base ID
			'wpForo Recent Posts',        // Name
			array( 'description' => 'Your forum\'s recent posts.' ) // Args
		);
	}
	
	public function widget( $args, $instance ) {
		echo $args['before_widget'];//This is a HTML content//
		echo '<div id="wpf-widget-recent-replies" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];//This is a HTML content//
		}
		$private = (!is_user_logged_in() || !WPF()->perm->usergroup_can('em')) ? 0 : NULL;
		$status = (!is_user_logged_in() || !WPF()->perm->usergroup_can('em')) ? 0 : NULL;
		// widget content from front end
		$posts_args = array( 
		  'orderby'		=> 'created',
		  'order'		=> 'DESC',
		  'row_count'	=> $instance['count'],
		  'private'		=> $private,
		  'status'		=> $status
		);
		$recent_posts = WPF()->post->get_posts_filtered($posts_args);
		$ug_can_va = WPF()->perm->usergroup_can('va');
		$is_avatar = wpforo_feature('avatars');
		echo '<div class="wpforo-widget-content"><ul>';
		foreach( $recent_posts as $post ){
			$post_url = wpforo_post( $post['postid'], 'url' );
			$member = wpforo_member( $post );
			?>
            <li>
                <div class="wpforo-list-item">
                    <?php if( $instance['display_avatar'] ): ?>
                    	<?php if( $ug_can_va && $is_avatar ): ?>
                            <div class="wpforo-list-item-left">
                                <?php echo WPF()->member->get_avatar( $post['userid']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="wpforo-list-item-right" <?php if( !$instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                        <p class="posttitle"><a href="<?php echo esc_url($post_url) ?>"><?php echo esc_html($post['title']) ?></a></p>
                        <p class="posttext"><?php echo esc_html(wpforo_text($post['body'], 55)); ?></p>
                        <p class="postuser"><?php wpforo_phrase('by') ?> <?php wpforo_member_link($member) ?>, <?php esc_html(wpforo_date($post['created'])) ?></p>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
            </li>
            <?php
		}
		echo '</ul></div>';
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Recent Posts';
		$count = ! empty( $instance['count'] ) ? $instance['count'] : '9';
		$display_avatar = isset( $instance['display_avatar'] ) ? (bool) $instance['display_avatar'] : false;
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p><p>
			<label><?php _e('Number of Items', 'wpforo'); ?></label>&nbsp;
			<input type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>"   value="<?php echo esc_attr($count) ; ?>">
		</p><p>
			<label><input <?php checked( $display_avatar ); ?> type="checkbox"  name="<?php echo esc_attr($this->get_field_name( 'display_avatar' )); ?>" >
			<?php _e('Display with Avatars', 'wpforo'); ?></label>
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : '';
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : false;
		return $instance;
	}
} // Recent replies


class wpforo_widget_forums extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpforo_widget_forums', // Base ID
			'wpForo Forums',        // Name
			array( 'description' => 'Forum tree.' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		echo $args['before_widget'];//This is a HTML content//
		echo '<div id="wpf-widget-forums" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];//This is a HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		WPF()->forum->tree('front_list');
		echo '</div>';
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Forums';
		$display_avatar = isset( $instance['display_avatar'] ) ? (bool) $instance['display_avatar'] : false;
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // forums tree


function wpforo_widget_search() {
    register_widget( 'wpForo_Widget_search' );
}
add_action( 'widgets_init', 'wpforo_widget_search' );

function wpforo_widget_login() {
	//Under development....
    //register_widget( 'wpForo_Widget_login_form' );
}
add_action( 'widgets_init', 'wpforo_widget_login' );

function wpforo_widget_online_members() {
    register_widget( 'wpForo_Widget_online_members' );
}
add_action( 'widgets_init', 'wpforo_widget_online_members' );

function wpforo_widget_recent_topics() {
    register_widget( 'wpForo_Widget_recent_topics' );
}
add_action( 'widgets_init', 'wpforo_widget_recent_topics' );

function wpforo_widget_recent_replies() {
    register_widget( 'wpForo_Widget_recent_replies' );
}
add_action( 'widgets_init', 'wpforo_widget_recent_replies' );

function wpforo_widget_forums() {
	//Under Development
    //register_widget( 'wpforo_widget_forums' );
}
add_action( 'widgets_init', 'wpforo_widget_forums' );

function wpforo_post_edited($post, $echo = true){
	$edit_html = '';
	if(!empty($post)){
		$created = wpforo_date($post['created'], 'd/m/Y g:i a', false);
		$modified = wpforo_date($post['modified'], 'd/m/Y g:i a', false);
		if( isset($modified) && $created != $modified ){
			$edit_html = '<div class="wpf-post-edited">' . wpforo_phrase('Edited: ', false) . wpforo_date($post['modified'], 'ago', false) . '</div>';
		}
	}
	if( $echo ) { 
		echo $edit_html;
	}
	else{ 
		return $edit_html;
	}
}

function wpforo_hide_title($title, $id = 0) {
	if( !wpforo_feature('page-title') ){
		if( $wpforo_base_slug = basename( wpforo_home_url() ) ) $wpforo_page = get_page_by_path($wpforo_base_slug);
		if(!empty($wpforo_page)){
			if (in_the_loop() && is_page($wpforo_page->ID) && $id == get_the_ID()) {
				$title = '';
			}
		}
	}
	return $title;
}
add_filter('the_title', 'wpforo_hide_title', 10, 2);


function wpforo_validate_gravatar( $email ) {
	$hashkey = md5(strtolower(trim($email)));
	$uri = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';
	$data = wp_cache_get($hashkey);
	if (false === $data) {
		$response = wp_remote_head($uri);
		if( is_wp_error($response) ) {
			$data = 'not200';
		} else {
			$data = $response['response']['code'];
		}
	    wp_cache_set($hashkey, $data, $group = '', $expire = 60*5);
	}		
	if ($data == '200'){
		return true;
	} else {
		return false;
	}
}

function wpforo_member_title( $member = array(), $echo = true, $before = '', $after = '' ){
	$title = array();
	
	if(empty($member) || !$member['groupid']) return '';
	$rating_title_ug_enabled = ( isset(WPF()->member->options['rating_title_ug'][$member['groupid']]) && WPF()->member->options['rating_title_ug'][$member['groupid']] ) ? true : false ;
	$usergroup_title_ug_enabled = ( isset(WPF()->member->options['title_usergroup'][$member['groupid']]) && WPF()->member->options['title_usergroup'][$member['groupid']] ) ? true : false ;
	
	if( wpforo_feature('rating_title') && $rating_title_ug_enabled && isset($member['stat']['title']) ){
		$title[] = '<span class="wpf-member-title wpfrt" title="' . wpforo_phrase('Rating Title', false) . '">' . esc_html($member['stat']['title']) . '</span>';
	}  
	if( empty($title) && WPF()->member->options['custom_title_is_on'] ){
        $title[] = '<span class="wpf-member-title wpfct" title="' . wpforo_phrase('User Title', false) . '">' . wpforo_phrase($member['title'], false) . '</span>';
	}else{
	    $before = $after = '';
    }
	if( $usergroup_title_ug_enabled  ){
		$class = '';
		if( $member['groupid'] == 1 ) $class = ' wpfbg-6 wpfcl-3';
		if( $member['groupid'] == 2 ) $class = ' wpfbg-5 wpfcl-3';
		if( $member['groupid'] == 4 ) $class = ' wpfbg-2 wpfcl-3';
		$title[] = '<span class="wpf-member-title wpfut wpfug-' . intval($member['groupid']) . $class . '" title="' . wpforo_phrase('Usergroup', false) . '">' . esc_html($member['groupname']) . '</span>';
	}
	if( !empty($title) ){
		$title_html = $before . implode(' ', $title) . $after;
		$title_html = apply_filters('wpforo_member_title', $title_html, $member);
		if( $echo ) { 
			echo $title_html;
		}
		else{ 
			return $title_html;
		}
	}
}

function wpforo_member_badge( $member = array(), $sep = '', $type = 'full' ){
	$rating_badge_ug_enabled = ( isset(WPF()->member->options['rating_badge_ug'][$member['groupid']]) && WPF()->member->options['rating_badge_ug'][$member['groupid']] ) ? true : false ;
	if( wpforo_feature('rating') && $rating_badge_ug_enabled && isset($member['stat']['rating']) ): ?>
        <div class="author-rating-<?php echo esc_attr($type) ?>" style="color:<?php echo esc_attr($member['stat']['color']) ?>" title="<?php wpforo_phrase('Member Rating Badge') ?>">
            <?php echo WPF()->member->rating_badge($member['stat']['rating'], $type); ?>
        </div><?php if($sep): ?><span class="author-rating-sep"><?php echo esc_html($sep); ?></span><?php endif; ?>
    <?php endif;
    
    do_action('wpforo_after_member_badge', $member);
}


function wpforo_member_nicename( $member = array(), $prefix = '', $bracket = true, $wrap = true, $class = 'wpf-author-nicename', $echo = true ){
	if( empty($member) || !isset($member['user_nicename']) ) return '';
	$nicename = '';
	if( $wrap ){ $nicename .= '<div class="' . $class . '" title="' . wpforo_phrase('You can mention a person using @nicename in post content to send that person an email message. When you post a topic or reply, forum sends an email message to the user letting them know that they have been mentioned on the post.', false) . '">';}
	if( $bracket ) $nicename .= '(';
	$nicename .= $prefix . urldecode($member['user_nicename']);
	if( $bracket ) $nicename .= ')';
	if( $wrap ){ $nicename .= '</div>';}
	if( $echo ){ echo $nicename; } else{ return $nicename; }
}


add_filter( 'body_class', 'wpforo_page_class', 1, 10 );
function wpforo_page_class( $classes ) {
	if(!empty($classes)){
    	if( function_exists('is_wpforo_page') ){
			if ( is_wpforo_page() ) {
				return array_merge( $classes, array( 'wpforo' ) );
			}
		}
	}
	return (array)$classes;
}

###############################################################################
########################## THEME API FUNCTIONS ################################
###############################################################################

function wpforo_post( $postid, $var = 'item', $echo = false ){
	$post = ( $var == 'item' ) ? array() : '';
	if( !$postid ) return $post;
	$cache = WPF()->cache->on('object_cashe');
	if( $cache ){
		 $post = WPF()->cache->get_item( $postid, 'post' );
	}
	if( empty($post) ){
		if( !$cache && $var == 'url' ){
			$post['url'] = WPF()->post->get_post_url($postid);
		}
		elseif( !$cache && $var == 'is_answered' ){
			$post['is_answered'] = WPF()->post->is_answered($postid);
		}
		elseif( !$cache && $var == 'votes_sum' ){
            $post = WPF()->post->get_post($postid);
            $post['votes_sum'] = $post['votes'];
		}
		elseif( !$cache && $var == 'likes_count' ){
			$post['likes_count'] = WPF()->post->get_post_likes_count($postid);
		}
		elseif( !$cache && $var == 'likers_usernames' ){
			$post['likers_usernames'] = WPF()->post->get_likers_usernames($postid);
		}
		else{
			$post = WPF()->post->get_post($postid);
			if( !empty($post) ){
				$post['url'] = WPF()->post->get_post_url($post);
				if( $cache ){
					$post['is_answered'] = WPF()->post->is_answered($postid);
					$post['votes_sum'] = $post['votes'];
					$post['likes_count'] = WPF()->post->get_post_likes_count($postid);
					$post['likers_usernames'] = WPF()->post->get_likers_usernames($postid);
				}
				if(!empty($post)){ 
					$cache_item = array( $postid => $post );
					WPF()->cache->create('item', $cache_item, 'post');
				}
			}
		}
	}
	
	if( $var != 'item' && $var ){
		$post = ( isset($post[$var]) ) ? $post[$var] : '';
	}
	
	if( $echo ){
		echo $post;
	}
	else{
		return $post;
	}
}

function wpforo_topic( $topicid, $var = 'item', $echo = false ){
	$topic = ( $var == 'item' ) ? array() : '';
	if( !$topicid ) return $topic;
	$cache = WPF()->cache->on('object_cashe');
	if( $cache ) $topic = WPF()->cache->get_item( $topicid, 'topic' );
	
	if( empty($topic) ){
		if( !$cache && $var == 'url' ){
			$topic['url'] = WPF()->topic->get_topic_url( $topicid );
		}
		elseif( !$cache && $var == 'is_answer' ){
			$topic['is_answer'] = WPF()->topic->is_solved( $topicid );
		}
		else{
			$topic = WPF()->topic->get_topic($topicid);
			if( !empty($topic) ){
				$topic['url'] = WPF()->topic->get_topic_url($topic);
				$topic['is_answer'] = WPF()->topic->is_solved( $topic['topicid'] );
				if(!empty($topic)){ 
					$cache_item = array( $topicid => $topic );
					WPF()->cache->create('item', $cache_item, 'topic');
				}
			}
		}
	}
	
	if( $var != 'item' && $var ){
		$topic = ( isset($topic[$var]) ) ? $topic[$var] : '';
	}
	
	if( $echo ){
		echo $topic;
	}
	else{
		return $topic;
	}
}


function wpforo_forum( $forumid, $var = 'item', $echo = false ){
	$data = array();
	$forum = ( $var == 'item' ) ? array() : '';
	$cache = WPF()->cache->on('object_cashe');
	if( !$forumid ) return $forum;
	if( $cache ) $forum = WPF()->cache->get_item( $forumid, 'forum' );
	
	if( empty($forum) ){
		if( !$cache && ($var == 'childs' || $var == 'counts') ){
			if( $var == 'childs' ) { 
				WPF()->forum->get_childs($forumid, $data);
				$forum['childs'] = $data;
			}
			else{ 
				WPF()->forum->get_childs($forumid, $data);
				$forum['childs'] = $data;
				$forum['counts'] = WPF()->forum->get_counts( $data );
			}
		}
		else{
			$forum = WPF()->forum->get_forum($forumid);
			if( !empty($forum) ){
				if( $cache ){
					WPF()->forum->get_childs($forum['forumid'], $data);
					$forum['childs'] = $data;
					$forum['counts'] = WPF()->forum->get_counts( $data );
				}
				if(!empty($forum)){ 
					$cache_item = array( $forumid => $forum );
					WPF()->cache->create('item', $cache_item, 'forum');
				}
			}
		}
	}
	
	if( $var != 'item' && $var ){
		$forum = ( isset($forum[$var]) ) ? $forum[$var] : '';
	}
	
	if( $echo ){
		echo $forum;
	}
	else{
		return $forum;
	}
}

function wpforo_member( $object, $var = 'item', $echo = false ){
	$member = array();
	if( empty( $object ) ) return $member;
	
	if( is_array( $object ) && isset($object['userid']) && $object['userid'] == 0 ){ 
		$member = WPF()->member->get_guest( $object );
	}
	else{
		$userid = ( is_array( $object ) && isset($object['userid']) ) ? intval($object['userid']) : intval($object);
		$member = WPF()->member->get_member( $userid );
	}
	
	if( $var != 'item' && $var ){
		$member = ( isset($member[$var]) ) ? $member[$var] : '';
	}
	
	if( $echo ){
		echo $member;
	}
	else{
		return $member;
	}
}

function wpforo_member_link( $member, $prefix = '', $length = 30, $class = '', $echo = true ){
	$display_name = ( isset($member['display_name']) && $member['display_name'] ) ? $member['display_name'] : wpforo_phrase('Anonymous', false);
	$nofollow = ( !wpforo_feature('seo-profile')) ? ' rel="nofollow" ' : '';
	$color = (isset($member['color']) && $member['color'] ) ? 'style="color:' . $member['color'] . '"' : '';
	$class = ($class) ? 'class="' . $class . '"' : '';
	if( isset($member['profile_url']) && $member['profile_url'] ){
		?><a href="<?php echo esc_url($member['profile_url']) ?>" <?php echo $nofollow ?> <?php echo $color ?> <?php echo $class ?>><?php if( strpos($prefix, '%s') !== FALSE ): ?><?php echo sprintf( wpforo_phrase($prefix, FALSE), esc_html(wpforo_text($display_name, $length, FALSE)) ); ?><?php else: ?><?php if( $prefix ){ echo wpforo_phrase( $prefix, false) . ' '; } ?><?php if( $length ){ echo esc_html(wpforo_text($display_name, $length, false)); } else { echo esc_html($display_name); } ?><?php endif; ?></a><?php
	}
	else{
		?><?php if( strpos($prefix, '%s') !== FALSE ): ?><?php echo sprintf( wpforo_phrase($prefix, FALSE), esc_html(wpforo_text($display_name, $length, FALSE)) ); ?><?php else: ?><?php if( $prefix ){ echo wpforo_phrase( $prefix, false) . ' '; } ?><?php if( $length ){ echo esc_html(wpforo_text($display_name, $length, false)); } else { echo esc_html($display_name); } ?><?php endif; ?><?php
    }
}

add_shortcode('wpforo-lostpassword', 'wpforo_lostpassword');
function wpforo_lostpassword(){ ?>
    <p id="wpforo-title"><?php wpforo_phrase('Reset Password') ?></p>

    <form name="wpflogin" action="<?php echo wp_lostpassword_url(); ?>" method="POST">
        <div class="wpforo-login-wrap">
            <div class="wpforo-login-content">
                <table class="wpforo-login-table wpfcl-1" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%; display:table;">
                    <tbody style="width:100%;">
                    <tr class="wpfbg-9">
                        <td class="wpf-login-label">
                            <p class="wpf-label wpfcl-1">
                                <label for="userlogin"><?php wpforo_phrase('Email or Username') ?>:</label>
                            </p>
                        </td>
                        <td class="wpf-login-field">
                            <input id="userlogin" autofocus required type="text" name="user_login" class="wpf-login-text wpfw-60" />
                            <p><?php wpforo_phrase('Enter your email address and we\'ll send you a link you can use to pick a new password.') ?></p>
                        </td>
                    </tr>
                    <tr class="wpfbg-9">
                        <td class="wpf-login-label">&nbsp;</td>
                        <td class="wpf-login-field">
                            <input type="submit" name="submit" value="<?php wpforo_phrase('Reset Password') ?>" />
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <?php
}

add_shortcode('wpforo-resetpassword', 'wpforo_resetpassword');
function wpforo_resetpassword(){ ?>
    <p id="wpforo-title"><?php wpforo_phrase('Reset Password') ?></p>

    <form name="wpflogin" action="<?php echo site_url( 'wp-login.php?action=resetpass' ); ?>" method="POST" autocomplete="off">
        <input type="hidden" name="rp_key" value="<?php echo $_REQUEST['rp_key'] ?>">
        <input type="hidden" name="rp_login" value="<?php echo $_REQUEST['rp_login'] ?>">
        <div class="wpforo-login-wrap">
            <div class="wpforo-login-content">
                <table class="wpforo-login-table wpfcl-1" width="100%" border="0" cellspacing="0" cellpadding="0" style="width:100%; display:table;">
                    <tbody style="width:100%;">
                    <tr class="wpfbg-9">
                        <td class="wpf-login-label">
                            <p class="wpf-label wpfcl-1">
                                <label for="pass1"><?php wpforo_phrase('New password') ?></label>
                            </p>
                        </td>
                        <td class="wpf-login-field">
                            <input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" required autofocus />
                        </td>
                    </tr>
                    <tr class="wpfbg-9">
                        <td class="wpf-login-label">
                            <p class="wpf-label wpfcl-1">
                                <label for="pass2"><?php wpforo_phrase('Repeat new password') ?></label>
                            </p>
                        </td>
                        <td class="wpf-login-field">
                            <input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" required />
                        </td>
                    </tr>
                    <tr class="wpfbg-9">
                        <td colspan="2">
                            <p class="description" style="text-align: center;"><?php echo wp_get_password_hint(); ?></p>
                        </td>
                    </tr>
                    <tr class="wpfbg-9">
                        <td class="wpf-login-label">&nbsp;</td>
                        <td class="wpf-login-field">
                            <input type="submit" name="submit" value="<?php wpforo_phrase('Reset Password'); ?>" />
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <?php
}

#############################################################################################
/**
 * Generates according page form fields using tpl->form_fields() function
 *
 * @since 1.4.0
 *
 * @param	array		$fields arguments
 * @param	boolean		$echo
 *
 * @return	string		form fields HTML
 */
function wpforo_fields( $fields, $echo = true ){
    if( empty($fields) ) return '';
	$fields = apply_filters( 'wpforo_form_fields', $fields );
	$html = WPF()->tpl->form_fields( $fields );
	if( $echo ){
		echo $html;
	}
	else{
		return $html;
	}
}

##################################################################################################
/**
 * Collects Registration Page POST data and sends to field generator function
 *
 * @since 	1.4.0
 *
 * @param	array		$fields arguments
 *
 * @return	NULL
 */

function wpforo_register_page_field_values( $fields ){
	WPF()->form['value']['user_login'] = (isset($_POST['wpfreg']['user_login'])) ? sanitize_user($_POST['wpfreg']['user_login']) : '';
	WPF()->form['value']['user_email'] = (isset($_POST['wpfreg']['user_email'])) ? sanitize_email($_POST['wpfreg']['user_email']) : '';
	WPF()->form['varname'] = 'wpfreg';
}
add_action( 'wpforo_register_page_start', 'wpforo_register_page_field_values', 10, 1 );


##################################################################################################
/**
 * Collects Account Page field data and sends to field generator function
 *
 * @since 	1.4.0
 *
 * @param	array		$fields arguments
 *
 * @return	NULL
 */

function wpforo_account_page_field_values( $fields ){
	if( isset(WPF()->current_object['user']) && !empty(WPF()->current_object['user']) ){
		$user = WPF()->current_object['user'];
		$user = apply_filters('wpforo_profile_header_obj', $user);
		WPF()->form['value'] = $user;
		WPF()->form['varname'] = 'member';
	}
}
add_action( 'wpforo_account_page_start', 'wpforo_account_page_field_values', 10, 1 );


##################################################################################################
/**
 * Collects Profile Page field data and sends to field generator function
 *
 * @since 	1.4.0
 *
 * @param	array		$fields arguments
 *
 * @return	NULL
 */

function wpforo_profile_page_field_values( $fields ){
	if( isset(WPF()->current_object['user']) && !empty(WPF()->current_object['user']) ){
		$user = WPF()->current_object['user'];
		WPF()->form['value'] = $user;
	}
}
add_action( 'wpforo_profile_page_start', 'wpforo_profile_page_field_values', 10, 1 );


function wpforo_search_page_field_values( $fields ){
    WPF()->form['value'] = ( !empty($_GET) ? (array) $_GET : array() );
    WPF()->form['varname'] = '';
}
add_action( 'wpforo_search_page_start', 'wpforo_search_page_field_values', 10, 1 );

function wpforo_user_avatar( $user, $size, $attr = '', $lastmod = false ){
	$avatar_html = '';
	if( is_int($user) && $user ){
		$avatar_html = ($size) ? get_avatar($user, $size) : get_avatar($user);
		if($attr) $avatar_html = str_replace('<img', '<img ' . $attr, $avatar_html);
	}
	elseif( is_array($user) && !empty($user) ){
		$avatar_html = WPF()->member->avatar($user, $attr, $size);
	}
	
	if( $lastmod ){
		$url = wpforo_avatar_url( $avatar_html );
		if($url){
			if( strpos($url, 'gravatar.com') === FALSE ){
				$avatar_html = str_replace($url, $url . '?lm=' . time(), $avatar_html);
			}
		}
	}
	return $avatar_html;
}

function wpforo_register_fields(){
    $fields = WPF()->member->get_register_fields();
    do_action( 'wpforo_register_page_start', $fields );

    return $fields;
}

function wpforo_account_fields(){
    $fields = WPF()->member->get_account_fields();
    do_action( 'wpforo_account_page_start', $fields );

    return $fields;
}

function wpforo_profile_fields(){
    $fields = WPF()->member->get_profile_fields();
    do_action( 'wpforo_profile_page_start', $fields );

    return $fields;
}

function wpforo_search_fields(){
    $fields = WPF()->member->get_search_fields();
    do_action( 'wpforo_search_page_start', $fields );

    if( WPF()->member->options['search_type'] == 'search' ){
        $fields = array(
            array(
                array(
                    array(
                        'type' => 'search',
                        'isDefault' => 1,
                        'isRemovable' => 0,
                        'isRequired' => 0,
                        'isEditable' => 1,
                        'class' => 'wpf-member-search-field',
                        'label' => wpforo_phrase('Find a member', false),
                        'title' => wpforo_phrase('Find a member', false),
                        'placeholder' => wpforo_phrase('Display Name or Nicename', false),
                        'faIcon' => 'fa-search',
                        'name' => 'wpfms',
                        'canBeInactive' => 0,
                        'can' => '',
                        'isSearchable' => 1
                    )
                )
            )
        );
    }

    return $fields;
}


function wpforo_unread_forum( $logid, $return = 'class', $echo = true ){
	$unread = false;
	if(!wpforo_feature('view-logging')) return;
	$viwed_ids = wpforo_getcookie( 'wpf_viewed_forums', true );
	if( empty($viwed_ids) ) $unread = true;
	if( is_array($viwed_ids) && !in_array( $logid, $viwed_ids ) ) $unread = true;
	if( $unread ){ if( $return == 'class' ){ $log = 'wpf_forum_unread'; } else{ $log = true; } if( $echo ){ echo $log; } else { return $log; } }
}

function wpforo_unread_topic( $logid, $return = 'class', $echo = true ){
	$unread = false;
	if(!wpforo_feature('view-logging')) return;
	$viwed_ids = wpforo_getcookie( 'wpf_viewed_topics', true );
	if( empty($viwed_ids) ) $unread = true;
	if( is_array($viwed_ids) && !in_array( $logid, $viwed_ids ) ) $unread = true;
	if( $unread ){ if( $return == 'class' ){ $log = 'wpf_topic_unread'; } else{ $log = true; } if( $echo ){ echo $log; } else { return $log; } }
}

if( !function_exists('custom_wpforo_get_account_fields') ){
    function custom_wpforo_get_account_fields($fields){
        $hide = array(
            'user_email',
            'user_nicename'
        );

        foreach ( $fields as $row_key => $row ){
            foreach ( $row as $col_key => $col ){
                foreach ( $col as $key => $field ){
                    if( in_array($field['fieldKey'], $hide) ){
                        unset($fields[$row_key][$col_key][$key]);
                    }
                }
            }
        }

        return $fields;
    }
//    add_filter('wpforo_get_account_fields', 'custom_wpforo_get_account_fields', 20);
}
