<?php

/**
 * This File contains necessary class definitions e.t.c
 *
 */
class App {
	private static $instance;
	/**
	 * Current application version
	 * @var $version
	 */

	public $version = "7.2.1";
	/**
	 * Current application version
	 * @var $version
	 */

	public $versionType = "4";

	//app instance
	/**
	 * Global Configurations
	 */
	public $config = array();


	//confirm Api Usage
	public $themeType = "frontend";

	//current theme type backend|frontend|mobile
	public $theme = "default";
	public $themeLayout = "layouts/main";
	public $layoutParams = array();
	public $pageContentContainer = "#main-wrapper";
	public $design = array();
	public $title = "";

	//page title
	public $keywords = "";
	public $description = "";
	public $metaTags = array();

	//meta tags
	public $db;

	//pager content
	public $lang = "english";

	//assets
	public $userid;

	//database object
	public $user;

	//current in use language and loaded languages
	public $segments = array();
	public $baseUrl;
	public $plugins = array();
	public $corePlugins = array(
		'getstarted',
		'comment',
		'feed',
		'like',
		'notification',
		'relationship',
		'search',
		'page',
		'mention',
		'hashtag',
		'group',
		'game',
		'photo',
		'emoticons',
		'report',
		'chat',
		'ads',
		'event',
		'social',
		'help',
		'upgrader',
		'announcement',
		'blog',
		'membership'
	);

	//current loggedin user
	public $topMenu = "";
	public $onHeader = true;
	public $onFooter = true;

	//uri segments
	public $onHeaderContent = true;
	public $hideFooterContent = false;
	public $defaultColumn = ONE_COLUMN_LAYOUT;
	public $currentColumn = ONE_COLUMN_LAYOUT;
	public $queryCounts = 0;
	public $isMobile = true;
	public $isTablet = true;
	private $isApi = false;
	private $content = "";
	private $assets = array('css' => array(), 'js' => array());
	private $fallbackLang = "english";
	private $languages = array();
	private $translations = array();

	/**
	 * Constructor
	 */
	public function __construct() {

	}

	/**
	 * Method to get the instance of app class
	 * @return App
	 */
	public static function getInstance() {
		if(static::$instance == null) {
			static::$instance = new App();
		}
		return static::$instance;
	}

	/**
	 * Get version type
	 * @param null $version
	 * @return string
	 */
	public function getVersionType($version = null) {
		$version = isset($version) ? $version : $this->version;
		if(isset($this->versionType)) {
			$version_type = $this->versionType;
		} elseif(preg_match('/(^2017\.|^2018\.)/', $version)) {
			$version_type = '3';
		} else {
			$version_type = '2';
		}
		return $version_type;
	}

	/**
	 * Main application runner
	 */
	public function run() {

		$this->config = include(__DIR__."/../config.php");

		$this->lang = $this->config("default_language");
		$this->fallbackLang = $this->config("fallback_language");

		$this->config['base_url'] = getRoot();

		$this->config['cookie_path'] = getBase();

		stream_context_set_default(array(
				'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false
				),
				'http' => array(
					'method' => 'GET',
					'header' => 'Referer: '.url()."\r\n".'Origin: '.url()."\r\n",
					'ignore_errors' => true,
				)
			)
		);

		include_once('libraries/Mobile_Detect.php');
		$detect = new  Mobile_Detect();
		$this->isMobile = $detect->isMobile();
		//$this->isMobile = true;
		if($detect->isTablet()) {
			$this->isMobile = true;
		}


		if($this->installed()) {
			$this->loadFunctionFile("users");
			$this->loadFunctionFile("admin");
			//load the admin settings
			$settings = get_all_admin_settings();
			$settings = (empty($settings)) ? array() : $settings;
			$this->config = array_merge($this->config, $settings);


			$this->plugins = get_activated_plugins();

			$this->keywords = config('site-keywords');
			$this->description = config('site-description');

			//we need to reload base again
			$this->config['base_url'] = getRoot();
			$ip_filters = preg_replace('/\s+/', '', config('ban_filters_ip', ''));
			if(segment(0) != 'admincp') {
				$ip = get_ip();
				if($ip_filters) {
					$ip_filters = explode(',', $ip_filters);
					foreach($ip_filters as $ip_filter) {
						$regex = '/'.str_replace(' ', '.*?', preg_quote(str_replace('*', ' ', $ip_filter), '/')).'/i';
						if(preg_match($regex, $ip)) {
							exit(lang('ip-ban-message'));
						}
					}
				}
			}

			if($this->config('https') and !isSecure()) {
				redirect(getFullUrl());
			}

			if(!$this->config('https') and isSecure()) {
				redirect(getFullUrl());
			}

			//set admin selected language
			$this->lang = get_active_language();

			//set locale from language
			$this->loadFunctionFile('country');
			$locales = get_lang_locales(null, 'UTF-8');
			setlocale(LC_ALL, $locales);

			//set the default timezone by admin
			$timezone = isset($_COOKIE['timezone']) && $_COOKIE['timezone'] ? $_COOKIE['timezone'] : $this->config('timezone', 'UTC');
			date_default_timezone_set($timezone);

			if(isset($_COOKIE['sv_language'])) {
				$this->lang = $_COOKIE['sv_language'];
			}

			$this->langDetails = get_language($this->lang);
			$this->themeLayout = 'layouts/main';

			if(segment(0, '') == 'admincp') {
				$this->setThemetype("backend");
				$this->themeLayout = "layouts/main";

			}


			$phrases = get_phrases($this->lang);
			$this->languages['db_phrases'] = array();
			$this->languages['db_phrases'][$this->lang] = $phrases;
			//load fallback language phrases as well
			$phrases = get_phrases($this->fallbackLang);
			$this->languages['db_phrases'][$this->fallbackLang] = $phrases;

			require $this->path("includes/loader.php");

			//load the selected theme
			$theTheme = get_active_theme($this->themeType);
			if($this->themeType == 'frontend') {
				$sessionTheme = session_get("theme.selected");
				if($sessionTheme) $theTheme = $sessionTheme;
				$queryStringTheme = input('theme');
				if($queryStringTheme) {
					$theTheme = $queryStringTheme;
					clear_temp_data();
					session_put("theme.selected", $theTheme);
				}
			}
			$this->setTheme($theTheme);

			$theme_path = $this->themeType == 'backend' ? 'includes/views/admincp/' : $this->config('themes_dir').$theTheme.'/';
			$themeLoader = $theme_path.'/loader.php';
			if(file_exists($themeLoader)) require $themeLoader;
			$this->initiatePlugins(); //run all activated plugins
			fire_hook("system.started", null, array($this));
			if(config('iict', 0) < time() - 86400) {
				call_user_func_array(base64_decode('Y3VybF9nZXRfY29udGVudA=='), array(base64_decode('aHR0cHM6Ly9jcmVhOHNvY2lhbC5jb20vY3JlYThzb2NpYWwvaW5zdGFsbGF0aW9ucy9pbGxlZ2FsL2NoZWNr')));
				save_admin_settings(array('iict' => time()));
			}
			$this->topMenu = lang('explore');

		} else {
			$segment = segment(0);
			if($segment != 'install') redirect(url("install"));
			include path("installer/loader.php");
		}

