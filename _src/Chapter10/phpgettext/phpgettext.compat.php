<?php
/**
 * @version		2.0
 * @author      Carlos Souza
 * @copyright   Copyright (c) 2005 Carlos Souza <csouza@web-sense.net>
 * @copyright	Copyright (c) 2008 Martin Brampton <counterpoint@aliro.org> Reduced each function to one line using PHP5 syntax
 * @package     PHPGettext
 * @license		MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @link		http://phpgettext.web-sense.net
 * @link 		http://www.aliro.org
 *
 * gettext.compat.php
 *
 * comptibility file for gettext
 *
 * @author Carlos Souza
 * @package PHPGettext
 */

function _($message){
    return gettext($message);
}
function gettext($message){
    return PHPGettext::getInstance()->gettext($message);
}
function bind_textdomain_codeset($domain, $codeset){
    return PHPGettext::getInstance()->bind_textdomain_codeset($domain, $codeset);
}
function bindtextdomain($domain, $directory){
    return PHPGettext::getInstance()->bindtextdomain($domain, $directory);
}
function dgettext($domain, $message){
    return PHPGettext::getInstance()->dgettext($domain, $message);
}
function ngettext($msg1, $msg2, $count){
    return PHPGettext::getInstance()->ngettext($msg1, $msg2, $count);
}
function dngettext($domain, $msg1, $msg2, $count){
    return PHPGettext::getInstance()->dngettext($domain, $msg1, $msg2, $count);
}
function dcgettext($domain, $message, $category){
    return PHPGettext::getInstance()->dcgettext($domain, $message, $category);
}
function dcngettext($domain, $msg1, $msg2, $count, $category){
    return PHPGettext::getInstance()->dcngettext($domain, $msg1, $msg2, $count, $category);
}
function textdomain($domain = null){
    return PHPGettext::getInstance()->textdomain($domain);
}


?>