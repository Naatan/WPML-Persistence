<?php

/*
Plugin Name: WPML Persistence
Plugin URI: http://wordpress.org/
Description: Ensures languages are persistent across a session and dont reset when a url doesnt include the language variable.
Version: 0.1
Author: Nathan Rijksen
Author URI: http://naatan.com/
 */

class WPML_Persistence
{
	
	/**
	 * Constructor
	 * 
	 * @returns	void					
	 */
	function __construct()
	{
		$this->register_hooks();
	}
	
	/**
	 * Register hooks
	 * 
	 * @returns	void							
	 */
	function register_hooks()
	{
		add_action('template_redirect', array($this, 'set_language_cookie'), 1);
		add_action('template_redirect', array($this, 'validate_page_language'), 2);
		wp_register_sidebar_widget('icl_lang_sel_widget_persist', __('Persistant Language Selector', 'wpml-persistence'), array($this, 'get_widget'));
		
	}
	
	/**
	 * Validate if the current page requested is in the users selected language, and if not - redirect
	 * 
	 * @returns	void							
	 */
	function validate_page_language()
	{
		// Don't validate if we don't have a preferred language
		if ( ! isset($_COOKIE['_icl_current_language_persist']))
		{
			return;
		}
		
		// Get preferred language and available langauges
		$lang 		= $_COOKIE['_icl_current_language_persist'];
		$languages 	= icl_get_languages('skip_missing=1');
		
		// If it's not available in the preferred language there's no point in validating
		if ( !isset($languages[$lang]))
		{
			return;
		}
		
		// Get relevant URI's for validation
		$site_uri = preg_replace('/^.*?\/\/.*?(\/.*)$/','$1',site_url()) . '/';
		$lang_uri = preg_replace('/^.*?\/\/.*?(\/.*)$/','$1',$languages[$lang]['url']);
		$req_uri  = $_SERVER['REQUEST_URI'];
		
		// Check if we need to force the language uri
		if (
			stripos($lang_uri,$req_uri) === false OR // check if request uri has the language uri
			(
				// Additional checks to see if its the front page
				$req_uri == $site_uri AND
				$lang_uri != $site_uri
			)
		)
		{
			header('location: ' . $languages[$lang]['url']);
			exit;
		}
		
	}
	
	/**
	 * Set the persistent language cookie, which holds the users preferred language
	 * 
	 * @returns	void							
	 */
	function set_language_cookie()
	{
		
		// Only set the cookie if the ?lang parameter is used or the cookie has not been set yet
		if (isset($_COOKIE['_icl_current_language_persist']) AND ! isset($_GET['lang']))
		{
			return;
		}
		
		// Get active language, GET param takes priority
		$lang = isset($_GET['lang']) ? $_GET['lang'] : ICL_LANGUAGE_CODE;
		
		// Cookie properties
		$cookie_domain 	= defined('COOKIE_DOMAIN') 	? COOKIE_DOMAIN : $_SERVER['HTTP_HOST'];
		$cookie_path 	= defined('COOKIEPATH') 		? COOKIEPATH 	: '/';
		
		// Set the cookie
		setcookie('_icl_current_language_persist', $lang, time()+86400, $cookie_path, $cookie_domain);
		$_COOKIE['_icl_current_language_persist'] = $lang;
	}
	
	/**
	 * Get the persistent language switcher widget
	 * Since we need to have a GET variable to force the switch we cant use the default one
	 * 
	 * @returns	void							
	 */
	function get_widget()
	{
		
		// Get available languages, including missing ones for the current page
		// This means that if the page doesnt have a translation, clicking the language
		// will redirect you to the front page
		$languages = icl_get_languages('skip_missing=0&orderby=name');
		
		// Check if we have more than 1 language, otherwise.. whats the point?
		if(1 < count($languages))
		{
			
			// echo languages
			echo '<ul class="language_switcher">';
			
			foreach($languages as $l)
			{
				echo '<li><a href="'.$l['url'].'?lang='.$l['language_code'].'">'.$l['translated_name'].'</a></li>';
			}
			
			echo '</ul>';
			
		}
	}
	
}

new WPML_Persistence;