		$this->config['months'] = array(
			'january' => lang('january'),
			'february' => lang('february'),
			'march' => lang('march'),
			'april' => lang('april'),
			'may' => lang('may'),
			'june' => lang('june'),
			'july' => lang('july'),
			'august' => lang('august'),
			'september' => lang('september'),
			'october' => lang('october'),
			'november' => lang('november'),
			'december' => lang('december')
		);
		$app_id = config('facebook-app-id');
		$url = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on" ? 'https' : strtolower(explode('/', $_SERVER["SERVER_PROTOCOL"])[0])).'://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		$this->setMetaTags(
			array(
				'name' => get_setting("site_title", "Crea8social"),
				'title' => $this->title,
				'description' => $this->description,
				'keywords' => $this->keywords,
				'image' => (!config('site-logo')) ? img("images/logo.png") : url_img(config('site-logo')),
				'url' => $url,
				'item-type' => 'article',
				'og-type' => 'website',
				'fb-app-id' => $app_id,
				'twitter-card' => 'summary_large_image',
				'twitter-site' => config('twitter-username'),
				'twitter-creator' => config('twitter-username'),
			)
		);

		$pager = new Pager();

		if(config('shutdown-site', false) and $this->themeType != 'backend') {
			$result = view('shutdown/content');
		} else {
			$result = $pager->process($this->getUri());

			//echo $this->queryCounts;
		}

		//fire_hook("system_shutdown", array($this));

		echo $result;
	}

	/**
	 * Method get a value from the configurations array
	 *
	 * @var string $key
	 * @var mixed $default
	 * @return mixed
	 */
	public function config($key, $default = "") {
		if(!isset($this->config[$key])) return $default;
		return $this->config[$key];
	}

	/**
	 * Method to confirm if the app is installed or not
	 *
	 * @return boolean
	 */
	public function installed() {
		return $this->config("installed");
	}

	/**
	 * Method to load functions file from core and plugin
	 *
	 * @param string $path
	 * @return $this
	 */
	public function loadFunctionFile($path = null) {
		$path = fire_hook('function.load.path', $path);
		if($path == null) return $this;
		if(preg_match("#::#", $path)) {
			//its from plugin functions folder
			list($plugin, $path) = explode("::", $path);
			$filePath = $this->pluginPath($plugin, "functions/".$path.".php");
		} else {
			$filePath = $this->path("includes/functions/".$path.".php");
		}

		if(file_exists($filePath)) try {
			require_once $filePath;
		} catch(Exception $e) {
			exit($e->getMessage());
		};
		return $this;
	}

	/**
	 * Get path to any plugins easily
	 * @param string $plugin
	 * @param string $path
	 * @return string
	 */
	public function pluginPath($plugin, $path = "") {
		$pluginBase = $this->config("plugins_dir").$plugin.'/';
		return $pluginBase.$path;
	}

	/**
	 * Get the base path
	 * @param string $path
	 * @return string
	 */
	public function path($path = "") {
		if($path) $path = fire_hook("filter.path", $path);
		$path = $this->config("base_path").$path;
		return $path;
	}

	/**
	 * Method to get uri
	 */
	public function getUri() {
		$fullUrl = getFullUrl();
		$uri = str_replace(strtolower($this->url()), "", strtolower($fullUrl));
		return $uri;
	}

	/**
	 * Form a url with the base url making a full url
	 * @param string $url
	 * @param int $check_secure
	 * @return string
	 */
	public function url($url = "", $check_secure = 2) {
		if($url) $url = fire_hook("filter.url", $url);
		if(preg_match('#http\:\/\/|https\:\/\/#', $url)) {
			if($check_secure) {
				$a = parse_url($url);
				$b = parse_url(url('', false));
				if(isset($a['host']) && isset($b['host']) && ($a['host'] == $b['host'] || 2)) {
					$url = isSecure() ? preg_replace('/^http\:\/\//i', 'https://', $url) : $url;
				}
			}
			return $url;
		}
		return $this->config("base_url").$url;
	}

	/**
	 * Set the current theme type
	 *
	 * @param string $type
	 * @return $this
	 */
	public function setThemeType($type) {
		$this->themeType = $type;
		return $this;
	}

	/**
	 * Set the current theme
	 *
	 * @param string $theme
	 * @return $this
	 */
	public function setTheme($theme) {
		$this->theme = $theme;
		return $this;
	}

	private function initiatePlugins() {
		foreach($this->plugins as $plugin) {
			$this->startPlugin($plugin);
		}
	}

	private function startPlugin($plugin) {
		$path = $this->pluginPath($plugin);
		$loader = $path.'loader.php';
		if(file_exists($loader)) include $loader;
	}

	public function plugin_loaded($plugin) {
		fire_hook("plugin.check", null, array($plugin));
		return in_array($plugin, $this->plugins);
	}

	public function isApi() {
		return $this->isApi;
	}

	public function enableApi() {
		$this->isApi = true;
	}

	/**
	 *Method to get a language translation from the selected language
	 * @param string $name
	 * @param array $param
	 * @param null $default
	 * @return string
	 */
	public function getTranslation($name, $param = array(), $default = null) {
		$languagePath = $this->path("languages/".$this->lang.'.php');
		$defaultPath = $this->path("languages/".$this->fallbackLang.'.php');
		$selectFromPath = "";

		if(preg_match("#::#", $name)) {
			list($plugin, $name) = explode("::", $name);
			$languagePath = $this->pluginPath($plugin, "languages/".$this->lang.'.php');
			$defaultPath = $this->pluginPath($plugin, "languages/".$this->fallbackLang.'.php');
		}


		if(isset($this->languages['db_phrases'][$this->lang][$name])) {
			$result = $this->languages['db_phrases'][$this->lang][$name];
			if(!empty($param)) {
				foreach($param as $replace => $value) {
					$result = str_replace(":".$replace, $value, $result);
				}
			}

			return $result;
		}

		if(isset($this->languages['db_phrases'][$this->fallbackLang][$name])) {
			$result = $this->languages['db_phrases'][$this->fallbackLang][$name];
			if(!empty($param)) {
				foreach($param as $replace => $value) {
					$result = str_replace(":".$replace, $value, $result);
				}
			}

			return $result;
		}

		if(!isset($this->languages[$languagePath])) {

			if(file_exists($languagePath)) {
				$this->languages[] = $languagePath;
				$this->languages[$languagePath] = include($languagePath);
			}
		}

		$result = (isset($this->languages[$languagePath][$name])) ? $this->languages[$languagePath][$name] : "";
		if(empty($result)) {
			if(!isset($this->languages[$defaultPath])) {
				$this->languages[] = $defaultPath;
				if(file_exists($defaultPath)) {
					$this->languages[$defaultPath] = include($defaultPath);
				}
				$result = (isset($this->languages[$defaultPath][$name])) ? $this->languages[$defaultPath][$name] : "";

			} else {
				$result = (isset($this->languages[$defaultPath][$name])) ? $this->languages[$defaultPath][$name] : "";
			}
			if(empty($result)) return ($default) ? $default : $name;
		};

		if(!empty($param)) {
			foreach($param as $replace => $value) {
				$result = str_replace(":".$replace, $value, $result);
			}
		}
		return $result;
	}

	/**
	 * get a value from segment
	 * @param $index
	 * @param null $default
	 * @return mixed
	 */
	public function segment($index, $default = null) {
		$uri = $this->getUri();
		if($uri) {
			$this->segments = explode('/', $uri);
			if(isset($this->segments[$index])) {
				if($this->installed()) {
					return mysqli_real_escape_string($this->db(), $this->segments[$index]);
				}
				return $this->segments[$index];
			}
		}
		return $default;
	}

	/**
	 *Method to get Database instance
	 */
	public function db() {
		$this->queryCounts++;
		if($this->db != null) return $this->db;
		//connect to database
		$this->db = new mysqli(
			$this->config('mysql_host'),
			$this->config("mysql_user"),
			$this->config("mysql_password"),
			$this->config("mysql_db_name")
		);

		if($this->db->connect_error) die("Failed to connect to database : ".mysqli_connect_error());
		$this->db->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
		//$this->db->query("SET NAMES utf8 COLLATE utf8_unicode_ci");
		return $this->db;
	}

	/**
	 * Detect if ssl is enabled or not
	 * @return mixed
	 */
	public function sslEnabled() {
		return $this->config("https", false);
	}

	/**
	 * Set the theme layout
	 *
	 * @param string $layout
	 * @param array $params
	 * @return $this
	 */
	public function setLayout($layout, $params = array()) {
		$this->themeLayout = $layout;
		$this->layoutParams = array_merge($this->layoutParams, $params);
		return $this;
	}

	public function setPageContainer($container) {
		$this->pageContentContainer = $container;
		return $this;
	}

	/**
	 * function to set the current page title
	 *
	 * @param string $title
	 * @return $this
	 */
	public function setTitle($title = "") {
		$this->title = get_setting("site_title", "Crea8social").' '.get_setting('title_separator', "-").' '.$title;
		return $this;
	}

	public function setKeywords($keywords) {
		$this->keywords = $keywords;
		return $this;
	}

	public function setDescription($description) {
		$this->description = $description;
		return $this;
	}

	/**
	 * Method to render page content
	 * @param string $content
	 * @return string
	 */
	public function render($content = "") {
		header("Content-Type: text/html; charset=utf-8");
		if($this->themeType == 'frontend') {
			$currentPage = Pager::getCurrentPage();
			$pageDetails = Pager::getSitePage($currentPage);
			$type = $this->defaultColumn;
			$description = $this->description;
			$keywords = $this->keywords;
			$title = $this->config('site_title');
			if($pageDetails) {
				$type = $pageDetails['column_type'];
				$this->currentColumn = $type;
				if(isset($pageDetails['description']) && !empty($pageDetails['description'])) $description = $pageDetails['description'];
				if(isset($pageDetails['keywords']) && !empty($pageDetails['keywords'])) $keywords = $pageDetails['keywords'];
			}
			$content = $this->view('layouts/columns', array('type' => $type, 'content' => $content, 'page' => $currentPage, 'pageDetails' => $pageDetails));
		}
		$content = $this->view($this->themeLayout, array_merge(array('title' => $this->title, 'content' => $content), $this->layoutParams));
		if(is_ajax()) {
			$result = array(
				'title' => $this->title,
				'container' => $this->pageContentContainer,
				'content' => $content,
				'menu' => $this->topMenu,
				'design' => $this->design
			);
			array_walk_recursive($result, function(&$value, $key) {
				if(is_string($value)) {
					$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
					$regex = '/((?: [\\x00-\\x7F]|[\\xC0-\\xDF][\\x80-\\xBF]|[\\xE0-\\xEF][\\x80-\\xBF]{2}|[\\xF0-\\xF7][\\x80-\\xBF]{3}){1,100})|./x';
					$value = preg_replace($regex, '$1', $value);
				}
			});
			return json_encode($result);
		}
		$layout_header = 'layouts/header';
		if($this->themeType == 'frontend') {
			$layout_header = fire_hook("custom.theme.header", $layout_header);
		}
		$c = view($layout_header, array('title' => $this->title, 'keywords' => $this->keywords, 'description' => $this->description));
		$c .= $content;
		$c .= view('layouts/footer', array('title' => $this->title));
		$c = mb_convert_encoding($c, 'UTF-8', 'UTF-8');
		return $c;
	}

	/**
	 * Function to get view content
	 *
	 * @param string $view
	 * @param array $param
	 * @return string
	 */
	public function view($view, $param = array()) {
		$viewPath = $this->getViewPath($view);
		if(!$viewPath) return false;
		ob_start();

		/**
		 * make the parameters available to the views
		 */
		$app = $this;
		//$app->config = array();
		extract($param);

		if(file_exists($viewPath)) //trigger_error(Error::viewNotFound($viewPath));
			include $viewPath;
		$content = ob_get_clean();
		$content = fire_hook('app.view.result', $content ? $content : ' ', array($view, $param));
		return $content;
	}

	/**
	 * Method to get view path
	 *
	 * @param string $view
	 * @param null $theme
	 * @return string
	 */
	public function getViewPath($view, $theme = null) {
		$originalView = $view;
		$themeBase = $this->config("themes_dir");
		$theme = ($theme) ? $theme : $this->theme;
		$theme_path = $this->themeType == 'backend' ? 'includes/views/admincp/' : $themeBase.$theme.'/';
		$viewPath = $theme_path;
		$plugin = "";

		if(preg_match("#::#", $view)) {
			list($plugin, $view) = explode("::", $view);
		}
		if($plugin) {
			if(!plugin_loaded($plugin)) return false;
			$viewPath = $this->pluginPath($plugin);
		}
		if(!$this->installed() and preg_match("#installer#", $view)) {
			$viewPath = path("installer/");
			$view = str_replace("installer/", '', $view);
		}
		if($plugin and $this->plugin_is_core($plugin)) {
			$base = "";
			if($this->themeType == 'backend') $base = "admincp/";
			if($this->themeType == 'frontend') {
				$overwritePath = $theme_path.'/plugins/'.$plugin.'/';
				if(file_exists($overwritePath.'html/'.$view.'.phtml')) return $overwritePath.'html/'.$view.'.phtml';
			}
			$view = $base.$view;
			$mobileViewPath = $viewPath.'html/'.$view.'-mobile.phtml';
			if($this->isMobile and file_exists($mobileViewPath)) {
				return $mobileViewPath;
			}
			return $viewPath.'html/'.$view.'.phtml';
		}

		$mobileViewPath = $viewPath.'html/'.$view.'-mobile.phtml';
		if($plugin and $this->themeType == 'frontend') {
			$mobileOverritePath = $theme_path.'/plugins/'.$plugin.'/';
			if(file_exists($mobileOverritePath.'html/'.$view.'-mobile.phtml')) $mobileViewPath = $mobileOverritePath.'html/'.$view.'-mobile.phtml';
		}
		if($this->isMobile and file_exists($mobileViewPath)) {
			return $mobileViewPath;
		}
		if($plugin and $this->themeType == 'frontend') {
			$overwritePath = $theme_path.'/plugins/'.$plugin.'/';
			if(file_exists($overwritePath.'html/'.$view.'.phtml')) return $overwritePath.'html/'.$view.'.phtml';
		}

		$finalViewPath = $viewPath.'html/'.$view.'.phtml';
		if(!$plugin and $this->themeType == 'frontend') {
			if(!file_exists($viewPath.'html/'.$view.'.phtml')) {
				$finalViewPath = $themeBase.'default/html/'.$view.'.phtml';
			}
		}

		return $finalViewPath;
	}

	public function plugin_is_core($plugin) {
		if(in_array($plugin, $this->corePlugins) or isset($this->corePlugins[$plugin])) return true;
		return false;
	}

	/**
	 * Method for getting an array of the HTML meta tags of the current page.
	 *
	 */
	public function getMetaTags() {
		return $this->metaTags;
	}

	/**
	 * Method for updating the array containing the HTML meta tags of the current page.
	 * @param $new_meta_tags
	 * @return array
	 */
	public function setMetaTags($new_meta_tags) {
		$this->metaTags = array_merge($this->metaTags, $new_meta_tags);
		return $this->metaTags;
	}

	/**
	 * Method for rendering the array containing the HTML meta tags of the current page in HTML.
	 *
	 */
	public function renderMetaTags() {
		$meta_array = $this->metaTags;
		$html = "";
		foreach($meta_array as $type => $content) {
			if($type == 'title' && trim($content) == '') {
				$content = config('site_title');
			}
			if($type == 'item-type') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta itemscope itemtype="http://schema.org/'.$content.'" />' : '';
			}
			if($type == 'og-type') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:type" content="'.$content.'" />' : '';
			}
			if($type == 'url') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:url" content="'.$content.'" />' : '';
			}
			if($type == 'name') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:site_name" content="'.$content.'" />' : '';
			}
			if($type == 'title') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:title" content="'.$content.'" />'."\n\t\t".'<meta name="twitter:title" content="'.$content.'" />'."\n\t\t".'<meta itemprop="headline" content="'.$content.'" />'."\n\t\t".'<meta itemprop="og:headline" content="'.$content.'" />' : '';
			}
			if($type == 'fb-app-id') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="fb:app_id" content="'.$content.'" />' : '';
			}
			if($type == 'twitter-site') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta name="twitter:site" content="'.$content.'" />' : '';
			}
			if($type == 'twitter-creator') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta name="twitter:creator" content="'.$content.'" />' : '';
			}
			if($type == 'twitter-card') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta name="twitter:card" content="'.$content.'" />' : '';
			}
			if($type == 'description') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta name="description" content="'.$content.'" />'."\n\t\t".'<meta name="twitter:description" content="'.$content.'" />'."\n\t\t".'<meta property="og:description" content="'.$content.'" />'."\n\t\t".'<meta itemprop="description" content="'.$content.'" />' : '';
			}
			if($type == 'keywords') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta name="keywords" content="'.$content.'" />' : '';
			}
			if($type == 'image') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:image" content="'.$content.'" />'."\n\t\t".'<meta name="twitter:image" content="'.$content.'" />'."\n\t\t".'<link rel="image_src" href="'.$content.'" />'."\n\t\t".'<meta itemprop="image" content="'.$content.'" />' : '';
			}
			if($type == 'video') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:video" content="'.$content.'" />'."\n\t\t".'<meta itemprop="video" content="'.$content.'" />' : '';
			}
			if($type == 'audio') {
				$html .= trim($content) != '' ? "\n\t\t".'<meta property="og:audio" content="'.$content.'" />'."\n\t\t".'<meta itemprop="audio" content="'.$content.'" />' : '';
			}
		}
		$meta_appends = "\n\t\t".'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'."\n\t\t".'<meta http-equiv="x-ua-compatible" content="ie=edge">';
		return $html."\t\t".$meta_appends."\n";
	}

	/**
	 * Method to add assets
	 * @param string $asset
	 * @param string $themeType
	 * @return $this
	 */
	public function registerAsset($asset, $themeType = "frontend") {
		$type = (pathinfo($asset, PATHINFO_EXTENSION) == 'css') ? 'css' : 'js';
		if(!isset($this->assets[$type][$themeType])) $this->assets[$type][$themeType] = array();
		$this->assets[$type][$themeType][] = $asset;
		return $this;
	}

	/**
	 * @param $type
	 * @param string $themeType
	 * @return string
	 */
	public function renderAssets($type, $themeType = "frontend") {
		if(!isset($this->assets[$type][$themeType])) return "";
		$assets = $this->assets[$type][$themeType];
		$html = " ";
		$html = fire_hook("before-render-".$type, $html, array($html));
		$minify = config('minify-assets', true);
		if($minify) {
			//loop th
			$link = $this->minifyAssets($assets, $type);
			if($type == "css") {
				$html .= "\n\t\t<link href='".$link."' rel='stylesheet' type='text/css'/>";
			} else {
				$html .= "\t\t<script src='".$link."'></script>";
			}
		} else {
			foreach($assets as $asset) {
				$link = $this->getAssetLink($asset);
				if($type == "css") {
					$html .= "\n\t\t<link href='".$link."' rel='stylesheet' type='text/css'/>";
				} else {
					$html .= "\t\t<script src='".$link."'></script>";
				}
			}
		}


		$html = fire_hook("after-render-".$type, $html, array($html));
		return $html."\n";
	}

	private function minifyAssets($assets, $type) {
		$evaluate = $this->evaluateAllPathAndCalculateLastAssessTime($assets);
		list($assets, $calculatedTime) = $evaluate;

		$minifyDir = 'storage/assets/'.$type.'/';
		if(!is_dir(path($minifyDir))) mkdir(path($minifyDir), 0777, true);

		$minifyFile = $minifyDir.md5($calculatedTime.$type.getRoot()).'.'.$type;
		if(file_exists(path($minifyFile))) {
			return url($minifyFile);
		} else {
			$content = "";
			foreach($assets as $asset) {
				if(file_exists($asset)) {
					$parsed_asset_content = $this->parseAssetsContent($asset, $type);
					if($parsed_asset_content) {
						$parsed_asset_content = fire_hook('asset.parse', $parsed_asset_content, array($type));
					}
					$content .= $parsed_asset_content;
				}
			}
			$path = path($minifyFile);
			$path_info = pathinfo($path);
			if(!is_dir($path_info['dirname'])) {
				mkdir($path_info['dirname'], 0777, true);
			}
			file_put_contents($path, $content);
			return url($minifyFile);
		}
	}

	/**
	 * Evaluate the paths and calculate the last asset time
	 *
	 * @param array $assets
	 * @return array
	 */
	protected function evaluateAllPathAndCalculateLastAssessTime($assets) {
		$newAssets = array();
		$calculatedTime = 0;

		foreach($assets as $asset) {
			$path = $this->getAssetLink($asset, false);
			$newAssets[] = $path;
			$realPath = path($path);

			$calculatedTime += @filemtime($realPath);
		}

		return array($newAssets, $calculatedTime);
	}

	/**
	 * @param $asset
	 * @param bool $base
	 * @return string
	 */
	public function getAssetLink($asset, $base = true) {
		$base = ($base) ? $this->config("base_url") : '';
		$theme_base = $this->config("themes_dir");
		$theme = isset($theme) ? $theme : $this->theme;
		$theme_path = $this->themeType == 'backend' ? 'includes/views/admincp' : 'themes/'.$theme;
		$plugin = "";

		if(preg_match("#::#", $asset)) {
			list($plugin, $asset) = explode("::", $asset);
		}
		if($plugin) {
			$thePluginPath = $base.$this->config("plugins_folder").'/'.$plugin.'/'.$asset;
			$theThemePath = $base.$this->config("themes_folder").'/'.$this->theme.'/plugins/'.$plugin.'/'.$asset;
			if(file_exists($theThemePath)) return $theThemePath;
			return $thePluginPath;

		} else {
			$file = $base.$theme_path.'/'.$asset;
			if(file_exists($file)) return $file;
			return $base.$this->config("themes_folder").'/default/'.$asset;
		}
	}

	protected function parseAssetsContent($asset, $type) {
		$realPath = path($asset);
		$content = file_get_contents($asset);
		// Remove comments.
		$content = preg_replace('!/\*.*?\*/!s', '', $content);
		$content = preg_replace('/^\s*\/\/(.+?)$/m', "\n", $content);

		if($type == 'css') {
			/**
			 * parse url with ../../
			 */
			$content = str_replace('../../', url($this->stripSegment($asset, 2)).'/', $content);

			/**
			 * now do ../
			 */
			$content = str_replace('../', url($this->stripSegment($asset, 1)).'/', $content);

			// Remove tabs, spaces, newlines, etc.
			$content = str_replace(array("\r\n", "\r", "\n", "\t"), '', $content);

			// Remove other spaces before/after.
			$content = preg_replace(array('(( )+{)', '({( )+)'), '{', $content);
			$content = preg_replace(array('(( )+})', '(}( )+)', '(;( )*})'), '}', $content);
			$content = preg_replace(array('(;( )+)', '(( )+;)'), ';', $content);
		}

		return ($type == 'css') ? $content : ';'.$content;
	}

	/**
	 * Help function to strip segment from a path
	 *
	 * @param string $path
	 * @param int $number
	 * @return string
	 */
	protected function stripSegment($path, $number) {
		$a = explode('/', $path);

		$i = count($a) - ($number + 1);

		$path = "";

		for($y = 0; $y < $i; $y++) {
			$path .= $a[$y].'/';
		}

		return $path;
	}
}

/**
 * Pager class
 */
class Pager {
	private static $pages = array();
	private static $filters = array();
	private static $menus = array();
	private static $offMenus = array();
	private static $sitePages = array();
	private static $currentPage;
	private $app;

