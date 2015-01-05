<?php
/*
Plugin Name: Tournament Seeker Golf Widget
Plugin URI: http://golf.tournamentseeker.com/api
Description: Display details about your favorite upcoming Golf events, straight from Golf.TournamentSeeker.com! 
Author: tournamentseeker
Author URI: http://golf.tournamentseeker.com
Version: 1.0.2
License: GPL-2	

*/
// Creating the widget

require_once 'TS_Golf_API.class.php';

class tournament_seeker_golf_event_widget extends WP_Widget
{

    function __construct()
    {
        parent::__construct(
        // Base ID of your widget
        'tournament_seeker_golf_event_widget',
        // Widget name will appear in UI
        __('TS Golf Events', 'tournament_seeker_golf_event_domain'),
        // Widget description
        array('description' => __('Displays the best video game events from TournamentSeeker.com!', 'tournament_seeker_golf_event_domain'),));
    }
    // Creating widget front-end
    // This is where the action happens
    public function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title)) echo $args['before_title'] . $title . $args['after_title'];
        // This is where you run the code and display the output
        $this->display_events($args, $instance);
        echo $args['after_widget'];
    }

    // Widget Backend
    public function form($instance)
    {
        $title = (isset($instance['title'])) ? $instance['title'] : __('New title', 'tournament_seeker_golf_event_domain');
        $api_key = (isset($instance['api_key'])) ? $instance['api_key'] : __('', 'tournament_seeker_golf_event_domain');
        $secret_key = (isset($instance['secret_key'])) ? $instance['secret_key'] : __('', 'tournament_seeker_golf_event_domain');
        $max_events = (isset($instance['max_events'])) ? $instance['max_events'] : 3;
        $list_type = (isset($instance['list_type'])) ? $instance['list_type'] : "my_events";
        $search_term = (isset($instance['search_term'])) ? $instance['search_term'] : "";


        // Widget admin form

?>
<h4>General</h4>
<p>
<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
</p>
<h4>API</h4>
<p>
<label for="<?php echo $this->get_field_id('api_key'); ?>"><?php _e('API Key:'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('api_key'); ?>" name="<?php echo $this->get_field_name('api_key'); ?>" type="text" value="<?php echo esc_attr($api_key); ?>" />
<label for="<?php echo $this->get_field_id('secret_key'); ?>"><?php _e('Secret Key:'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('secret_key'); ?>" name="<?php echo $this->get_field_name('secret_key'); ?>" type="text" value="<?php echo esc_attr($secret_key); ?>" />
</p>
<h4>Event Config</h4>
<p>
<label for="<?php echo $this->get_field_id('max_events'); ?>"><?php _e('Max events:'); ?></label>
<select id="<?php echo $this->get_field_id('max_events'); ?>" name="<?php echo $this->get_field_name('max_events'); ?>">
<?php for($i = 1; $i <= 5; $i++ ) : ?>
	<option <?= ($max_events == $i ? 'selected="selected"' : ""); ?>><?=$i?></option>
<?php endfor; ?>
</select>
</p>
<p>
<label for="<?php echo $this->get_field_id('list_type'); ?>"><?php _e('Events to List:'); ?></label>
<select id="<?php echo $this->get_field_id('list_type'); ?>" name="<?php echo $this->get_field_name('list_type'); ?>">
	<?php $val="my_events";?><option <?= ($list_type == $val ? 'selected="selected"' : ""); ?> value="<?=$val?>">My Events</option>
	<?php $val="my_favs";?><option <?= ($list_type == $val ? 'selected="selected"' : ""); ?> value="<?=$val?>">My Favorites</option>
	<?php $val="search";?><option <?= ($list_type == $val ? 'selected="selected"' : ""); ?> value="<?=$val?>">Custom Search</option>
</select>
</p>
<p>
<label for="<?php echo $this->get_field_id('search_term'); ?>"><?php _e('Search Term:'); ?></label>
<input class="widefat" id="<?php echo $this->get_field_id('search_term'); ?>" name="<?php echo $this->get_field_name('search_term'); ?>" type="text" value="<?php echo esc_attr($search_term); ?>" />
</p>
<?php
    }
    // Updating widget replacing old instances with new
    public function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        $instance['api_key'] = (!empty($new_instance['api_key'])) ? strip_tags($new_instance['api_key']) : '';
        $instance['secret_key'] = (!empty($new_instance['secret_key'])) ? strip_tags($new_instance['secret_key']) : '';
        $instance['max_events'] = (!empty($new_instance['max_events'])) ? strip_tags($new_instance['max_events']) : 3;
        $instance['list_type'] = (!empty($new_instance['list_type'])) ? strip_tags($new_instance['list_type']) : 'my_events';
        $instance['search_term'] = (!empty($new_instance['search_term'])) ? strip_tags($new_instance['search_term']) : '';
        return $instance;
    }

    private function css() {
?>

<style type="text/css">
.ts-golf-event-container {
	-webkit-border-radius: 20px;
	-moz-border-radius: 20px;
	border-radius: 20px;
	border: 2px #669933 solid;
	padding: 10px;

	background: rgba(255,255,255,1);
	background: -moz-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(250,250,250,1) 60%, rgba(245,245,245,1) 100%);
	background: -webkit-gradient(left top, left bottom, color-stop(0%, rgba(255,255,255,1)), color-stop(60%, rgba(250,250,250,1)), color-stop(100%, rgba(245,245,245,1)));
	background: -webkit-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(250,250,250,1) 60%, rgba(245,245,245,1) 100%);
	background: -o-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(250,250,250,1) 60%, rgba(245,245,245,1) 100%);
	background: -ms-linear-gradient(top, rgba(255,255,255,1) 0%, rgba(250,250,250,1) 60%, rgba(245,245,245,1) 100%);
	background: linear-gradient(to bottom, rgba(255,255,255,1) 0%, rgba(250,250,250,1) 60%, rgba(245,245,245,1) 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed', GradientType=0 );

}

