<?php
/**
 *  P3_Feed
 *
 *  require
 *      * P3_Abstract
 *
 *  @version 3.0.5
 *  @see     https://github.com/orzy/p3
 *  @license The MIT license (http://www.opensource.org/licenses/mit-license.php)
 *
 *  参考サイト
 *      * http://www.futomi.com/lecture/japanese/rss20.html
 *      * http://www.futomi.com/lecture/japanese/rfc4287.html
 *      * http://validator.w3.org/feed/
 *      * http://sitemaps.org/ja/protocol.php
 *      * http://www.google.com/support/webmasters/bin/answer.py?answer=34648
 */
class P3_Feed extends P3_Abstract {
	
	const RSS2 = 'RSS2';
	const ATOM1 = 'ATOM1';
	const SITEMAP = 'SITEMAP';
	const SITEMAP_MOBILE = 'SITEMAP_MOBILE';
	
	private $_siteUrl;
	private $_title;
	private $_description;
	private $_lang;
	private $_items = array();
	
	/**
	 *  @param	string	$siteUrl	(Optional) RSS2では必須
	 *  @param	string	$title	(Optional) RSS2・ATOM1では必須
	 *  @param	string	$desc	(Optional) RSS2では必須
	 *  @param	string	$lang	(Optional)
	 */
	public function __construct($siteUrl = '', $title = '', $desc = '', $lang = 'ja') {
		$this->_siteUrl = $siteUrl;
		$this->_title = $title;
		$this->_description = $desc;
		$this->_lang = $lang;
		
		header('Content-Type: application/xml; charset=' . self::ENCODING);
	}
	/**
	 *  コンテンツを追加する
	 *  RSS2ではtitleかdescriptionのどちらかは必須
	 *  @param	string	$url	ATOM1・Sitemapでは必須
	 *  @param	string	$title	(Optional)ATOM1では必須
	 *  @param	string	$description	(Optional)
	 *  @param	string	$date	(Optional)ATOM1では必須
	 *  @param	string	$author	(Optional)
	 *                           ATOM1では必須（feed全体のauthor（未対応）があれば不要）
	 *                           RSS2ではE-Mailアドレス（名前付きでもよい）
	 */
	public function add($url, $title = '', $description = '', $date = '', $author = '') {
		$this->_items[] = compact('url', 'title', 'description', 'date', 'author');
	}
	/**
	 *  フィードを出力する
	 *  @param	string	$format	(Optional)
	 */
	public function feed($format = self::RSS2) {
		echo '<?xml version="1.0" encoding="' . self::ENCODING . '" ?>' . "\n";
		
		switch ($format) {
			case self::RSS2:
				$this->_rss2();
				break;
			case self::ATOM1:
				$this->_atom1();
				break;
			case self::SITEMAP:
			case self::SITEMAP_MOBILE:
				$this->_sitemap($format);
				break;
			default:
				throw new InvalidArgumentException("'$format'には対応していません");
		}
	}
	
	private function _rss2() {
		echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
		echo "<channel>\n";
		
		echo $this->_element('title', $this->_title);
		echo $this->_element('link', $this->_siteUrl);
		echo $this->_element('description', $this->_description);
		echo '<pubDate>' . date(DATE_RSS) . "</pubDate>\n";
		echo '<atom:link rel="self" type="application/rss+xml" href="';
		echo $this->_getFeedUrl() . '" />' . "\n";
		
		if ($this->_lang) {
			echo $this->_element('language', $this->_lang);
		}
		
		foreach ($this->_items as $item) {
			echo "<item>\n";
			
			if (isset($item['title'])) {
				echo $this->_element('title', $item['title']);
			}
			
			if (isset($item['url'])) {
				echo $this->_element('link', $item['url']);
				echo $this->_element('guid', $item['url']);
			}
			
			if (isset($item['description'])) {
				echo $this->_element('description', $item['description']);
			}
			
			if (isset($item['author'])) {
				echo $this->_element('author', $item['author']);
			}
			
			if (isset($item['date'])) {
				$date = date(DATE_RSS, strToTime($item['date']));
				echo "<pubDate>$date</pubDate>\n";
			}
			
			echo "</item>\n";
		}
		
		echo "</channel>\n";
		echo '</rss>';
	}
	
	private function _atom1() {
		$lang = $this->_lang ? ' xml:lang="' . $this->_h($this->_lang) . '"' : '';
		$feedUrl = $this->_getFeedUrl();
		
		echo '<feed xmlns="http://www.w3.org/2005/Atom"' . $lang . ">\n";
		
		echo $this->_element('title', $this->_title);
		echo '<updated>' . date(DATE_ATOM) . "</updated>\n";
		echo "<id>$feedUrl</id>\n";
		echo '<link rel="self" type="application/atom+xml" href="';
		echo $feedUrl . '" />' . "\n";
		
		if ($this->_description) {
			echo $this->_element('subtitle', $this->_description);
		}
		
		if ($this->_siteUrl) {
			$siteUrl = $this->_h($this->_siteUrl);
			echo '<link type="text/html" href="' . $siteUrl . '" />' . "\n";
		}
		
		foreach ($this->_items as $item) {
			$url = $this->_h($item['url']);
			$date = date(DATE_ATOM, strToTime($item['date']));
			
			echo "<entry>\n";
			echo $this->_element('title', $item['title']);
			echo '<link type="text/html" href="' . $url . '" />' . "\n";
			echo "<id>$url</id>\n";
			echo "<updated>$date</updated>\n";
			echo '<author>' . $this->_element('name', $item['author']) . "</author>\n";
			
			if (isset($item['description'])) {
				$description = $this->_h($item['description']);
				echo '<content type="html">' . $description . "</content>\n";
			}
			
			echo "</entry>\n";
		}
		
		echo '</feed>';
	}
	
	private function _sitemap($format) {
		$ns = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
		
		if ($format === self::SITEMAP_MOBILE) {
			$ns .= ' xmlns:mobile="http://www.google.com/schemas/sitemap-mobile/1.0"';
		}
		
		echo "<urlset $ns>\n";
		
		foreach ($this->_items as $item) {
			echo "<url>\n";
			echo $this->_element('loc', $item['url']);
			
			if (isset($item['date'])) {
				$date = date(DATE_W3C, strToTime($item['date']));
				echo "<lastmod>$date</lastmod>\n";
			}
			
			if ($format === self::SITEMAP_MOBILE) {
				echo "<mobile:mobile />\n";
			}
			
			echo "</url>\n";
		}
		
		echo '</urlset>';
	}
	
	private function _getFeedUrl() {
		$url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		return $this->_h($url);
	}
	
	private function _element($name, $text) {
		return "<$name>" . $this->_h($text) . "</$name>\n";
	}
}