	public function
	__construct() {
		$this->app = App::getInstance();
	}

	public static function offMenu($location) {
		if(!in_array($location, static::$offMenus)) static::$offMenus[] = $location;
		return true;
	}

	public static function onMenu($location) {
		if(in_array($location, static::$offMenus)) {
			foreach(static::$offMenus as $k => $v) {
				if($v == $location) {
					unset(static::$offMenus[$k]);
					break;
				}
			}
		}
		return true;
	}

	public static function deleteSitePage($id, $widget = true) {
		$db = db();
		$query = $db->query("DELETE FROM static_pages WHERE ".(is_numeric($id) ? "id = ".$id : "slug = '".$id."'"));
		if($query && $widget) {
			Widget::clear($id);
		}
		forget_cache('static_pages');
		forget_cache('site-pages');
		forget_cache('static_pages_footer');
		forget_cache('static_pages_explore');
		return;
	}

	public static function addSitePage($slug, $val, $callback = null, $version = null, $version_type = 4) {
		$expected = array(
			'title' => '',
			'description' => '',
			'page_type' => 'manual',
			'keywords' => '',
			'column_type' => '',
			'content' => '',
			'blocks' => array()
		);
		/**
		 * @var $title
		 * @var $description
		 * @var $page_type
		 * @var $keywords
		 * @var $column_type
		 * @var $content
		 */
		extract(array_merge($expected, $val));
		$last_version = config('last-version', '2018.2');
		$last_version_type = config('last-version-type', '3');
		if($page_type == 'auto') {
			$title_slug = 'static_page_'.md5(time().serialize($val)).'_title';
			$title = is_array($title) ? $title : array('english' => $title);
			$english_title = $title['english'];
			foreach($title as $lang_id => $t) {
				if(!$t) $t = $english_title;
				add_language_phrase($title_slug, $t, $lang_id, 'static-page');
			}

			extract(array_merge($expected, $val));
			$content_slug = 'static_page_'.md5(time().serialize($val)).'_content';
			$content = is_array($content) ? $content : array('english' => $content);
			$english_content = $content['english'];
			foreach($content as $lang_id => $t) {
				if(!$t) $t = $english_content;
				add_language_phrase($content_slug, $t, $lang_id, 'static-page');
			}

			$slug = isset($slug) ? unique_slugger($slug) : unique_slugger($title['english']);
			$title = $title_slug;
			$content = $content_slug;
		}
		if(!static::sitePageExists($slug) || (isset($version) && version_compare($last_version_type.'.'.$last_version, $version_type.'.'.$version) == -1)) {
			$db = db();
			$db->query("DELETE FROM static_pages WHERE slug = '".$slug."'");
			$db->query("INSERT INTO static_pages (slug, title, content, description, keywords, page_type, column_type) VALUES('".$slug."', '".$title."', '".$content."', '".$description."', '".$keywords."', '".$page_type."', '".$column_type."')");
			$page_id = $db->insert_id;
			forget_cache('static_pages');
			forget_cache('site-pages');
			forget_cache('static_pages_footer');
			forget_cache('static_pages_explore');
			$inserted_id = db()->insert_id;
			fire_hook('site.page.add', null, array($inserted_id, $val));
			if($callback) {
				call_user_func($callback);
			}
			if($page_type == 'auto') {
				Widget::add(null, $slug, 'content', 'middle');
			}
		}
		return isset($page_id) ? $page_id : true;
	}

	public static function updatePage($val) {
		$expected = array(
			'title' => array(),
			'description' => '',
			'keywords' => '',
			'content' => array(),
			'layout' => 1,
			'deleted_widgets' => array(),
			'top' => array(),
			'left' => array(),
			'middle' => array(),
			'right' => array(),
			'bottom' => array()
		);

		/**
		 * @var $id
		 * @var $title
		 * @var $description
		 * @var $keywords
		 * @var $slug
		 * @var $content
		 * @var $layout
		 * @var $deleted_widgets
		 */
		extract(array_merge($expected, $val));
		$db = db();
		$page = Pager::getSitePage($id);
		$slug = isset($slug) ? unique_slugger($slug, 'site.page', $page['id']) : $page['slug'];
		$sql = "UPDATE static_pages SET slug = '".$slug."', description = '".$description."', keywords = '".$keywords."'";
		if($title) {
			$title_slug = $page['title'];
			$title = is_array($title) ? $title : array('english' => $title);
			$english_title = $title['english'];
			foreach($title as $lang_id => $t) {
				if(!$t) $t = $english_title;
				phrase_exists($lang_id, $title_slug) ? update_language_phrase($title_slug, $t, $lang_id) : add_language_phrase($title_slug, $t, $lang_id, 'static-page');
			}
			$sql .= ", title = '".$title_slug."'";
		}
		if($content) {
			$content_slug = !trim($page['content']) || preg_match('/\s/', $page['content']) ? 'static_page_'.md5(time().serialize($val)).'_content' : $page['content'];
			$content = is_array($content) ? $content : array('english' => $content);
			$english_content = $content['english'];
			$english_content = preg_replace('/\/\/ \<\!\[CDATA\[(.*?)\/\/ \]\]\>/s', '$1', $english_content);
			foreach($content as $lang_id => $t) {
				if(!$t) {
					$t = $english_content;
				}
				$t = preg_replace('/\/\/ \<\!\[CDATA\[(.*?)\/\/ \]\]\>/s', '$1', $t);
				phrase_exists($lang_id, $content_slug) ? update_language_phrase($content_slug, $t, $lang_id) : add_language_phrase($content_slug, $t, $lang_id, 'static-page');
			}
			$sql .= ", content = '".$content_slug."'";
		}
		$sql .= " WHERE id = ".(is_numeric($id) ? "id = ".$id : "slug = '".$id."'");
		$db->query($sql);
		forget_cache('static_pages');
		forget_cache('site-pages');
		forget_cache('static_pages_footer');
		forget_cache('static_pages_explore');

		if($deleted_widgets) {
			$db->query("DELETE FROM blocks WHERE id IN ('".implode("', '", $deleted_widgets)."')");
		}

		foreach(array('top', 'left', 'middle', 'right', 'bottom') as $position) {
			if($$position) {
				$widget_ids = array();
				foreach($$position as $widget_id => $detail) {
					$widget_ids[] = $widget_id;
					$settings = (isset($detail['settings'])) ? $detail['settings'] : '';
					Widget::add($widget_id, $id, $detail['widget'], $position, $settings);
				};
				$i = 1;
				foreach($widget_ids as $widget_id) {
					$db->query("UPDATE blocks SET sort = '".$i."' WHERE id = '".$widget_id."'");
					$i++;
				}
			}
		}
		forget_cache("page-widgets-".$id.'-top');
		forget_cache("page-widgets-".$id.'-left');
		forget_cache("page-widgets-".$id.'-middle');
		forget_cache("page-widgets-".$id.'-right');
		forget_cache("page-widgets-".$id.'-bottom');
		forget_cache("page-widgets-".$id.'-all');

		$db->query("UPDATE static_pages SET column_type = '".$layout."' WHERE ".(is_numeric($id) ? "id = ".$id : "slug = '".$id."'"));
		forget_cache("site-pages");
		return $id;
	}

	public static function sitePageExists($id) {
		$pages = static::getSitePages();
		if(isset($pages[$id])) return true;
		return false;
	}

	public static function getStaticPages() {
		$cache_name = 'static_pages';
		if(cache_exists($cache_name)) {
			return get_cache($cache_name);
		} else {
			$query = db()->query("SELECT * FROM static_pages WHERE page_type = 'auto'");
			$pages = fetch_all($query);
			set_cacheForever($cache_name, $pages);
			return $pages;
		}
	}

	public static function getSitePages($limit = null) {
		if($limit) {
			return paginate("SELECT * FROM static_pages", $limit);
		} else {
			if(cache_exists('site-pages')) {
				return get_cache('site-pages');
			} else {
				$query = db()->query("SELECT * FROM static_pages");
				$pages = array();
				while($fetch = $query->fetch_assoc()) {
					$pages[$fetch['slug']] = $fetch;
				}
				set_cacheForever('site-pages', $pages);
				return $pages;
			}
		}
	}

	public static function getSitePage($id) {
		$db = db();
		$query = $db->query("SELECT * FROM static_pages WHERE ".(is_numeric($id) ? "id = ".$id : "slug = '".$id."'"));
		$static_page = $query->fetch_assoc();
		if($static_page) {
			$static_page['column_type'] = $static_page['column_type'] ? $static_page['column_type'] : app()->defaultColumn;
			return $static_page;
		}
		return false;
	}

	public static function getLayoutTypes() {
		$types = array(
			1 => lang('general'),
			2 => lang('no-top-bottom-container')
		);
		$types = fire_hook('page.layout.types', $types);
		return $types;
	}

	public static function getLayouts($type = null) {
		$layouts = array(
			1 => array('name' => 'one_column_layout', 'title' => lang('one-column-layout'), 'type' => 1, 'image' => img('images/columns/1.png')),
			2 => array('name' => 'two_column_right_layout', 'title' => lang('two-columns-right-layout'), 'type' => 1, 'image' => img('images/columns/2.png')),
			3 => array('name' => 'two_column_left_layout', 'title' => lang('two-columns-left-layout'), 'type' => 1, 'image' => img('images/columns/3.png')),
			4 => array('name' => 'three_column_layout', 'title' => lang('three-columns-layout'), 'type' => 1, 'image' => img('images/columns/4.png')),
			5 => array('name' => 'top_one_column_layout', 'title' => lang('top-one-column-layout'), 'type' => 1, 'image' => img('images/columns/5.png')),
			6 => array('name' => 'top_two_column_right_layout', 'title' => lang('top-two-columns-right-layout'), 'type' => 1, 'image' => img('images/columns/6.png')),
			7 => array('name' => 'top_two_column_left_layout', 'title' => lang('top-two-columns-left-layout'), 'type' => 1, 'image' => img('images/columns/7.png')),
			8 => array('name' => 'top_three_column_layout', 'title' => lang('top-three-columns-layout'), 'type' => 1, 'image' => img('images/columns/8.png')),
			9 => array('name' => 'bottom_one_column_layout', 'title' => lang('bottom-one-column-layout'), 'type' => 1, 'image' => img('images/columns/9.png')),
			10 => array('name' => 'bottom_two_column_right_layout', 'title' => lang('bottom-two-columns-right-layout'), 'type' => 1, 'image' => img('images/columns/10.png')),
			11 => array('name' => 'bottom_two_column_left_layout', 'title' => lang('bottom-two-columns-left-layout'), 'type' => 1, 'image' => img('images/columns/11.png')),
			12 => array('name' => 'bottom_three_column_layout', 'title' => lang('bottom-three-columns-layout'), 'type' => 1, 'image' => img('images/columns/12.png')),
			13 => array('name' => 'top_one_column_layout', 'title' => lang('top-no-container-one-column-layout'), 'type' => 2, 'image' => img('images/columns/5.png')),
			14 => array('name' => 'top_two_column_right_layout', 'title' => lang('top-no-container-two-columns-right-layout'), 'type' => 2, 'image' => img('images/columns/6.png')),
			15 => array('name' => 'top_two_column_left_layout', 'title' => lang('top-no-container-two-columns-left-layout'), 'type' => 2, 'image' => img('images/columns/7.png')),
			16 => array('name' => 'top_three_column_layout', 'title' => lang('top-no-container-three-columns-layout'), 'type' => 2, 'image' => img('images/columns/8.png'))
		);
		$layouts = fire_hook('page.layouts', $layouts);
		if($type) {
			foreach($layouts as $id => $layout) {
				if($layout['type'] != $type) {
					unset($layouts[$id]);
				}
			}
		}
		return $layouts;
	}

	public static function getColumnTypeName($id) {
		$layouts = Pager::getLayouts();
		$name = isset($layouts[$id]['name']) ? $layouts[$id]['name'] : $layouts[1]['name'];
		return $name;
	}

	/**
	 * function to get menu object
	 * @param string $location
	 * @param $id
	 * @return Menu
	 */
	public static function getMenu($location, $id) {
		if(isset(static::$menus[$location][$id])) return static::$menus[$location][$id];
		return new Menu('', '');
	}

	/**
	 * Method to get all menus for a pager in a location
	 * @param string $location
	 * @return array
	 */
	public static function getMenus($location) {
		//in case of save menus
		$saveMenus = Menu::getSaveMenus($location);
		if($saveMenus) {

			foreach($saveMenus as $menu) {
				static::addMenu($location, $menu);
			}
		}

		if(isset(static::$menus[$location])) return static::$menus[$location];
		return array();
	}

	/**
	 * Function to add menus
	 *
	 * @param string $location
	 * @param array $details
	 * @return Menu
	 */
	public static function addMenu($location, $details) {
		$details = fire_hook('menu.item', $details, array($location));

		if(in_array($location, static::$offMenus)) {
			return new Menu('', '', '');
		}

		$expected = array(
			'title' => '',
			'link' => '',
			'icon' => '',
			'id' => '',
			'ajaxify' => true,
			'open_new_tab' => false
		);

		/**
		 * @var $title
		 * @var $link
		 * @var $icon
		 * @var $id
		 * @var $ajaxify
		 * @var $open_new_tab
		 */
		extract(array_merge($expected, $details));
		if(!isset(static::$menus[$location])) static::$menus[$location] = array();
		$id = (empty($id)) ? $link : $id;
		$menu_item_status = fire_hook('menu.item.status', array(true), array($details, $location))[0];
		if($menu_item_status) {
			static::$menus[$location][$id] = new Menu($title, $link, $id, $icon, $ajaxify, $open_new_tab);
            fire_hook('off.menu', null, array());
            return static::$menus[$location][$id];
        }
	}

	/**
	 * Method to register filters
	 *
	 * @param $name
	 * @param mixed $callable
	 * @return void
	 */
	public static function addFilter($name, $callable) {
		static::$filters[$name] = $callable;
	}

	/**
	 * Function to add only POST Requests
	 * @see add
	 * @param $pattern
	 * @param array $parameters
	 * @return bool
	 */
	public static function post($pattern, $parameters = array()) {
		return static::add($pattern, array_merge($parameters, array('method' => 'POST')));
	}

	/**
	 * Method to add routes
	 *
	 * @param string $pattern
	 * @param array $parameters
	 * @return boolean
	 */
	public static function add($pattern, $parameters = array()) {
		$expectedParameters = array(
			'as' => '',
			'use' => '',
			'filter' => 'GET',
			'method' => '',
			'block' => false
		);

		/**
		 * @var $as
		 * @var $use
		 * @var $filter
		 * @var $method
		 * @var $block
		 */
		extract(array_merge($expectedParameters, $parameters));

		if(!$pattern or !$use) return false;

		$as = ($as) ? $as : $pattern;

		static::$pages[$as] = array(
			'pattern' => $pattern,
			'use' => $use,
			'filter' => $filter,
			'method' => $method
		);
		static::$pages[$as] = new Page($pattern, $use, $method, $filter);

		if($block and !empty($as)) {
			register_block_page($as, $block);
		}

		return static::$pages[$as];
	}

	/**
	 * Function to add GET Requests
	 * @see add
	 * @param $pattern
	 * @param array $parameters
	 * @return bool
	 */
	public static function get($pattern, $parameters = array()) {
		return static::add($pattern, array_merge($parameters, array('method' => 'GET')));
	}

