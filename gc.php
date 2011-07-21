<?php
/**
*       Google Plus User Information Scraper (and 'Google Card' backend)
*       http://plusdevs.com
*       http://plusdevs.com/googlecard-googleplus-php-scraper/
*
*       This program is free software: you can redistribute it and/or
*       modify
*       it under the terms of the GNU General Public License as published
*       by
*       the Free Software Foundation, either version 3 of the License, or
*       (at your option) any later version.
*
*       This program is distributed in the hope that it will be useful,
*       but WITHOUT ANY WARRANTY; without even the implied warranty of
*       MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*       GNU General Public License for more details.
*
*       You should have received a copy of the GNU General Public License
*       along with this program.  If not, see
*       <http://www.gnu.org/licenses/>.
*/

# procedural reimplementation of the 'googleCard' googleplus scraper
# (see README for docs.)

# init
global $__gc;

# settings
$__gc = array(
		# basics for http access
		'url'		=>	'http://plus.google.com/',
		'user_agent'	=>	'Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0',
		# whether to cache or not
		'cache'		=>	1,
		# number of hours to cache, if caching
		'cache_hours'	=>	2,
		# whether to trust the cache (0 = more secure, 1 = faster)
		'trust_cache'	=>	0,
		# whether to trust the web (0 = intelligent, 1 = fast but stupid)
		'trust_world'	=>	0,
		# directory in which to store cachefile
		'cache_dir'	=>	'.gc_cache',
		# debug
		'debug'		=>	0
	);
# cachedir fix for windows platforms, which may break on '.blah'
if(DIR_SEPARATOR == '\\') { $__gc['cache_dir'] = 'gc_cache'; }

# check cache dir sanity
#  - exists?
if(!is_dir($__gc['cache_dir'])) {
 # attempt to create
 if(!mkdir($__gc['cache_dir'])) {
  print "ERROR: failed to create cache_dir '" . $__gc['cachedir'] . "'\n";
  die();
 }
}
#  - is writable?
if(!is_writable($__gc['cache_dir'])) {
 print "ERROR: cache_dir '" . $__gc['cache_dir'] . "' does not exist, or is not writable!\n";
 die();
}

# acquire information regarding a user on google+ by screen scraping
# arguments:
#  $googleplusid   numeric google+ id of the user whose data you wish
#                  to query. (note: this should be 21-100 numerals)
# returns an array with the following elements:
#  count   the number of followers the user has
#  img     url to the users' image
#  url     url to users' google+ page
#  name    the user's name
# ... or false on failure.
# note that cache behaviour is controlled via $__gc
function google_plus_user_info($googleplusid) {
 # sanitise
 $googleplusid = preg_replace('/[^0-9]/','',$googleplusid);
 $length = strlen($googleplusid);
 if($length < 21 || $length > 100) { return; }
 # init
 global $__gc;
 $tr = array(); # data 'to return'
 $url = $__gc['url'] . $googleplusid;
 # first, handle the case of caching enabled
 if($__gc['cache']) {
  # build cachefile path (cross-platform)
  $cachefile = $__gc['cache_dir'] . DIRECTORY_SEPARATOR . $googleplusid;
  # does the cachefile exist?
  if(file_exists($cachefile) && is_readable($cachefile)) {
   # is cachefile fresh?
   if(filemtime($cachefile) > (time() - ($__gc['cache_hours']*60*60))) {
    # great! read the data.
    $tr = json_decode(file_get_contents($cachefile),1);
    __gc_debug("loaded data from cachefile '$cachefile'");
    # sanitise if desired
    if($__gc['trust_cache'] == 0) { $tr = __gc_tr_fix($tr); }
    # append URL and return
    $tr['url'] = $url;
    return $tr;
   }
  }
 }
 # cache was either disabled or stale, so we need to fetch new data.
 $html = __gc_http($url);
 # attempt to extract the relevant information
 #  - number of followers ("in <x> circles")
 preg_match('/<h4 class="a-c-ka-Sf">(.*?)<\/h4>/s',$html,$matches);
 $tr['count'] = preg_replace('/[^0-9]/', '', $matches[1]);
 if($tr['count']=='') { $tr['count'] = 0; }
 #  - user's name
 preg_match('/<span class="fn">(.*?)<\/span>/s',$html,$matches);
 $tr['name'] = $matches[1];
 #  - user's image URL
 preg_match('/<div class="a-Ba-V-z-N">(.*?)<\/div>/s',$html,$matches);
 $img_div_html = $matches[1]; # actually div data
 preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i',$img_div_html,$matches);
 $tr['img'] = 'http:' . $matches[1];
 # finally, we handle saving to cache if required
 if($__gc['cache']) {
  file_put_contents($cachefile,json_encode($tr));
  __gc_debug("stored data to cachefile '$cachefile'");
 }
 # sanitise if required
 if(!$__gc['trust_world']) {
  $tr = __gc_tr_fix($tr);
 }
 # append URL and return
 $tr['url'] = $url;
 return $tr;
}

# try to load a page
function __gc_http($url) {
 global $__gc;
 $ch = curl_init($url);
 curl_setopt($ch, CURLOPT_HEADER, 0);
 curl_setopt($ch, CURLOPT_USERAGENT, $__gc['user_agent']);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
 return curl_exec($ch);
}

# internal function to validate information prior to returning
# called from two places:
#  - after untrusted cache load
#  - prior to storing cache results
function __gc_tr_fix($tr) {
 __gc_debug("[gc_tr_fix] input was " . print_r($tr,1));
 if(!is_array($tr)) { $tr = array(); }
 # count must be numeric
 if(($tr['count']+0) == 0) { $tr['count'] = 0; }
 # img must be an http url
 if(substr($tr['img'],0,7) != 'http://') { $tr['img'] = ''; }
 # if name is unset, set it blank
 if(!isset($tr['name'])) { $tr['name'] = ''; }
 # now strip_tags() on all data, simultaneously dropping unknown keys
 $allowed_keys = array('name','img','count');
 $keys = array_keys($tr);
 foreach($keys as $key) {
  if(!in_array($key,$allowed_keys)) {
   unset($tr[$key]);
  }
  else {
   $tr[$key] = strip_tags($tr[$key]);
  }
 }
 __gc_debug("[gc_tr_fx] output was " . print_r($tr,1));
 return $tr;
}

# debug
function __gc_debug($str) {
 global $__gc;
 if($__gc['debug']) { print "DEBUG: " . $str . "\n"; }
}

?>