.ts-golf-event-container .header-logo {
	width: 60%;
	margin: 0 auto;
}

.ts-golf-event-container .header-container {
	text-align: center;
}

.ts-golf-event-container h4 {
	color: #669933;
	font-weight: bold;
	text-align: center;
	margin: 5px 0 0 0;
}
.ts-golf-event-container h6 {
	color: #669933;
	font-weight: bold;
	text-align: center;
	margin: 0 0 5px 0;
}

.ts-golf-event {
	padding: 5px 0;
	border-bottom: 1px #eee solid;
}
.ts-golf-event:last-child {
	border-bottom: none;
}

.ts-golf-event .event-logo img {
	-webkit-border-radius: 10px;
	-moz-border-radius:10px;
	border-radius: 10px;
	border: 2px #fff solid;
}
.ts-golf-event .event-logo {
	max-width: 20%;
	display: inline-block;
}
.ts-golf-event .event-logo img.free {
	border-color: #669933;
}
.ts-golf-event .event-logo img.featured {
	border-color: #22bb22;
}
.ts-golf-event .event-logo img.charity {
	border-color: #98006a;
}
.ts-golf-event .event-logo img.premium {
	border-color: #fdce01;
}

.ts-golf-event .event-title {
	display: inline-block;
	width: 70%;
	padding-left:5%;
	font-weight: bold;
	text-align: center;
	vertical-align: top;
}

.ts-golf-event a {
	color:#669933 !important;
	text-overflow: ellipsis !important;
}

.ts-golf-event-date {
	display: inline-block;
	padding-top: 10px;
	font-weight: normal;

}

.golf-event-details {
	list-style-type: none !important;
	margin: 10px 10px 10px 5px !important;
	padding: 0px;

}

.golf-event-details li {
	background: none !important;
	font-size: 12px !important;
	text-align: center;
}

.golf-event-details li .header {
	display: block;
	font-weight: bold;
	color: #669933;
}

.golf-event-details li span{
	text-overflow: ellipsis;
}

.ts-golf-events-none {
	text-align: center;
	font-weight: bold;
	color: #669933;
}


</style>