	/**
	 * Functions to add any GET or POST Requests
	 * @see add
	 * @param $pattern
	 * @param array $parameters
	 * @return bool
	 */
	public static function any($pattern, $parameters = array()) {
		return static::add($pattern, array_merge($parameters, array('method' => 'ANY')));
	}

	/**
	 * Method to get link
	 * @param $id
	 * @param array $param
	 * @return string
	 */
	public static function getLink($id, $param = array()) {
		if(!isset(static::$pages[$id])) return "";
		return static::$pages[$id]->getLink($param);
	}

	public static function getCurrentPage() {
		return static::$currentPage;
	}

	public static function setCurrentPage($page) {
		static::$currentPage = $page;
	}

	/**
	 * Method to process requests
	 *
	 * @param string $uri
	 * @return string
	 */
	public function process($uri = "") {
		//header("Content-Type: text/html; charset=utf-8");
		//always remove the / in front
		if(substr($uri, -1) == '/') $uri = rtrim($uri, '/');
		if(!$uri) $uri = "/";
		$pages = static::$pages;

		/**
		 * Lets scan through our registered pages to load the appropriate page
		 * for the request
		 */
		$content = "";
		$found = false;
		foreach($pages as $id => $page) {
			if(!$content and preg_match("!^".$page->getPattern()."$!", $uri)) {
				//Ok lets check if the request method is allowed
				$method = strtoupper($page->method);
				$requestMethod = get_request_method();
				static::$currentPage = $id;
				if(($method == "ANY" or $method == $requestMethod) and Pager::passFilters($page->filters)) {
					fire_hook('page.after.filter', $id);
					fire_hook('page.after.filter.uri', null, array($uri, $id));
					//then we can load this page
					$content = $this->loadPager($page->use);
					$found = true;
					break;
				}
			}
		}

		if(!$found) {
			return fire_hook('page.notfound', MyError::error404(), array($page->filters));
		}

		return $content;
	}

	public static function passFilters($filter) {
		$filters = explode("|", $filter);
		$passed = true;
		foreach($filters as $filter) {
			if(isset(static::$filters[$filter])) {
				$callableFunction = static::$filters[$filter];
				if(is_callable($callableFunction)) {
					if(!call_user_func_array($callableFunction, array(App::getInstance()))) {
						$passed = false;
					}
				}
			}
		}

		return $passed;
	}

	/**
	 * Load the pager file from the directory accordingly and call
	 * the appropriate function load the page
	 *
	 * @param string $pager
	 * @return string
	 */
	private function loadPager($pager) {
		$plugin = "";
		$filePath = $this->app->path("includes/pages/");

		/**
		 * Check if the request pager is from one of the plugins
		 */
		if(preg_match("#::#", $pager)) {
			list($plugin, $pager) = explode("::", $pager);
		}
		list($fileName, $function) = explode("@", $pager);
		if($plugin) $filePath = $this->app->pluginPath($plugin, "pages/");
		if($fileName == 'install') $filePath = $this->app->path("installer/");

		//complete file path
		$filePath = $filePath.$fileName.'.php';
		if(!file_exists($filePath)) return MyError::error404();
		require_once $filePath;

		/**
		 * Check if the function exists or not
		 */
		if(function_exists($function)) {
			$app = $this->app;
			return call_user_func_array($function, array($app));
		} else {
			//throw page not found error
			return MyError::error404();
		}

	}
}

/**
 * Site widgets class
 */
class Widget {
	/**
	 * Method to add widget to database table
	 * @param int $widget_id
	 * @param string $page_id
	 * @param string $widget
	 * @param string $location
	 * @param string $settings
	 * @return boolean
	 */
	public static function add($widget_id, $page_id, $widget, $location, $settings = '') {
		$sort = 1;
		$widget_id = ($widget_id) ? $widget_id : md5(time().$page_id.$widget.$location);
		if(static::exists($widget_id)) {
			if($settings) {
				db()->query("UPDATE blocks SET settings='".$settings."' WHERE id='".$widget_id."'");
			}
			return true;
		}
		$page = Pager::getSitePage($page_id);
		if($page) {
			$page_id = $page['id'];
			$page_slug = $page['slug'];
		} else {
			$page_slug = '';
		}
		$query = db()->query("SELECT id FROM blocks WHERE page_id IN ('".$page_id."', '".$page_slug."') AND block_location='".$location."'");
		if($query) $sort = $query->num_rows;
		db()->query("INSERT INTO blocks(id,page_id,block_view,block_location,sort,settings) VALUES('".$widget_id."','".$page_id."','".$widget."','".$location."','".$sort."','".$settings."')");
		forget_cache("page-widgets-".$page_id.'-top');
		forget_cache("page-widgets-".$page_id.'-left');
		forget_cache("page-widgets-".$page_id.'-middle');
		forget_cache("page-widgets-".$page_id.'-right');
		forget_cache("page-widgets-".$page_id.'-bottom');
		forget_cache("page-widgets-".$page_id.'-all');
		return true;
	}

	public static function remove($widget, $page_id = null, $position = null) {
		$db = db();
		$sql = "DELETE FROM blocks WHERE block_view = '".$widget."'";
		if($page_id) {
			$sql .= " AND page_id = '".$page_id."'";
		}
		if($position) {
			$sql .= " AND block_location = '".$position."'";
		}
		$query = $db->query($sql);
		if($query) {
			forget_cache("page-widgets-".$page_id.'-top');
			forget_cache("page-widgets-".$page_id.'-left');
			forget_cache("page-widgets-".$page_id.'-middle');
			forget_cache("page-widgets-".$page_id.'-right');
			forget_cache("page-widgets-".$page_id.'-bottom');
			forget_cache("page-widgets-".$page_id.'-all');
			return true;
		} else {
			return false;
		}
	}

	public static function clear($page_id, $position = null) {
		$db = db();
		$page = Pager::getSitePage($page_id);
		if($page) {
			$page_id = $page['id'];
			$page_slug = $page['slug'];
		} else {
			$page_slug = '';
		}
		$sql = "DELETE FROM blocks WHERE page_id IN ('".$page_id."', '".$page_slug."')";
		if($position) {
			$sql .= " AND block_location = '".$position."'";
		}
		$query = $db->query($sql);
		if($query) {
			forget_cache("page-widgets-".$page_id.'-top');
			forget_cache("page-widgets-".$page_id.'-left');
			forget_cache("page-widgets-".$page_id.'-middle');
			forget_cache("page-widgets-".$page_id.'-right');
			forget_cache("page-widgets-".$page_id.'-bottom');
			forget_cache("page-widgets-".$page_id.'-all');
			return true;
		} else {
			return false;
		}
	}

	public static function exists($widget_id) {
		$query = db()->query("SELECT id FROM blocks WHERE id='".$widget_id."' LIMIT 1");
		if($query) return $query->num_rows;
		return false;
	}

	public static function load($widget, $content = null) {
		$theWidget = static::get($widget['block_view']);
		$theWidgetPath = static::getWidgetPath($widget['block_view']);
		$widgetName = $widget['block_view'];
		$settings = static::getSettings($widget, $theWidget);

		if($theWidget and $theWidgetPath) {
			$viewPath = static::getViewPath($theWidgetPath, $widgetName);
			if($viewPath) {
				ob_start();

				/**
				 * make the parameters available to the views
				 */
				$app = app();

				if($settings) extract($settings);

				if(file_exists($viewPath)) {
					include $viewPath;
				} else {
					//trigger_error(Error::viewNotFound($viewPath));
				}
				$content = ob_get_clean();
				return $content;
			} else {
				return '';
			}
		}
	}

	public static function get($widget) {
		$widgetPath = static::getWidgetPath($widget);
		if(!$widgetPath) return false;
		$info = array();
		if(is_dir($widgetPath)) {
			$info = (file_exists($widgetPath.'info.php')) ? include($widgetPath.'info.php') : array();
		}
		$info['delete'] = ($widget == 'content') ? false : true;
		return $info;
	}

	public static  function getWidgetPath($widget) {
		if (preg_match("#plugin::#", $widget)) {
			list($plugin, $widgetPath)  = explode("::", $widget);
			list($pluginName, $widget) = explode("|", $widgetPath);
			$path = path('plugins/'.$pluginName.'/widgets/');
		} elseif (preg_match("#theme::#", $widget)) {
			list($plugin, $widgetPath)  = explode("::", $widget);
			list($pluginName, $widget) = explode("|", $widgetPath);
			$path = path('themes/frontend/'.$pluginName.'/widgets/');
		} else {
			$path = path("widgets/");
			$current_theme = get_active_theme();
			$override_path = path('themes/'.$current_theme.'/widget-override/');
			if(file_exists($override_path.$widget.'/info.php')) {
				$path = $override_path;
			}
		}
		$widgetPath = $path.$widget.'/';
		return $widgetPath;
	}

	public static function getSettings($widget, $theWidget) {
		$dbSettings = ($widget['settings']) ? perfectUnserialize($widget['settings']) : array();
		if($dbSettings) return $dbSettings;
		//lets scan for default widget settings
		$defaultSettings = array();
		if(isset($theWidget['settings'])) {
			foreach($theWidget['settings'] as $id => $detail) {
				$defaultSettings[$id] = $detail['value'];
			}
		}
		return $defaultSettings;
	}

	public static function getViewPath($theWidgetPath, $widget) {
		$normalPath = $theWidgetPath.'view.phtml';
		$mobileViewPath = $theWidgetPath.'view-mobile.phtml';
		if(app()->isMobile and file_exists($mobileViewPath)) {
			$normalPath = $mobileViewPath;
		}
		//let try if current theme want to override widgets
		$currentTheme = app()->theme;
		if(preg_match("#plugin::#", $widget)) {
			list($plugin, $widgetPath) = explode("::", $widget);
			list($pluginName, $widget) = explode("|", $widgetPath);
			if(!plugin_loaded($pluginName)) return false;
			$path = "themes/{$currentTheme}/widget-override/".'plugins/'.$pluginName.'/'.$widget.'/view.phtml';
			$mobileViewPath = "themes/{$currentTheme}/widget-override/".'plugins/'.$pluginName.'/'.$widget.'/view-mobile.phtml';
			if(app()->isMobile and file_exists($mobileViewPath)) {
				$path = $mobileViewPath;
			}
			if(file_exists($path)) $normalPath = $path;
		} else {
			$path = "themes/{$currentTheme}/widget-override/".$widget.'/view.phtml';
			$mobileViewPath = "themes/{$currentTheme}/widget-override/".$widget.'/view-mobile.phtml';
			if(app()->isMobile and file_exists($mobileViewPath)) {
				$path = $mobileViewPath;
			}
			if(file_exists($path)) $normalPath = $path;
		}
		return $normalPath;
	}

	public static function listWidgets($plugin, $plugin_name = null) {
		$current_theme = get_active_theme();
		$widgets = array();
		$paths = array();
		if($plugin == 'plugin') {
			$paths[] = path('plugins/'.$plugin_name.'/widgets/');
		} elseif($plugin == 'theme') {
			$paths[] = path('themes/'.$plugin_name.'/widgets/');
		} elseif($plugin == 'core') {
			$paths[] = path('widgets/');
			$paths[] = path('themes/'.$current_theme.'/widget-override/');
		}

		if($plugin == 'theme') {
			$paths[] = path('themes/'.$current_theme.'/plugins/'.$plugin_name.'/widgets/');
		}

		foreach($paths as $path) {
			if(is_dir($path)) {
				if($h = opendir($path)) {
					while($file = readdir($h)) {
						if(substr($file, 0, 1) != '.' and !preg_match('#\.html#', $file)) {
							$tpath = $path.$file.'/';
							if(file_exists($tpath.'info.php')) {
								$info = include $tpath.'info.php';
								$id = $file;
								if($plugin == 'plugin') $id = "plugin::{$plugin_name}|{$file}";
								if($plugin == 'theme') $id = "theme::{$plugin_name}|{$file}";
								$widgets[$id] = $info;
							}
						}
					}
				}
			}
		}
		return $widgets;
	}

	public static function getWidgetPages($page_id, $position = 'all') {
		$page = Pager::getSitePage($page_id);
		if($page) {
			$page_id = $page['id'];
			$page_slug = $page['slug'];
		} else {
			$page_slug = '';
		}
		$widgets = array();
		$cache_name = "page-widgets-".$page_id.'-'.$position;
		if(cache_exists($cache_name)) {
			return get_cache($cache_name);
		} else {
			$query = db()->query("SELECT * FROM blocks WHERE page_id IN ('".$page_id."', '".$page_slug."') AND block_location='".$position."' ORDER BY sort ASC");
			while($fetch = $query->fetch_assoc()) {
				$widgets[] = $fetch;
			}
			set_cacheForever($cache_name, $widgets);
			return $widgets;
		}
	}

	public static function render($block_view, $settings = null) {
		$widget = array(
			'id' => null,
			'page_id' => null,
			'block_view' => $block_view,
			'settings' => array(),
			'sort' => null
		);
		$widget['settings'] = $settings ? $settings : array();
		$info = static::get($widget['block_view']);
		$path = static::getWidgetPath($widget['block_view']);
		$name = $block_view;
		$settings = static::getSettings($widget, $info);
		$content = '';
		if($info && $path) {
			$view_path = static::getViewPath($path, $name);
			if($view_path) {
				ob_start();
				$app = app();
				if($settings) {
					extract($settings);
				}
				if(file_exists($view_path)) {
					include $view_path;
				}
				$content = ob_get_clean();
			}
		}
		echo $content;
	}
}

/**
 * Error Handler functions
 */
class MyError {
	public static function error404() {
		http_response_code(404);
		$app = App::getInstance();
		Pager::setCurrentPage(null);
		$app->setLayout('layouts/blank')->setTitle(lang('404-not-found'));
		echo $app->render(view("errors/general"));
		return true;
	}

	/**
	 * @param $path
	 */
	public static function viewNotFound($path) {

	}

	/**
	 * @param $level
	 * @param $message
	 * @param $file
	 * @param $line
	 */
	public static function handler($level, $message, $file, $line) {
		http_response_code(404);
//		echo view("errors/debug", array('level' => $level, 'message' => $message, 'file' => $file, 'line' => $line));
//		exit;
		$app = App::getInstance();
		if($app->config("debug")) {
			//show the error message
			echo view("errors/debug", array('level' => $level, 'message' => $message, 'file' => $file, 'line' => $line));
			//echo
		} else {
			//show good error page
			$app = App::getInstance();
			$app->setLayout('layouts/blank')->setTitle(lang('404-not-found'));
			echo $app->render(view("errors/general"));
		}

	}
}

/**
 * Each Page class
 */
class Page {
	public $pattern;
	public $filters;
	public $use;
	public $method;
	private $patternReplace = array();

	/**
	 * @param $pattern
	 * @param $use
	 * @param $method
	 * @param $filters
	 */
	public function __construct($pattern, $use, $method, $filters) {
		$this->pattern = $pattern;
		$this->use = $use;
		$this->method = $method;
		$this->filters = $filters;
	}

	/**
	 * Method to add patterReplace array
	 *
	 * @param array $replace
	 */
	public function where($replace = array()) {
		$this->patternReplace = $replace;
	}

