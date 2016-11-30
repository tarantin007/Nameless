<?php 
/*
 *	Made by Samerton
 *  https://github.com/NamelessMC/Nameless/
 *  NamelessMC version 2.0.0-dev
 *
 *  License: MIT
 *
 *  Mark a post as spam
 */

if(!$user->isLoggedIn()){
	Redirect::to(URL::build('/forum'));
	die();
}
 
require('modules/Forum/classes/Forum.php');
 
// Always define page name
define('PAGE', 'forum');

// Initialise
$forum = new Forum();

// Get the post
if(!isset($_POST['post']) || !is_numeric($_GET['post'])){
	Redirect::to(URL::build('/forum'));
	die();
}

$post = $queries->getWhere('posts', array('id', '=', $_GET['post']));
if(!count($post)){
	// Doesn't exist
	Redirect::to(URL::build('/forum'));
	die();
}
$post = $post[0];

// Check the user can moderate the forum
if($forum->canModerateForum($user->data()->group_id, $post->forum_id)){
	// Check token
	if(Token::check(Input::get('token'))){
		// Valid token, go ahead and mark the user as spam
		// Delete all posts from the user
		$queries->delete('posts', array('post_creator', '=', $post->post_creator));
	
		// Delete all topics from the user
		$queries->delete('topics', array('topic_creator', '=', $post->post_creator));
		
		// Log user out
		$banned_user = new User($post->post_creator);
		$banned_user_ip = $banned_user->data()->last_ip;
		$banned_user->logout();
		
		// Ban IP
		$queries->create('ip_bans', array(
			'ip' => $banned_user_ip,
			'banned_by' => $user->data()->id,
			'banned_at' => date('U'),
			'reason' => 'Spam'
		));
		
		// Ban user
		$queries->update('users', $post->post_creator, array(
			'banned' => 1
		));
		
		// Redirect
		Session::flash('spam_info', $language->get('moderator', 'user_marked_as_spam'));
		Redirect::to(URL::build('/forum'));
		die();
		
	} else {
		// Invalid token
		Redirect::to(URL::build('/forum/view_topic/', 'tid=' . $post->topic_id . '&pid=' . $post->id));
		die();
	}
} else {
	// Can't moderate forum
	Redirect::to(URL::build('/forum'));
	die();
}