<?php
	}

    private function display_events($args, $instance)
    {
    	$apiKey = $instance['api_key'];
    	$secret_key = $instance['secret_key'];

		$listTitle = "";
		$tsapi = new TS_Golf_API($apiKey, $secret_key);

		if($instance['list_type'] == "search")
		{
			$eventList = TS_Golf_Event::SearchEventsBySearchTerm($tsapi, $instance['search_term']);
			$listTitle = "Upcoming Events";
		} else if($instance['list_type'] == "my_favs")
		{
			$eventList = TS_Golf_Event::GetMyFavorites($tsapi);
			$listTitle = "My Favorites";
		} else
		{
			$eventList = TS_Golf_Event::GetMyEvents($tsapi);
			$listTitle = "My Events";
		}

		$newEvent = new TS_Golf_Event(0, 0, "API Test", "This is a test event", "0000-00-00 00:00:00", "0000-00-00 00:00:00", "2014-12-31 00:00:00", "2014-12-31 00:10:00", "Test Venue", "123 street", "city", 10, "12345", "http://www.google.com", "http://www.google.com");
		$newEvent->divisions[] = new TS_Golf_Division(0, 1, "", 1);
		$resp = TS_Golf_Event::AddEvent($tsapi, $newEvent);
		var_dump($resp);
		$this->css();
	?>

	<div class="ts-golf-event-container">
		<div class="header-container"><a href="htt://www.tournamentseeker.com/" target="_blank"><img src="<?= (plugin_dir_url( __FILE__ ) . "tslogo.png") ?>" class="header-logo"></a></div>
		<h6><?= $listTitle?></h6>
	<?php $idx = 0;
		if(is_array($eventList)) :
			shuffle($eventList);
		 foreach($eventList as $event) :
		 	$fl = $event->featureLevel;
		 	$featureLevel = ($fl == 3) ? "premium" : ($fl == 2) ? "charity" : ($fl == 1) ? "featured" : "free";

		 	?>
		<div class="ts-golf-event">
			<div class="event-logo">
				<a href="http://www.tournamentseeker.com/events/<?=$event->eventID?>/" target="_blank"><img class="<?=$featureLevel?>" src="<?=$event->eventLogoThumb?>"></a>
			</div>
			<div class="event-title">
				<a href="http://www.tournamentseeker.com/events/<?=$event->eventID?>/" target="_blank"><span><?=$event->eventName;?></span></a><br>
				<span class="ts-golf-event-date"><?=date('M jS, Y', strtotime($event->eventStartTime))?> &#149; <?=date('g:ia', strtotime($event->eventStartTime))?></span>
			</div>
			<ul class="golf-event-details">
				<!-- <li><span class="header">Date: </span></li> -->
				<?php if($event->isOnlineEvent) : ?>
					<li><span class="header">Location: </span><span>Online</span></li>
				<?php else : ?>
					<li><span class="header">Location: </span><span><?=$event->venueName?></span></li>
					<li><span class="header">Address: </span><span><?=$event->addressStreet?></span></li>
					<li><span class="header"> </span><span><?=$event->addressCity?>, <?=$event->addressState_Abbr;?> <?=$event->addressZip?></span></li>
				<?php endif; ?>

				<?php if(strlen($event->webAddress) > 0) : ?>
					<li><span class="header">Web: </span><a href="<?=$event->webAddress?>" target="_blank"><?=$event->webAddress?></a></li>
				<?php endif; ?>
				<?php if(strlen($event->facebookLink) > 0) : ?>
					<li><span class="header">Facebook: </span><a href="<?=$event->facebookLink?>" target="_blank">Facebook Page</a></li>
				<?php endif; ?>
				<?php if(strlen($event->twitterHash) > 0) : ?>
					<li><span class="header">Twitter: </span><a href="https://twitter.com/search?q=<?=$event->twitterHash?>" target="_blank"><?=$event->twitterHash?></a></li>
				<?php endif; ?>
				<?php if(strlen($event->streamLink) > 0) : ?>
					<li><span class="header">Stream: </span><a href="<?=$event->streamLink?>" target="_blank"><?=$event->streamLink?></a></li>
				<?php endif; ?>
				<?php if(strlen($event->regAddress) > 0) : ?>
					<li><span class="header">Register: </span><a href="<?=$event->regAddress?>" target="_blank">Registration Page</a></li>
				<?php endif; ?>


				<?php $firstEvent = true; ?>
				<?php foreach($event->divisions as $division) : ?>
					<li><span class="header"><?= ($firstEvent ? "Divisions: " : "")?></span><span><?=$division->formatName?> (<?=$division->skillTypeName?>)</span></li>
					<?php $firstEvent = false; ?>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php if(++$idx >= $instance['max_events']) break; ?>
	<?php endforeach; ?>
	<?php else : ?>
		<div class="ts-golf-events-none"><span>No events found. </span></div>
	<?php endif; ?>
	</div>

	<?php
    }

} // Class tournament_seeker_golf_event_widget ends here

// Register and load the widget
function tournament_seeker_golf_event_load_widget()
{
    register_widget('tournament_seeker_golf_event_widget');
}
add_action('widgets_init', 'tournament_seeker_golf_event_load_widget');
?>