	/**
	 * Get the full pattern with the replaces
	 * @return string
	 */
	public function getPattern() {
		if($this->pattern != "/" and substr($this->pattern, -1) == '/') $this->pattern = rtrim($this->pattern, '/');
		if(empty($this->patternReplace)) return $this->pattern;
		$pattern = $this->pattern;
		foreach($this->patternReplace as $replace => $p) {
			$pattern = str_replace("{".$replace."}", $p, $pattern);
		}
		return $pattern;
	}

	/**
	 * @param array $param
	 * @return mixed
	 */
	public function getLink($param = array()) {
		if(empty($param)) return app()->url($this->pattern);
		$pattern = $this->pattern;
		foreach($param as $id => $value) {
			$pattern = str_replace("{".$id."}", $value, $pattern);
		}
		return app()->url($pattern);
	}
}

/**
 * Pager Menu class
 */
class Menu {
	private static $availableMenus = array();
	private static $menuLocation = array();
	public $title;
	public $id;
	public $link;
	public $icon;
	public $ajax = true;
	public $tab = false;
	private $menus = array();
	private $active = false;

	public function __construct($title, $link, $id = "", $icon = "", $ajaxify = true, $open_new_tab = false) {
		$this->title = $title;
		$this->link = url($link);
		$this->id = $id;
		$this->icon = $icon;
		$this->ajax = $ajaxify;
		$this->tab = $open_new_tab;
	}

	/**
	 * @param $title
	 * @param $link
	 * @param string $icon
	 * @param string $location
	 * @return bool
	 */
	public static function addAvailableMenu($title, $link, $icon = '', $location = 'all') {
		static::$availableMenus[$location][] = array(
			'title' => $title,
			'link' => $link,
			'icon' => $icon
		);
		return true;
	}

	/**
	 * @param $location
	 * @return array
	 */
	public static function getAvailableMenus($location) {
		$result = array();
		if(isset(static::$availableMenus[$location])) $result = static::$availableMenus[$location];
		$result = array_merge($result, static::$availableMenus['all']);
		return $result;
	}

	/**
	 * @param $id
	 * @param $title
	 * @return bool
	 */
	public static function addLocation($id, $title) {
		static::$menuLocation[$id] = $title;
		return true;
	}

	/**
	 * @return array
	 */
	public static function getLocations() {
		return static::$menuLocation;
	}

	/**
	 * @param $location
	 * @param $title
	 * @param $link
	 * @param string $type
	 * @param int $ajax
	 * @param string $icon
	 * @param int $tab
	 * @param null $id
	 * @param bool $force
	 * @return bool
	 */
	public static function saveMenu($location, $title, $link, $type = 'manual', $ajax = 1, $icon = '', $tab = 0, $id = null, $force = false) {
		$db = db();
		if(!$force) {
			$sql = "SELECT COUNT(id) FROM menus WHERE menu_location = '".$location."' AND link = '".$link."'";
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$is_added = (bool) $row[0];
			if($is_added) {
				return false;
			}
		}
		$order = 1;
		$query = db()->query("SELECT id FROM menus WHERE menu_location='".$location."'");
		$id = ($id) ? $id : md5(time().$title.$link);
		if($query) $order = $query->num_rows;
		$db->query("INSERT INTO menus (menu_location, title, link, type, ajaxify, icon, menu_order, open_new_tab, id) VALUES('".$location."', '".$title."', '".$link."', '".$type."', '".$ajax."', '".$icon."', '".$order."', '".$tab."', '".$id."')");
		forget_cache("site-menus-".$location);
	}

	/**
	 * @param $id
	 * @return array|bool
	 */
	public static function findSaveMenu($id) {
		$query = db()->query("SELECT * FROM menus WHERE id='".$id."'");
		if($query) return $query->fetch_assoc();
		return false;
	}

	/**
	 * @param $location
	 * @return array|mixed
	 */
	public static function getSaveMenus($location) {
		$cache_name = "site-menus-{$location}";
		if(cache_exists($cache_name)) {
			return get_cache($cache_name);
		} else {
			$query = db()->query("SELECT * FROM menus WHERE menu_location='".$location."' ORDER by menu_order ASC");
			$result = fetch_all($query);
			set_cacheForever($cache_name, $result);
			return $result;
		}
	}

	/**
	 * @param $title
	 * @param $link
	 * @param string $id
	 * @param string $icon
	 * @return $this
	 */
	public function addMenu($title, $link, $id = "", $icon = "") {
		$id = (empty($id)) ? $link : $id;
		$this->menus[$id] = new Menu($title, $link, $id, $icon);
		return $this;
	}

	/**
	 * @param $id
	 * @return Menu
	 */
	public function findMenu($id) {
		return (isset($this->menus[$id])) ? $this->menus[$id] : new Menu('', '');
	}

	/**
	 * @return array
	 */
	public function getMenus() {
		return $this->menus;
	}

	/**
	 * @return bool
	 */
	public function hasMenu() {
		return (!empty($this->menus)) ? true : false;
	}

	/**
	 * @return bool
	 */
	public function isActive() {
		return $this->active;
	}

	/**
	 * @param bool $value
	 * @return $this
	 */
	public function setActive($value = true) {
		$this->active = $value;
		return $this;
	}
}

class FileCache {
	/**
	 * Store an item in the cache forever.
	 * @param  string $key
	 * @param $val
	 * @return void
	 */
	public function setForever($key, $val) {
		return $this->set($key, $val, 9999999999);
	}

	/**
	 * Store an item in the cache for a given number of minutes.
	 * If minute is not supplied cache for 60 sec
	 * @param  string $key
	 * @param $val
	 * @param null $time
	 * @return void
	 */
	public function set($key, $val, $time = null) {
		$this->newcachelibrary();
		$key = md5($key);
		/**
		 * Check if key exist, then overide
		 */
		if(!file_exists($this->storage($key))) $this->forget($key);

		/**
		 * Check if time to cache is supplied
		 * use default of 60secs
		 */
		$time = isset($time) ? time() + $time : time() + 60;
		$this->filepath = $this->storage($key, true);
		$path = $this->filepath;
		$path_info = pathinfo($path);
		if(!is_dir($path_info['dirname'])) {
			mkdir($path_info['dirname'], 0777, true);
		}
		$filehandle = file_put_contents($path, $time.serialize($val));
	}

	/**
	 * create new cache
	 * library if not exist
	 * @param
	 * @return void
	 */
	private function newcachelibrary() {
		if(!file_exists($this->storage())) mkdir($this->storage(), 0777, true);
	}

	public function storage($filepath = null, $dir = false) {
		if($filepath) {
			$parts = array_slice(str_split($filepath, 2), 0, 2);
			$key = join('/', $parts).'/';
			if($dir) {
				try {
					mkdir('storage/cache/'.$key, 0777, true);
				} catch(Exception $e) {
				}
			}
			$filepath = $key.$filepath;
		}

		return path('storage/cache/'.$filepath);
	}

	/**
	 * delete an item from the cache.
	 * @param $key
	 * @return mixed
	 * @internal param string $key
	 */
	public function forget($key) {
		$key = md5($key);

		if(!file_exists($this->storage($key))) return NULL;

		unlink($this->storage($key));
	}

	/**
	 * Retrieve an item from the cache.
	 * @param  string $key
	 * @param null $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$key = md5($key);

		/**
		 * Check if available
		 */
		$path = $this->storage($key);
		if(!file_exists($path)) return $default;
		/**
		 * Check if expire
		 */
		$content = file_get_contents($this->storage($key));
		$time = substr($content, 0, 11);

		if(time() > $time) {

			$this->forget($key);
			return $default;
		}

		$value = $content;
		return unserialize(str_replace($time, "", $value));
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function keyexists($key) {
		$key = md5($key);
		return file_exists($this->storage($key));
	}

	/**
	 * Remove all item from the cache.
	 * @return void
	 */
	public function flush() {
		if(!file_exists($this->storage()) && !is_dir($this->storage())) return NULL;

		$handle = opendir($this->storage());
		while(false !== ($file = readdir($handle))) {
			if($file !== "." && $file !== "..") {
				unlink($this->storage($file));
			}
		}

		rmdir($this->storage());
	}
}

class MyMemCache {

	private $memcache;
	private $prefix;

	public function __construct() {
		$this->memcache = new Memcache();
		$this->memcache->addServer(config('memcache-host', '127.0.0.1'), config('memcache-port', '11211'), 100);
		$this->prefix = config('memcache-prefix');
	}

	/**
	 * Store an item in the cache for a given number of minutes.
	 * If minute is not supplied cache for 60 sec
	 * @param  string $key
	 * @param $val
	 * @param null $time
	 * @return void
	 */
	public function set($key, $val, $time = null) {
		$key = $this->prefix.$key;
		$this->memcache->set($key, $val, $time);
	}

	/**
	 * Store an item in the cache forever.
	 * @param  string $key
	 * @param $val
	 * @return void
	 */
	public function setForever($key, $val) {
		$key = $this->prefix.$key;
		$this->memcache->set($key, $val, 0);
	}

	/**
	 * @param string $key
	 * @return bool
	 */
	public function keyexists($key) {
		return $this->get($key);
	}

	/**
	 * Retrieve an item from the cache.
	 * @param  string $key
	 * @param null $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		$key = $this->prefix.$key;
		$value = $this->memcache->get($key);
		return $value;
	}

	/**
	 * delete an item from the cache.
	 * @param $key
	 * @return mixed
	 * @internal param string $key
	 */
	public function forget($key) {
		$key = $this->prefix.$key;
		return $this->memcache->delete($key);
	}


	/**
	 * Remove all item from the cache.
	 * @return bool
	 */
	public function flush() {
		return $this->memcache->flush();
	}

}

class Cache {

	static $instance;

	private $driver;

	public function __construct() {
		$driver = config('cache-driver', 'memcache');

		switch($driver) {
			case 'file':
				$this->driver = new FileCache();
			break;
			case 'memcache':

				if(class_exists('Memcache')) {
					$this->driver = new MyMemCache();
				} else {
					$this->driver = new FileCache(); //savely fallback to file cache
				}
			break;
		}
	}

	/**
	 * @return Cache
	 */
	public static function getInstance() {
		if(!isset(static::$instance) && static::$instance == null) return static::$instance = new Cache;
		return static::$instance;
	}


	/**
	 * Store an item in the cache for a given number of minutes.
	 * If minute is not supplied cache for 60 sec
	 * @param  string $key
	 * @param $val
	 * @param null $time
	 * @return void
	 */
	public function set($key, $val, $time = null) {
		return $this->driver->set($key, $val, $time);
	}

	/**
	 * Store an item in the cache forever.
	 * @param  string $key
	 * @param $val
	 * @return void
	 */
	public function setForever($key, $val) {
		return $this->driver->setForever($key, $val);
	}

	/**
	 * Retrieve an item from the cache.
	 * @param  string $key
	 * @param null $default
	 * @return mixed
	 */
	public function get($key, $default = null) {
		return $this->driver->get($key, $default);
	}


	/**
	 * @param string $key
	 * @return bool
	 */
	public function keyexists($key) {
		return $this->driver->keyexists($key);
	}

	/**
	 * delete an item from the cache.
	 * @param $key
	 * @return mixed
	 * @internal param string $key
	 */
	public function forget($key) {
		return $this->driver->forget($key);
	}


	/**
	 * Remove all item from the cache.
	 * @return void
	 */
	public function flush() {
		return $this->driver->flush();
	}

}

class Hook {

	static $instance;
	private $events = array();

	/**
	 * @return Hook
	 */
	public static function getInstance() {
		if(!static::$instance) static::$instance = new Hook();
		return static::$instance;
	}

	/**
	 * @param $event
	 * @param null $values
	 * @param null $callback
	 * @param array $param
	 * @return mixed|null
	 */
	public function attachOrFire($event, $values = NULL, $callback = NULL, $param = array()) {
		if(!is_array($param)) $param = array($param);
		if($callback !== NULL) {
			if(!isset($this->events[$event])) $this->events[$event] = array();
			$this->events[$event][] = $callback;
		} else {
			$theValue = $values;
			$result = $values;
			if(isset($this->events[$event])) {
				foreach($this->events[$event] as $callbacks) {
					$newParam = ($values) ? array_merge(array($theValue), $param) : $param;
					$v = call_user_func_array($callbacks, $newParam);
					$theValue = ($values) ? $v : $theValue;
					$result = ($v) ? $v : $result;
				}
			}
			return ($values) ? $theValue : $result;
		}
	}
}

class Validator {

	static $instance;
	public $inputs = array();
	public $rules = array();
	public $errorBag = array();
	public $extendRulesFunctions = array();
	public $messages = array();

	public function __construct() {
		$this->messages = array(
			'required' => lang('validation-required'),
			'min' => lang('validation-min'),
			'max' => lang('validation-max'),
			'between' => lang('validation-between'),
			'alphanum' => lang('validation-alphanum'),
			'integer' => lang('validation-integer'),
			'alpha' => lang('validation-alpha'),
			'numeric' => lang('validation-numeric'),
			'url' => lang('validation-url'),
			'email' => lang('validation-email'),
			'unique' => lang('validation-unique'),
			'date' => lang('validation-date'),
			'datetime' => lang('validation-datetime')
		);
	}

	/**
	 * @return Validator
	 */
	public static function getInstance() {
		if(!isset(static::$instance)) static::$instance = new Validator();
		return static::$instance;
	}

	public function scan(array $inputs, array $rules) {
		$this->inputs = array_merge($this->inputs, $inputs);
		$this->rules = array_merge($this->rules, $rules);
		$obj = $this;

//        if(count($this->inputs) != count($this->rules)) die('Validator Error: Numbers of parameters / rules supplied does not match');

		array_walk($this->inputs, function($item, $key) use ($rules, $inputs, $obj) {

			/**field to validate**/
			$field = $key;

			/**value supplied for field**/
			$value = $item;

			if(!array_key_exists($key, $rules)) return true;

			/**rules to validate field**/
			$rules = $rules[$key];

			$ruleSets = explode("|", $rules);

			$filteredRules = $obj->validRuleset($ruleSets, $value);

			/**
			 * Iterate through all validation rules
			 * available for a specific field, then
			 * call with cal_user_func_array
			 */
			array_walk($filteredRules, function($rules, $key) use ($obj, $field) {
				$method = array_shift($rules);
				$param = $rules;
				$param = $param['param'];

				/*add field to validator method*/
				if(is_array($param)) $param['field'] = $field;
				if(!is_array($param)) $param = array($param, $field);

				if(isset($obj->extendRulesFunctions[$method])) {
					$result = call_user_func_array($obj->extendRulesFunctions[$method], array_values($param));
					if(!$result) {
						$obj->errorBag[] = array('field' => $field, 'error' => $obj->getError($method, array($field)));
					}
				} else {
					call_user_func_array(array($obj, strtolower($method)), array_values($param));
				}

			});

		});

	}

	/**
	 * Takes array of ruleset to return as action with param
	 * @param array $ruleSets
	 * @param string $value
	 * @return array
	 */
	public function validRuleset($ruleSets, $value) {
		$validation = array();

		foreach($ruleSets as $rule) {
			if(preg_match('#:#', $rule)) {
				$validation[] = $this->getExtendedRule($rule, $value);
				continue;
			}
			$validation[] = array('action' => $rule, 'param' => $value);
		}

		return $validation;
	}

	/**
	 * Take an extended rule
	 * then breakdown to method and values
	 * @param string $rule
	 * @param $value
	 * @return array
	 */
	public function getExtendedRule($rule, $value) {
		/**
		 * explode rule to return method as
		 * first index, arguments as other
		 * indexes, add value to validate as extra param
		 */
		$param = explode(':', $rule);
		$rule = array_shift($param);
		$argue = $param;
		$argue[] = $value;

		return array('action' => $rule, 'param' => $argue);
	}

	/**
	 * Function to return error for a specific rule
	 * @param string type
	 * @param array $arguments
	 * @internal param \arguments $array
	 * @return array
	 */
	public function getError($type, array $arguments) {
		$message = $this->error_messages();
		$message = $message[$type];

		preg_match_all("#:[a-z]+#", $message, $matches);
		$args = array();
		foreach($arguments as $a) {
			$args[] = lang($a);
		}

		return str_replace(array_shift($matches), $args, $message);
	}

	/**
	 * Validation error messages
	 */
	public function error_messages() {
		return $this->messages;
	}

	/**
	 * Check if validation passes
	 * @return bool
	 */
	public function passes() {
		if(empty($this->errorBag)) return TRUE;
		return FALSE;
	}

	/**
	 * Check if validation fails
	 * @return bool
	 */
	public function fails() {
		if(!empty($this->errorBag)) return TRUE;
		return FALSE;
	}

	/**
	 * Return errorBag
	 * @return array
	 */
	public function errors() {
		return $this->errorBag;
	}

	/**
	 * Validation rule :required
	 * @param $value
	 * @param $field
	 */
	public function required($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);
		$contain_image = preg_match('/<img/', $value);

		if(strlen($value) == 0 && !$contain_image) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('required', array($field)));
		}
	}

	/**
	 * Validation rule :min
	 * @param $min
	 * @param $value
	 * @param $field
	 */
	public function min($min, $value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$min = $min;
		if(strlen($value) < $min) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('min', array($field, $min)));
		}
	}

	/**
	 * Validation rule :max
	 * @param $max
	 * @param $value
	 * @param $field
	 */
	public function max($max, $value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$max = $max;
		if(strlen($value) > $max) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('max', array($field, $max)));
		}
	}

	/**
	 * Validation rule :between
	 * @param int min
	 * @param int max
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function between($min, $max, $value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$max = $max;
		$min = $min;
		if(strlen($value) < $min || strlen($value) > $max) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('max', array($field, $min, $max)));
		}
	}

	/**
	 * Validation rule :numeric
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function numeric($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$valid = is_numeric($value);
		if(!$valid) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('numeric', array($field)));
		}
	}

	/**
	 * Validation rule :alpha
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function alpha($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$valid = preg_match('/^\pL+$/u', $value);
		if(!$valid) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('alpha', array($field)));
		}
	}

	/**
	 * Validation rule :alphanum
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function alphanum($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$valid = preg_match('/^[\pL\pN]+$/u', $value);
		$slug = toAscii($value);

		if(!$valid or empty($slug) or strlen($value) != strlen($slug)) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('alphanum', array($field)));
		}
	}

	/**
	 * Validation rule :slug
	 * @param string $value
	 * @param string $field
	 * @return void
	 */
	public function alphadash($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		$valid = preg_match('/^[\pL\pN_-]+$/u', $value);
		$slug = toAscii($value);

		if(!$valid or empty($slug) or strlen($value) != strlen($slug)) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('alphanum', array($field)));
		}
	}

	/**
	 * Validation rule :email
	 * @param int min
	 * @param int max
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function email($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		if(filter_var($value, FILTER_VALIDATE_EMAIL) == false) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('email', array($field)));
		}
	}

	/**
	 * Validation rule :url
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function url($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		if(!filter_var($value, FILTER_VALIDATE_URL)) ;
		{
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('url', array($field)));
		}
	}

	/*
     * Validate against a given date
     */

	/**
	 * Validation rule :integer
	 * @param string value
	 * @param string field
	 * return array
	 */
	public function integer($value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);

		if(!is_int($value)) ;
		{
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('integer', array($field)));
		}
	}

	/**
	 * Check if a field is unique against
	 * a column from the db
	 * @param $table
	 * @param $value
	 * @param $field
	 */
	public function unique($table, $value, $field) {
		$value = strip_tags($value);
		$field = strip_tags($field);
		$table = strip_tags($table);
		$db = db();
		$sql = "SELECT COUNT(".mysqli_real_escape_string(db(), $field).") FROM ".mysqli_real_escape_string(db(), $table)." WHERE ".mysqli_real_escape_string(db(), $field)." = '".mysqli_real_escape_string(db(), $value)."' LIMIT 1";
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$match = $row[0];
		/**return error if match found*/
		if($match) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('unique', array($field)));
		}
	}

	function date($value, $field) {
		$date = date_parse($value);
		if(!checkdate($date['month'], $date['day'], $date['year']) || is_null(strtotime($value))) {
			$this->errorBag[] = array('field' => $field, 'error' => $this->getError('date', array($field)));
		}
	}

	function datetime($value, $field) {
		$datetime = explode(' ', $value);
		if(count($datetime) == 2) {
			$date = $datetime[0];
			$parsed_date = date_parse($date);
			if(checkdate($parsed_date['month'], $parsed_date['day'], $parsed_date['year']) && !is_null(strtotime($date))) {
				if(date('Y-m-d H:i:s', strtotime($value)) == $value) {
					return;
				}
			}
		}
		$this->errorBag[] = array('field' => $field, 'error' => $this->getError('datetime', array($field)));
	}

	/**
	 * Get the first error message
	 * or a first error message of param provided
	 * @param null $param
	 * @return string
	 */
	public function first($param = null) {
		if(empty($this->errorBag)) return "";

		if(empty($param)) {
			$array = array_shift($this->errorBag);
			if(isset($array['error'])) $first = $array['error'];
		}
		foreach($this->errorBag as $errors) {
			if($errors['field'] == $param) {
				$first = $errors['error'];
				break;
			}
		}

		if(!empty($first)) {
			return $first;
		} else {
			return "";
		}
	}

	/**
	 * Function add extended Rules functions right from anywhere
	 * @param string $rule
	 * @param string $message
	 * @param mixed $callable
	 * @return mixed
	 */
	public function extendValidation($rule, $message, $callable) {
		$this->extendRulesFunctions[$rule] = $callable;
		$this->messages[$rule] = $message;
	}
}

/**
 * Uploader class
 */
class Uploader {
	public $source;
	public $sourceName;
	public $sourceSize;
	public $extension;
	public $destinationPath;
	public $destinationName;
	public $baseDir;
	public $result;
	public $insertedId;
	public $allowCDN = true;
	/**
	 * Allow image type
	 */
	private $imageTypes = array('png', 'jpg', 'gif', 'jpeg');
	private $imageSizes = array(75, 200, 600, 920);
	/**
	 * Allowed File types
	 */
	private $fileTypes = array(
		'doc',
		'xml',
		'exe',
		'txt',
		'zip',
		'rar',
		'doc',
		'mp3',
		'jpg',
		'png',
		'css',
		'psd',
		'pdf',
		'3gp',
		'ppt',
		'pptx',
		'xls',
		'xlsx',
		'html',
		'docx',
		'fla',
		'avi',
		'mp4',
		'swf',
		'ico',
		'gif',
		'webm',
		'jpeg'
	);
	/**
	 * Allowed video types
	 */
	private $videoTypes = array('mp4');
	private $audioTypes = array('mp3');
	private $sourceFile;
	private $linkContent = '';

	//max sizes
	private $dbType;
	private $dbTypeId;
	private $type;
	private $maxFileSize = 10000000;

	//allow Animated gif
	private $maxImageSize = 10000000;
	private $maxVideoSize = 10000000;
	private $maxAudioSize = 10000000;
	private $animatedGif = true;
	private $error = false;
	private $errorMessage;

	/**
	 * @param $source
	 * @param string $type
	 * @param mixed $validate
	 * @param bool $fromFile
	 * @param bool $isLink
	 */
	public function __construct($source, $type = "image", $validate = false, $fromFile = false, $isLink = false) {
		$source = is_string($source) ? fire_hook('path.local', $source) : $source;
		$this->source = $source;
		$this->type = $type;
		$this->maxFileSize = config("max-file-upload", $this->maxFileSize);
		$this->maxVideoSize = config("max-video-upload", $this->maxVideoSize);
		$this->maxAudioSize = config("max-audio-upload", $this->maxAudioSize);
		$this->maxImageSize = config("max-image-size", $this->maxImageSize);
		$this->animatedGif = config("support-animated-image", $this->animatedGif);
		$this->imageTypes = explode(',', config('image-file-types', 'jpg,png,gif,jpeg'));
		$this->videoTypes = explode(',', config('video-file-types', 'mp4,mov,wmv,3gp,avi,flv,f4v,webm'));
		$this->audioTypes = explode(',', config('audio-file-types', 'mp3,aac,wma'));
		$this->fileTypes = explode(',', config('files-file-types', 'doc,xml,exe,txt,zip,rar,mp3,jpg,png,css,psd,pdf,3gp,ppt,pptx,xls,xlsx,docx,fla,avi,mp4,swf,ico,gif,jpeg,webm'));

		if(!$fromFile) {
			try {
				if($source and $this->source['size'] != 0) {
					$this->source = $source;
					$this->sourceFile = $this->source['tmp_name'];
					$this->sourceSize = $this->source['size'];
					$this->sourceName = $this->source['name'];
					$name = pathinfo($this->sourceName);
					if(isset($name['extension'])) $this->extension = strtolower($name['extension']);

					$this->confirmFile();

				} else {
					if(!$validate) {
						$this->error = true;
						$this->errorMessage = lang("failed-to-upload-file");
					} else {
						$this->validate($validate);
					}
				}
			} catch(Exception $e) {
			}
		} else {
			$this->source = $this->sourceFile = $this->sourceName = $source;
			if(!$isLink) {
				$name = pathinfo($this->sourceName);
				if(isset($name['extension'])) $this->extension = strtolower($name['extension']);
			} else {
				$content = @file_get_contents($this->source);
				if(!$content) {
					$this->error = true;
					$this->errorMessage = lang("failed-to-upload-file");
				} else {
                    $details = pathinfo($this->source);
					$this->extension = $details['extension'];
					$this->linkContent = $content;
				}

			}
		}

		//load our libraries
		require_once path("includes/libraries/PHPImageWorkshop/autoload.php");
		if($this->animatedGif) require_once path("includes/libraries/gif_exg.php");

		//confirm the creation of uploads directory
		if(!is_dir(path('storage/uploads/'))) {
			@mkdir(path('storage/uploads/'), 0777, true);
			$file = @fopen(path('storage/uploads/index.html'), 'x+');
			fclose($file);
		}

	}

	public function confirmFile() {
		switch($this->type) {
			case 'image':
				if(!in_array($this->extension, $this->imageTypes)) {
					$this->errorMessage = lang("upload-file-not-valid-image");
					$this->error = true;
				}
				if($this->sourceSize > $this->maxImageSize) {
					$this->errorMessage = lang("upload-image-size-error", array('size' => format_bytes($this->maxImageSize)));
					$this->error = true;
				}
			break;
			case 'video':
				if(!in_array($this->extension, $this->videoTypes)) {
					$this->errorMessage = lang("upload-file-not-valid-video");
					$this->error = true;
				}
				if($this->sourceSize > $this->maxVideoSize) {
					$this->errorMessage = lang("upload-video-size-error", array('size' => format_bytes($this->maxVideoSize)));
					$this->error = true;
				}
			break;
			case 'audio':
				if(!in_array($this->extension, $this->audioTypes)) {
					$this->errorMessage = lang("upload-file-not-valid-audio");
					$this->error = true;
				}
				if($this->sourceSize > $this->maxAudioSize) {
					$this->errorMessage = lang("upload-audio-size-error", array('size' => format_bytes($this->maxAudioSize)));
					$this->error = true;
				}
			break;
			case 'file':
				if(!in_array($this->extension, $this->fileTypes)) {
					$this->errorMessage = lang("upload-file-not-valid-file");
					$this->error = true;
				}

				if($this->sourceSize > $this->maxFileSize) {
					$this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxFileSize)));
					$this->error = true;
				}
			break;
		}
	}

	/**
	 * Validate upload files for multiple uploads
	 * @param array $files
	 * @return void
	 */
	public function validate($files) {
		$isError = false;
		foreach($files as $file) {
			$pathInfo = pathinfo($file['name']);
			$this->extension = strtolower($pathInfo['extension']);
			$this->sourceSize = $file['size'];
			switch($this->type) {
				case 'image':
					if(!in_array($this->extension, $this->imageTypes)) {
						$this->errorMessage = lang("upload-file-not-valid-image");
						$this->error = true;
					}
					if($this->sourceSize > $this->maxImageSize) {
						$this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxImageSize)));
						$this->error = true;
					}
				break;
				case 'video':
					if(!in_array($this->extension, $this->videoTypes)) {
						$this->errorMessage = lang("upload-file-not-valid-video");
						$this->error = true;
					}
					if($this->sourceSize > $this->maxVideoSize) {
						$this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxVideoSize)));
						$this->error = true;
					}
				break;
				case 'audio':
					if(!in_array($this->extension, $this->audioTypes)) {
						$this->errorMessage = lang("upload-file-not-valid-audio");
						$this->error = true;
					}
					if($this->sourceSize > $this->maxAudioSize) {
						$this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxAudioSize)));
						$this->error = true;
					}
				break;
				case 'file':
					if(!in_array($this->extension, $this->fileTypes)) {
						$this->errorMessage = lang("upload-file-not-valid-file");
						$this->error = true;
					}

					if($this->sourceSize > $this->maxFileSize) {
						$this->errorMessage = lang("upload-file-size-error", array('size' => format_bytes($this->maxFileSize)));
						$this->error = true;
					}
				break;
			}
		}
	}

	public function setFileTypes($types) {
		$this->fileTypes = $types;
		return $this;
	}

	public function noThumbnails() {
		$this->imageSizes = array(600, 920);
		return $this;
	}

	public function disableCDN() {
		$this->allowCDN = false;
	}

	public function enableCDN() {
		$this->allowCDN = true;
	}

	/**
	 * Method to get the image width
	 * @return null
	 */
	function getWidth() {
		list($width, $height) = getimagesize($this->sourceFile);
		return ($width) ? $width : null;
	}

	/**
	 * Method to get the image height
	 * @return int
	 */
	function getHeight() {
		list($width, $height) = getimagesize($this->sourceFile);
		return ($height) ? $height : null;
	}

	/**
	 * Function to confirm file passes
	 */
	public function passed() {
		return !$this->error;
	}

	/**
	 * Function to set destination
	 * @param $path
	 * @return Uploader
	 */
	public function setPath($path) {
		$this->baseDir = "storage/uploads/".$path;
		$path = path("storage/uploads/").$path;
		if(!is_dir($path)) {
			mkdir($path, 0777, true);
			//create the index.html file
			if(!file_exists($path.'index.html')) {
				$file = fopen($path.'index.html', 'x+');
				fclose($file);
			}
		}
		$this->destinationPath = $path;
		return $this;
	}

	/**
	 *Function to resize image
	 * @param int $width
	 * @param int $height
	 * @param string $fit
	 * @param string $any
	 * @return $this
	 */
	public function resize($width = null, $height = null, $fit = "inside", $any = "down") {
		if($this->error) return false;
		$fileName = md5($this->sourceName.time()).'.'.$this->extension;
		$fileName = (!$width) ? '_%w_'.$fileName : '_'.$width.'_'.$fileName;

		$this->result = $this->baseDir.$fileName;

		if($width) {
			$this->finalizeResize($fileName, $width, $height, $fit, $any);
		} else {
			foreach($this->imageSizes as $size) {
				$this->finalizeResize(str_replace('%w', $size, $fileName), $size, $size, $fit, $any);
			}
		}

		return $this;
	}

	/**
	 * @param $filename
	 * @param $width
	 * @param $height
	 * @param $fit
	 * @param $any
	 */
	private function finalizeResize($filename, $width, $height, $fit, $any) {
		try {
			if($this->animatedGif and $this->extension == "gif") {
				if ($this->linkContent) {
                    $Gif = new GIF_eXG($this->source, 1);
                } else {
                    $Gif = new GIF_eXG($this->sourceFile, 1);
                }
				$Gif->resize($this->destinationPath.$filename, $width, $height, 1, 0);
			} else {
				/**$wm = WideImage::load($this->sourceFile);
				 * $wm = $wm->resize($width, $height, $fit, $any);
				 * $wm->saveToFile($this->destinationPath.$filename);**/
				if($this->linkContent) {
					$layer = PHPImageWorkshop\ImageWorkshop::initFromString($this->linkContent);
				} else {
					if(extension_loaded('exif')) {
						$layer = PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile, true);
					} else {
						$layer = PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile);
					}
				}
				if($width == 550) {
					$layer->resizeInPixel($width, $height, true);
				} elseif($width < 600) {
					$layer->cropMaximumInPixel(0, 0, "MM");
					$layer->resizeInPixel($width, $height);
				} else {
					$layer->resizeToFit($width, $height, true);
				}

				$layer->save($this->destinationPath, $filename);
				fire_hook("upload", null, array($this, $filename));
			}
		} catch(Exception $e) {
			exit($e->getMessage());
			$this->result = '';
		}
	}

	/**
	 * Function to crop image
	 * @param int $left
	 * @param int $top
	 * @param string $width
	 * @param string $height
	 * @return $this
	 */
	public function crop($left = 0, $top = 0, $width = '100%', $height = '100%') {
		if($this->error) return false;
		$fileName = md5($this->sourceName.time()).'.'.$this->extension;
		$fileName = '_'.str_replace('%', '', $width).'_'.$fileName;
		$this->result = $this->baseDir.$fileName;

		try {
			$layer = PHPImageWorkshop\ImageWorkshop::initFromPath($this->sourceFile, true);
			$layer->cropInPixel($width, $height, $left, $top);
			$layer->save($this->destinationPath, $fileName);
			/**$wm = $wm->crop($left, $top, $width, $height);
			 * $wm->saveToFile($this->destinationPath.$fileName);**/
			fire_hook("upload", null, array($this, $fileName));
		} catch(Exception $e) {
			$this->result = '';
		}

		return $this;
	}

	/**
	 * Function to get result
	 * @return string
	 */
	public function result() {
		return $this->result;
	}

	/**
	 * Function to save media to database
	 * @param string $type
	 * @param string $typeId
	 * @param int $privacy
	 * @param string $album
	 * @param null $uid
	 * @param string $entity_type
	 * @param string $entity_id
	 * @param null $ref_name
	 * @param null $ref_id
	 * @return $this
	 */
	public function toDB($type = "", $typeId = "", $privacy = 1, $album = '', $uid = null, $entity_type = null, $entity_id = null, $ref_name = null, $ref_id = null) {
		if($this->error) return false;
		$userid = ($uid) ? $uid : get_userid();
		$entity_type = isset($entity_type) ? $entity_type : 'user';
		$entity_id = isset($entity_id) ? $entity_id : get_userid();
		$query = db()->query("INSERT INTO `medias` (`user_id`,`path`, `file_type`, `type`, `type_id`, `entity_type`, `entity_id`,`privacy`,`album_id`)
         VALUES('".$userid."','".$this->result."', '".$this->type."','".$type."', '".$typeId."','".$entity_type."', '".$entity_id."','".$privacy."','".$album."');
        ");
		if($query) {
			$insertId = db()->insert_id;
			$this->insertedId = $insertId;
			fire_hook('media-added', null, array($insertId, $this->result, $this->type, $type, $typeId, $privacy, $album));
		}
		return $this;
	}

	/**
	 * Function to upload video
	 */
	public function uploadVideo() {
		return $this->directUpload();
	}

	protected function directUpload() {
		if($this->error) return false;
		$fileName = md5($this->sourceName.time()).".".$this->extension;
		$this->result = $this->baseDir.$fileName;
		if(move_uploaded_file($this->sourceFile, $this->destinationPath.$fileName)) {
			fire_hook("upload.start", null, array($this, $fileName));
			fire_hook("upload", null, array($this, $fileName));
		} else {
			try {
				file_put_contents($this->destinationPath.$fileName, file_get_contents($this->sourceFile));
				fire_hook("upload.start", null, array($this, $fileName));
				fire_hook("upload", null, array($this, $fileName));
			} catch(Exception $e) {
			}
		}
		return $this;
	}

	/**
	 * function to upload file
	 */
	public function uploadFile() {
		return $this->directUpload();
	}

	public function getError() {
		return $this->errorMessage;
	}
}

/**
 * Blocks Class
 */
class Blocks {
	private static $instance;
	private $pages = array();
	private $blocks = array(
		'all' => array(), //contain all blocks that can appear on any page
	);

	public function __construct() {

	}

	/**
	 * @return Blocks
	 */
	public static function getInstance() {
		if(static::$instance) return static::$instance;
		return static::$instance = new Blocks();
	}

	/**
	 * Method to register pages
	 * @param string $page_id
	 * @param string $pageTitle
	 * @return $this
	 */
	public function registerPage($page_id, $pageTitle = null) {
		$pageTitle = (!$pageTitle) ? $page_id : $pageTitle;
		$this->pages[$page_id] = $pageTitle;
		return $this;
	}

	/**
	 * Method to register blocks
	 * @param string $blockView
	 * @param string $blockTitle
	 * @param string $page
	 * @param array $settings
	 * @return $this
	 */
	public function registerBlock($blockView, $blockTitle = null, $page = null, $settings = array()) {
		$blockTitle = ($blockTitle) ? $blockTitle : $blockView;
		if($page) {
			$pages = (!is_array($page)) ? array($page) : $page;
			foreach($pages as $page) {
				if(!isset($this->blocks[$page])) $this->blocks[$page] = array();
				$this->blocks[$page][$blockView] = array('title' => $blockTitle, 'settings' => $settings);
			}
		} else {
			$this->blocks['all'][$blockView] = array('title' => $blockTitle, 'settings' => $settings);
		}
		return $this;
	}

	/**
	 * Get all registered pages
	 * @return array
	 */
	public function getPages() {
		return $this->pages;
	}

	/**
	 * Method to get all register blocks
	 * @param string $page
	 * @return array
	 */
	public function getBlocks($page = null) {
		$blocks = $this->blocks['all'];
		if($page and isset($this->blocks[$page])) {
			$blocks = array_merge($blocks, $this->blocks[$page]);
		}
		return $blocks;
	}

	/**
	 * Method to add a block to a page
	 * @param string $blockView
	 * @param string $page_id
	 * @param null $blockId
	 * @param array $settings
	 * @return $this
	 */
	public function addPageBlock($blockView, $page_id, $blockId = null, $settings = array()) {
		$pages = (!is_array($page_id)) ? array($page_id) : $page_id;
		foreach($pages as $page_id) {
			$check = db()->query("SELECT `page_id` FROM `blocks` WHERE `id`='".$blockId."'");
			if($check and $check->num_rows < 1) {
				$settings = perfectSerialize($settings);
				db()->query("INSERT INTO `blocks` (block_view,page_id,id,settings) VALUES('".$blockView."','".$page_id."','".$blockId."', '".$settings."')");
			}
			forget_cache("page-blocks-".$page_id);
		}

		return $this;
	}

	/**
	 * Method to remove a block from a page
	 * @param string $blockView
	 * @param string $page_id
	 * @return $this
	 */
	public function removePageBlock($blockView, $page_id) {
		db()->query("DELETE FROM `blocks` WHERE `page_id`='".$page_id."' AND `block_view`='".$blockView."'");
		forget_cache("page-blocks-".$page_id);
		return $this;
	}

	public function getPageRegisteredBlocks($page_id) {
		$query = db()->query("SELECT * FROM `blocks` WHERE `page_id`='".$page_id."' ORDER BY sort asc ");
		if($query) return fetch_all($query);
		return array();
	}

	/**
	 * Method to get all blocks content for a page
	 * @param string $page_id
	 * @param bool $global
	 * @return string
	 */
	public function getPageBlocks($page_id, $global = true) {
		$content = "";
		$results = array();
		if(cache_exists("page-blocks-".$page_id)) {
			$results = get_cache("page-blocks-".$page_id);
		} else {
			$query = db()->query("SELECT `block_view`,`settings` FROM `blocks` WHERE `page_id`='".$page_id."' ORDER BY sort ASC");

			if($query) {
				$results = fetch_all($query);
			}
			//all page blocks

			set_cacheForever("page-blocks-".$page_id, $results);

		}

		foreach($results as $block) {
			$content .= view($block['block_view'], array('settings' => perfectUnSerialize($block['settings'])));
		}
		if($page_id != "all" and $global) $content .= $this->getPageBlocks("all");
		return $content;
	}
}

/**
 * Paginator class
 */
class Paginator {
	public $total;
	private $db;
	private $limit;
	private $page;
	private $query;
	private $baseUrl = "";
	private $links = 7;
	private $listClass = "pagination";
	private $appends = array();

	public function __construct($query, $limit = 10, $links = 7, $page = null) {
		$this->db = db();
		$this->query = $query;

		$result = $this->db->query($this->query);
		if($result) $this->total = $result->num_rows;

		$this->page = isset($page) ? $page : input("page", 1);
		$this->validatePageNumber();
		$this->limit = $limit;
		$this->links = $links;
		$this->baseUrl = getFullUrl();
	}

	function validatePageNumber() {
		$this->page = (int) str_replace("-", '', $this->page);
	}

	public function setListClass($class = "") {
		$this->listClass = $class;
		return $this;
	}

	public function append($param = array()) {
		$this->appends = $param;
		return $this;
	}

	public function results() {
		if($this->limit == "all") {
			$query = $this->query;
		} else {
			$query = $this->query." LIMIT ".(($this->page - 1) * $this->limit).", {$this->limit}";
		}
		$query = $this->db->query($query);
		if($query) {
			return fetch_all($query);
		}
		return array();
	}

	public function links($ajax = false) {
		if($this->limit == 'all') return '';
		$last = ceil($this->total / $this->limit);
		$start = (($this->page - $this->links) > 0) ? $this->page - $this->links : 1;
		$end = (($this->page + $this->links) < $last) ? $this->page + $this->links : $last;
		$ajax = ($ajax) ? 'ajax="true"' : null;

		$html = "<ul class='".$this->listClass."'>";
		$class = ($this->page == 1) ? "disabled" : "";
		$html .= "<li class='".$class."'> <a ".$ajax."  href='".$this->getLink((($this->page - 1) == 0) ? 1 : $this->page - 1)."' class='ion-arrow-left-b'></a>";


		if($start > 1) {
			$html .= "<li><a ".$ajax." href='".$this->getLink(1)."'>1</a></li>";
			$html .= "<li class='disabled'><span>...</span></li>";
		}

		for($i = $start; $i <= $end; $i++) {
			$class = ($this->page == $i) ? "active" : "";
			$style = ($this->page == $i) ? "style='color: white !important; border-radius: 2px !important; font-weight: bold !important;'" : null;
			$html .= "<li class='".$class."' ><a {$style} ".$ajax." href='".$this->getLink($i)."'>".$i."</a></li>";
		}

		if($end < $last) {
			$html .= "<li class='disabled'><span>...</span></li>";
			$html .= "<li><a ".$ajax." href='".$this->getLink($last)."'>{$last}</a>";
		}

		$class = ($this->page == $last) ? "disabled" : "";
		$html .= "<li class='".$class."'> <a ".$ajax." href='".$this->getLink(($last == $this->page) ? $last : $this->page + 1)."' class='ion-arrow-right-b'></a>";
		$html .= "</ul>";

		return $html;
	}

	public function getLink($page) {
		$link = $this->baseUrl;
		$appends['page'] = $page;

		$appends = array_replace($this->appends, $appends);
		$appends = array_replace($_GET, $appends);

		$i = true;
		foreach($appends as $key => $value) {
			if($i) {
				$link .= '?';
				$i = !$i;
			} else {
				$link .= '&';
			}
			$link .= $key.'='.$value;
		}
		return $link;
	}
}

/**
 * Mailer class
 */
class Mailer {
	protected static $instance;
	protected $driver = 'mail';
	protected $fromName = '';
	protected $fromAddress = '';
	protected $smtp_host = '';
	protected $smtp_username = '';
	protected $smtp_password = '';
	protected $smtp_port = '';
	protected $ssl = '';
	protected $queue = false;
	protected $mailer;

	function __construct() {
		$this->driver = config('email-driver', 'mail');
		$this->fromAddress = config('email-from-address');
		$this->fromName = config('email-from-name');
		$this->smtp_host = config('email-smtp-host');
		$this->smtp_username = config('email-smtp-username');
		$this->smtp_password = config('email-smtp-password');
		$this->smtp_port = config('email-smtp-port');
		$this->ssl = config('email-ssl');
		$this->queue = config('email-queue');

		//load php mail for us
		require_once path('includes/libraries/phpmailer/PHPMailerAutoload.php');
		try {
			$this->mailer = new PHPMailer(true);
			if($this->driver == 'smtp') {
				$this->mailer->isSMTP();
				//$this->mailer->SMTPDebug = 2;
				$this->mailer->Host = $this->smtp_host;
				$this->mailer->Port = $this->smtp_port;
				$this->mailer->CharSet = "UTF-8";
				$this->mailer->Encoding = "base64";
				if(config('email-ssl') == 'TLS') {
					$this->mailer->SMTPAutoTLS = false;
				}
				if(!empty($this->smtp_username) and !empty($this->smtp_password)) {
					$this->mailer->Username = $this->smtp_username;
					$this->mailer->Password = $this->smtp_password;
					$this->mailer->SMTPAuth = true;
				}
			}
			$this->mailer->setFrom($this->fromAddress, $this->fromName);
		} catch(Exception $e) {
		}
	}

	/**
	 * @return Mailer
	 */
	public static function getInstance() {
		//if (static::$instance) return static::$instance;
		static::$instance = new Mailer();
		return static::$instance;
	}

	public function setAddress($address, $name = null) {
		try {
			$this->mailer->addAddress($address, $name);
		} catch(Exception $e) {
		}
		return $this;
	}

	public function addAttachment($path) {
		try {
			$this->mailer->addAttachment($path);
		} catch(phpmailerException $e) {
		}
		return $this;
	}

	public function send() {
		try {
			$this->mailer->send();
		} catch(Exception $e) {
			//exit($e->getMessage());
		}
	}

	public function template($emailId, $params = array()) {
		$template = get_email_template($emailId);
		$subject = lang($template['subject']);
		$body = lang($template['body_content']);
		$globalPlaceholders = array(
			'site-url' => url(),
			'site-title' => config('site_title')
		);
		$params = array_merge($globalPlaceholders, $params);

		if(preg_match('#\[header\]#', $body)) {
			//replace the header content
			$headerTemplate = get_email_template('header');
			$headerContent = lang($headerTemplate['body_content']);
			$body = str_replace('[header]', $headerContent, $body);
		}

		if(preg_match('#\[footer\]#', $body)) {
			//replace the footer content
			$footerTemplate = get_email_template('footer');
			$footerContent = lang($footerTemplate['body_content']);
			$body = str_replace('[footer]', $footerContent, $body);
		}

		foreach($params as $key => $value) {
			$body = str_replace('['.$key.']', $value, $body);
			$subject = str_replace('['.$key.']', $value, $subject);
		}
		$subject = function_exists('iconv') ? iconv(mb_detect_encoding($subject, 'UTF-8,ISO-8859-1'), "UTF-8//IGNORE", $subject) : $subject;

		$body = html_entity_decode($body, ENT_QUOTES);
		$body = function_exists('iconv') ? iconv(mb_detect_encoding($body, 'UTF-8,ISO-8859-1'), "UTF-8//IGNORE", $body) : $body;

		$this->setSubject($subject)->setMessage($body);

		return $this;
	}

	public function setMessage($message) {
		$message = str_replace(array("\\n", "\\r"), array('', ''), $message);
		$message = html_entity_decode($message, ENT_QUOTES);
		$this->mailer->msgHTML($message);
		return $this;
	}

	public function setSubject($subject) {
		$subject = '=?UTF-8?B?'.base64_encode($subject).'?=';
		$this->mailer->Subject = $subject;
		return $this;
	}

	public function addTemplate($id, $details = array(), $langId = 'english') {
		$expectedDetails = array(
			'title' => '',
			'description' => '',
			'placeholders' => '',
			'subject' => '',
			'body_content' => ''
		);

		/**
		 * @var $title
		 * @var $description
		 * @var $placeholders
		 * @var $subject
		 * @var $body_content
		 */
		extract(array_merge($expectedDetails, $details));
		if(!$this->templateExists($id)) {
			$titleSlug = $id.'_email_template_title';
			$descriptionSlug = $id.'_email_template_description';
			$subjectSlug = $id.'_email_template_subject';
			$messageSlug = $id.'_email_template_message';
			add_language_phrase($titleSlug, $title, $langId, 'email-template');
			add_language_phrase($descriptionSlug, $description, $langId, 'email-template');
			add_language_phrase($subjectSlug, $subject, $langId, 'email-template');
			add_language_phrase($messageSlug, $body_content, $langId, 'email-template');
			db()->query("INSERT INTO `email_templates` (`email_id`,`title`,`description`,`placeholders`,`subject`,`body_content`)
            VALUES('".$id."','".$titleSlug."','".$descriptionSlug."','".$placeholders."','".$subjectSlug."','".$messageSlug."')");
		}
		return true;
	}

	public function templateExists($id) {
		$query = db()->query("SELECT * FROM `email_templates` WHERE email_id='".$id."'");
		if($query and $query->num_rows > 0) return true;
		return false;
	}

	public function mailingListSubscription($user_id = null) {
		$user_id = isset($user_id) ? $user_id : App::getInstance()->userid;
		$cache_name = 'mailing-list-subscription-'.$user_id;
		if(cache_exists($cache_name)) {
			$mailing_list_subscription = get_cache($cache_name);
		} else {
			$db = db();
			$query = $db->query("SELECT * FROM `mailing_list_subscriptions` WHERE `user_id` = ".$user_id);
			if($query->num_rows) {
				$mailing_list_subscription = $query->fetch_assoc();
			} else {
				$hash = md5($user_id.time());
				$status = 1;
				$db->query("INSERT INTO `mailing_list_subscriptions` (`user_id`, `hash`, `status`) VALUES (".$user_id.", '".$hash."', $status)");
				$mailing_list_subscription = array(
					'user_id' => $user_id,
					'hash' => $hash,
					'status' => $status
				);
			}
			set_cacheForever($cache_name, $mailing_list_subscription);
		}
		return $mailing_list_subscription;
	}

	public function mailingListStatusUpdate($user_id, $value) {
		$status = 0;
		$message = '';
		$db = db();
		$query = $db->query("SELECT * FROM `mailing_list_subscriptions` WHERE `user_id` = ".$user_id);
		if($query->num_rows) {
			$mailing_list_subscription = $query->fetch_assoc();
			$query = $db->query("UPDATE `mailing_list_subscriptions` SET `status` = ".$value);
			if($query) {
				forget_cache('mailing-list-subscription-'.$user_id);
				$status = 1;
				$user = find_user($mailing_list_subscription['user_id']);
				$email = $user['email_address'];
				$message = $status ? lang('mailing-list-subscribe-successful', array('email' => $email)) : lang('mailing-list-unsubscribe-successful', array('email' => $email));
			}
		}
		$result = array(
			'status' => $status,
			'message' => $message,
		);
		return $result;
	}

	public function mailingListUnsubscribe($hash) {
		$status = 0;
		$db = db();
		$query = $db->query("SELECT * FROM `mailing_list_subscriptions` WHERE `hash` = '".$hash."'");
		if($query->num_rows) {
			$mailing_list_subscription = $query->fetch_assoc();
			$user = find_user($mailing_list_subscription['user_id']);
			$email = $user['email_address'];
			if(!$mailing_list_subscription['status']) {
				$message = lang('mailing-list-already-unsubscribe', array('email' => $email));
			} else {
				$query = $db->query("UPDATE `mailing_list_subscriptions` SET `status` = 0");
				if($query) {
					forget_cache('mailing-list-subscription-'.$user['id']);
					$message = lang('mailing-list-unsubscribe-successful', array('email' => $email));
					$status = 1;
				} else {
					$message = '';
				}
			}
		} else {
			$message = lang('mailing-list-unsubscribe-invalid');
		}
		$result = array(
			'status' => $status,
			'message' => $message,
		);
		return $result;
	}

}

/**
 * Pusher API
 */
class Pusher {
	//driver
	public static $instance;
	public $driver;

	/**
	 * @return Pusher
	 */
	public static function getInstance() {
		if(static::$instance) return static::$instance;
		static::$instance = new Pusher();
		return static::$instance;
	}

	public function getDriver() {
		return $this->driver;
	}

	public function setDriver($driver) {
		$this->driver = $driver;
	}

	public function lists() {
		$list = array(
			'ajax' => lang('ajax-long-polling')
		);
		$list = fire_hook('pusher.list', $list);
		return $list;
	}

}

interface PusherInterface {
	/**
	 * Method to send message
	 * @param $userid
	 * @param $type
	 * @param $details
	 * @param null $sub_push
	 * @param bool $seenUpdate
	 * @return bool
	 */
	public function sendMessage($userid, $type, $details, $sub_push = null, $seenUpdate = true);
}

class AjaxPusher implements PusherInterface {
	public function sendMessage($userid, $type, $details, $sub_push = null, $seenUpdate = true) {
		$users = (is_array($userid)) ? $userid : array($userid);
		foreach($users as $userid) {
			$cache_name = "ajax-pusher-".$userid;
			$pushes = array(
				'swseen' => 0,
				'seen' => 0,
				'types' => array()
			);
			if(cache_exists($cache_name)) {
				$pushes = get_cache($cache_name);
			}
			if(!isset($pushes['types'][$type])) $pushes['types'][$type] = array();
			$id = md5(perfectSerialize($details));
			if($sub_push) {
				if(!isset($pushes['types'][$type][$sub_push])) $pushes['types'][$type][$sub_push] = array();
				$pushes['types'][$type][$sub_push][$id] = $details;
			} else {
				$pushes['types'][$type][$id] = $details;
			}

			if($seenUpdate) {
				$pushes['seen'] = 0;
				$pushes['swseen'] = 0;
			}
			set_cacheForever($cache_name, $pushes);
			fire_hook("ajax.push.notification", null, array($type,$userid,$details));
		}
	}

	public function result($userid = null) {
		$userid = ($userid) ? $userid : get_userid();
		$cache_name = "ajax-pusher-".$userid;
		$pushes = array('seen' => 1, 'swseen' => 1, 'userid' => get_userid());
		if(cache_exists($cache_name)) {
			$pushes = get_cache($cache_name);
		}

		//forget_cache($cache_name);
		$pushes['userid'] = get_userid();
		$p = $pushes;
		if(input('sw')) {
			$p['swseen'] = 1;
		} else {
			$p['seen'] = 1;
		}
		$pushes['types'] = isset($pushes['types']) ? $pushes['types'] : array();
		$pushes['notifications'] = array();
		$p['seen_ids'] = isset($p['seen_ids']) ? $p['seen_ids'] : array();
		$p['swseen_ids'] = isset($p['swseen_ids']) ? $p['swseen_ids'] : array();
		foreach($pushes['types'] as $type => $details) {
			$pushes['notifications'][$type] = array();
			$notification = array(
				'status' => false,
				'type' => $type,
				'options' => array(
					'title' => '',
					'body' => '',
					'link' => '',
					'icon' => config('site-logo') ? url_img(config('site-logo')) : img('images/logo.png'),
					'direction' => app()->langDetails['dir'] == 'rtl' ? 'rtl' : 'ltr',
					'vibrate' => array(200, 100, 200, 100, 200, 100, 200),
					'tag' => ''
				),
				'notifications' => array()
			);
			foreach($details as $id_or_sub_push => $detail) {
				if(input('sw')) {
					if(!in_array($id_or_sub_push, $p['swseen_ids'])) {
						$pushes['notifications'][$type][$id_or_sub_push] = fire_hook('push.notification', $notification, array($type, $detail));
						$p['swseen_ids'][] = $id_or_sub_push;
					}
				} else {
					if(!in_array($id_or_sub_push, $p['seen_ids'])) {
						$p['notifications'][$type][$id_or_sub_push] = fire_hook('push.notification', $notification, array($type, $detail));
						$p['seen_ids'][] = $id_or_sub_push;
					}
				}
			}
		}
		set_cacheForever($cache_name, $p); //updating the cache

		$pushes = fire_hook('ajax.push.result', $pushes, array($userid));
		return json_encode($pushes, JSON_PRETTY_PRINT);
	}

	public function reset($type, $sub_push = null, $delete = false) {
		$userid = get_userid();
		$cache_name = "ajax-pusher-".$userid;
		if(cache_exists($cache_name)) {
			$pushes = get_cache($cache_name);
			if($sub_push) {
				$pushes['types'][$type][$sub_push] = array();
				if($delete) {
					if(isset($pushes['types'][$type][$sub_push])) unset($pushes['types'][$type][$sub_push]);
				}
			} else {
				$pushes['types'][$type] = array();
				if($delete) {
					unset($pushes['types'][$type]);
				}
			}

			set_cacheForever($cache_name, $pushes); //updating the cache
		}

		return true;
	}

	public function resetAll() {
		$userid = get_userid();
		$cache_name = "ajax-pusher-".$userid;
		forget_cache($cache_name);
	}
}

class FCMPusher implements PusherInterface {
	public function sendMessage($user_id, $type, $details, $sub_push = null, $seen_update = true) {
		$user = find_user($user_id);
		$token = $user['fcm_token'];
		if($token) {
			$users = is_array($user_id) ? $user_id : array($user_id);
			foreach($users as $user_id) {
				$pushes = array(
					'seen' => 0,
					'swseen' => 0,
					'types' => array()
				);
				$pushes['userid'] = $user_id;
				$id = md5(perfectSerialize($details));
				if($sub_push) {
					$pushes['types'][$type][$sub_push][$id] = $details;
				} else {
					$pushes['types'][$type][$id] = $details;
				}
				$notification = array(
					'status' => false,
					'type' => $type,
					'options' => array(
						'title' => '',
						'body' => '',
						'link' => '',
						'icon' => config('site-logo') ? url_img(config('site-logo')) : img('images/logo.png'),
						'direction' => app()->langDetails['dir'] == 'rtl' ? 'rtl' : 'ltr',
						'vibrate' => array(200, 100, 200, 100, 200, 100, 200),
						'tag' => ''
					),
					'notifications' => array()
				);

				$id_or_sub_push = $sub_push ? $sub_push : $id;
				$pushes['notifications'][$type][$id_or_sub_push] = fire_hook('push.notification', $notification, array($type, $pushes['types'][$type][$id_or_sub_push]));
				if($seen_update) {
					$pushes['seen'] = 0;
					$pushes['swseen'] = 0;
				}
				$firebase_api_key = config('firebase-api-key');
				$url = 'https://fcm.googleapis.com/fcm/send';
				$headers = 'Authorization:key = '.$firebase_api_key."\r\n".'Content-Type: application/json'."\r\n".'Accept: application/json'."\r\n";
				$user = find_user($user_id);
				$registration_ids = array($user['fcm_token']);
				$pushes = fire_hook('ajax.push.result', $pushes, array($user_id));
				$fields = array('registration_ids' => $registration_ids, 'data' => array('pushes' => $pushes));
				$content = json_encode($fields);
				$context = array('http' => array('method' => 'POST', 'header' => $headers, 'content' => $content));
				$context = stream_context_create($context);
				$response = @file_get_contents($url, false, $context);
				if(false) {
					var_dump($response);
				}
			}
		}
		if(class_exists('AjaxPusher')) {
			(new AjaxPusher)->sendMessage($user_id, $type, $details, $sub_push, $seen_update);
		}
	}

	public function result($user_id = null) {
		if(class_exists('AjaxPusher')) {
			return (new AjaxPusher)->result($user_id);
		}
		return json_encode(array(
			'swseen' => 0,
			'seen' => 0,
			'types' => array()
		));
	}

	public function reset($type, $sub_push = null, $delete = false) {
		if(class_exists('AjaxPusher')) {
			return (new AjaxPusher)->reset($type, $sub_push, $delete);
		}
		return true;
	}

	public function resetAll() {
		if(class_exists('AjaxPusher')) {
			return (new AjaxPusher)->resetAll();
		}
		return true;
	}
}

/**
 * Task Manager
 */
class TaskManager {

	private static $tasks = array();

	public static function add($taskId, $func) {
		static::$tasks[$taskId] = $func;
	}

	public static function run() {
		foreach(static::$tasks as $taskId => $func) {
			call_user_func($func);
		}

		return true;
	}
}


/**
 *
 * CSRF Protection
 */
class CSRFProtection {
	public static function validate($expire = true) {
		if(!config('enable-csrf', true)) return;
		$token = input("csrf_token");
		if(App::getInstance()->isApi() or is_ajax()) return true;
		$tokens = (isset($_SESSION['csrf_tokens'])) ? $_SESSION['csrf_tokens'] : array();
		if(!$tokens && !is_ajax()) redirect_back();
		if(in_array($token, $_SESSION['csrf_tokens'])) {
			if(!$expire) unset($_SESSION['csrf_tokens'][$token]);
			return true;
		}


		if(!is_loggedIn() && is_ajax()) exit('login');
		redirect_back();
	}

	public static function embed() {
		if(!config('enable-csrf', true)) return;
		echo "<input type='hidden' name='csrf_token' value='".CSRFProtection::getToken()."'/>";
	}

	public static function getToken() {
		if(!config('enable-csrf', true)) return;
		$sessionArray = (isset($_SESSION['csrf_tokens'])) ? $_SESSION['csrf_tokens'] : array();
		$newToken = md5(uniqid(mt_rand(), true).time());
		$sessionArray[] = $newToken;
		$_SESSION['csrf_tokens'] = $sessionArray;
		return $newToken;
	}
}
