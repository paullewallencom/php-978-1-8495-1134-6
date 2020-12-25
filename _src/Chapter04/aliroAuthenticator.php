<?php

/*******************************************************************************
 * Aliro - the modern, accessible content management system
 *
 * This code is copyright (c) Aliro Software Ltd - please see the notice in the 
 * index.php file for full details or visit http://aliro.org/copyright
 *
 * Some parts of Aliro are developed from other open source code, and for more 
 * information on this, please see the index.php file or visit 
 * http://aliro.org/credits
 *
 * Author: Martin Brampton
 * counterpoint@aliro.org
 *
 * aliroLoginDetails is a simple data class used to create an object to carry the
 * information from a user login - user ID, password and the flag for whether the
 * system is to "remember" the user and automatically log them in.  The main use
 * for objects of this class is to pass data to mambots related to the authentication
 * process.
 *
 * aliroExtensionHandler knows all about the various installed extensions in
 * the system.  Anything not integral to the core - components, modules, mambots,
 * templates - are counted as extensions.  It is a cached singleton class and
 * uses common code the implement the object cache.
 *
 * aliroAuthenticator is the abstract class that contains common code for use
 * on both the user and admin sides of Aliro.
 *
 * aliroUserAuthenticator is the class that is instantiated to handle user side
 * authentication - basically login and logout.  On the user side, the actual
 * authentication is done by mambots.  The default Aliro authentication mambot
 * checks the credentials against the database, although it calls back to the
 * aliroUserAuthenticator class to perform the actual validation.  It is possible
 * to supplement the default processing with other mambots, or replace it
 * completely.  Uses for such an approach might include use of an LDAP system.
 * There are several mambot "hooks" and the other purpose for this is to be able
 * to integrate extensions that elaborate the handling of users with additional
 * properties and such like.
 *
 */

class aliroLoginDetails {
	private $id = 0;
    private $username = '';
    private $password = '';
    private $remember = '';
    private $message = '';
    private $status = _ALIRO_LOGIN_FAILED;
    private $user = null;

    public function __construct ($user, $password='', $remember='') {
        $this->username = $user;
        $this->password = $password;
        $this->remember = $remember;
    }

    public function __get ($property) {
		if ('escapedUser' == $property) return aliroDatabase::getInstance()->getEscaped($this->username);
		elseif ('escapedPassword' == $property) return aliroDatabase::getInstance()->getEscaped($this->password);
        else return isset($this->$property) ? $this->$property : null;
    }

    public function __set ($property, $value) {
    	if (in_array($property, array('message', 'status', 'user'))) $this->$property = $value;
    }
    
    // The following methods are deprecated
    public function getUser () {
    	return $this->username;
    }

    public function getPassword () {
    	return $this->password;
    }

    public function getRemember () {
    	return $this->remember;
    }
}

abstract class aliroAuthenticator {

	// Not to be called to act on anything other than the current user
	public function logout () {
		if (!empty($_SESSION["aliro_{$this->prefix}id"])) {
			$currentDate = date('Y-m-d H:i:s');
			$query = "UPDATE #__users SET lastvisitDate='$currentDate' WHERE id='" . $_SESSION["aliro_{$this->prefix}id"] . "'";
			aliroDatabase::getInstance()->doSQL($query);
		}
		aliroSession::getSession()->logout();
	}

	public static function makePassword ($syllables = 3) {
		// Developed from code by http://www.anyexample.com
		// 8 vowel sounds 
		$vowels = array ('a', 'o', 'e', 'i', 'y', 'u', 'ou', 'oo'); 
		// 20 random consonants 
		$consonants = array ('w', 'r', 't', 'p', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm', 'qu');
		// Generate three syllables
		for ($i=0, $password=''; $i<$syllables; $i++) $password .= aliroAuthenticator::makeSyllable($vowels, $consonants, $i);
		// Return with suffix added
		return $password.aliroAuthenticator::makeSuffix($vowels, $consonants);
	}

	private static function makeSuffix ($vowels, $consonants) {
		// 10 random suffixes
		$suffix = array ('dom', 'ity', 'ment', 'sion', 'ness', 'ence', 'er', 'ist', 'tion', 'or');
		$new = $suffix[array_rand($suffix)];
		// return suffix, but put a consonant in front if it starts with a vowel
		return (in_array($new[0], $vowels)) ? $consonants[array_rand($consonants)].$new : $new;
	}

	private static function makeSyllable ($vowels, $consonants, $double=false) {
		$doubles = array('n', 'm', 't', 's');
		$c = $consonants[array_rand($consonants)];
		// One in three chance of doubling the consonant - except for first syllable
		if ($double AND in_array($c, $doubles) AND 1 == mt_rand(0,2)) $c .= $c;
		return $c.$vowels[array_rand($vowels)];
	}
	
	public static function makeSalt () {
		return aliroAuthenticator::makeRandomString(24);
	}
	
	private static function makeRandomString ($length=8) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!%,-:;@_{}~";
		for ($i = 0, $makepass = '', $len = strlen($chars); $i < $length; $i++) $makepass .= $chars[mt_rand(0, $len-1)];
		return $makepass;
	}

}

final class aliroUserAuthenticator extends aliroAuthenticator {
	private static $instance = __CLASS__;
	protected $prefix = 'user';
	protected $lastVisit = '';
	protected $foundorphan = false;

	public static function getInstance () {
	    return is_object(self::$instance) ? self::$instance : (self::$instance = new self::$instance());
	}

	public function userLogin () {
		$request = aliroRequest::getInstance();
		$message = $request->getFormCheckError();
		if ($message) $this->redirectToLogin($message, _ALIRO_ERROR_WARN);
		$username = $request->getParam($_POST, 'username');
		$passwd = $request->getParam($_POST, 'passwd');
		$remember = $request->getParam($_REQUEST, 'remember');
		if (!$username OR !$passwd) {
			$message = T_('Please complete the username and password fields.');
			$this->redirectToLogin($message, _ALIRO_ERROR_WARN);
			exit;
		}
		$message = $this->systemLogin ($username, $passwd, $remember);
		if ($message) $this->redirectToLogin ($message, _ALIRO_ERROR_WARN);
		$_SESSION['aliro_login'] = 0;
		if ($this->foundorphan) $request->redirect($this->foundorphan, T_('Your request has been recovered and processed'), _ALIRO_ERROR_INFORM);
		$return = $request->getParam($_REQUEST, 'return');
		if ($return) $request->redirect($return);
		$request->goBack();
	}
	
	protected function redirectToLogin ($message='', $severity=_ALIRO_ERROR_INFORM) {
		$uri = aliroSEF::getInstance()->urilink('/login');
		aliroRequest::getInstance()->redirect($uri, $message, $severity);
	}

	public function systemLogin ($username=null, $passwd=null, $remember=null) {
		$session = aliroSession::getSession();
		$mambothandler = aliroMambotHandler::getInstance();
		if (!$session->cookiesAccepted()) return T_('Your browser is not accepting cookies - login is not possible.');
		$nogomessages = $failmessages = array();
		$my = $this->loginCheck($username, $passwd, $nogomessages, $failmessages, $remember);
		// Used to check for checkuser being true as well i.e. no messages returned
		// Depends on whether validators are additive or conjunctive
		$loginfo = new aliroLoginDetails($username, $passwd, ($remember ? true : false));
		$database = aliroDatabase::getInstance();
		if (is_object($my)) {
			$this->foundorphan = $session->recoverOrphanData($my->id);
			$session->setNewUserData($my);
			aliroUser::reset();
			$loginfo->user = $my;
			$mambothandler->trigger('goodLogin', $loginfo);
			if ($database->defaultDate() == $this->lastVisit) {
				$mambothandler->trigger('firstLogin', $my);
				$mailer = new aliroMailMessage();
				$mailer->mailSuperAdmins(sprintf(T_('First ever login by %s at %s'), $my->name, aliroCore::getInstance()->getCfg('sitename')), '');
			}
			$currentDate = date("Y-m-d H:i:s");
			$query = "UPDATE #__users SET lastvisitDate='$currentDate', block=0 where id='$my->id'";
			if ($remember) {
				$lifetime = time() + 365*24*60*60;
				setcookie("usercookie[username]", $username, $lifetime, "/");
				setcookie("usercookie[password]", $passwd, $lifetime, "/");
			}
		}
		else {
			$escuser = aliroDatabase::getInstance()->getEscaped($username);
			$query = "UPDATE #__users SET block=block+1 where username='$escuser'";
			$lifetime = time() - 365*24*60*60;
			// Delete remember me cookies on failed login
			setcookie("usercookie[username]", $username, $lifetime, "/");
			setcookie("usercookie[password]", $passwd, $lifetime, "/");
		}
		$database->doSQL($query);
		if (isset($my)) return false;
		else {
			$mambothandler->trigger('badLogin', array($loginfo));
			sleep(2);
			if (!empty($nogomessages)) return implode('<br />', $nogomessages);
			if (!empty($failmessages)) return implode('<br />', $failmessages);
			return T_('User validation plugin(s) present but no valid responses');
		}
	}

	public function loginCheck ($username, $password, &$nogomessages, &$failmessages, $remember=false) {
		$loginfo = new aliroLoginDetails($username, $password, ($remember ? true : false));
		$logresults = aliroMambotHandler::getInstance()->trigger('requiredLogin',array($loginfo));
		if (0 == count($logresults)) $nogomessages[] = T_('Logins are not permitted.  There is no authentication check active.');
		foreach ($logresults as $result) {
			if ($result instanceof aliroLoginDetails) {
				if (_ALIRO_LOGIN_PROHIBITED == $result->status) $nogomessages[] = $result->message;
				elseif (_ALIRO_LOGIN_FAILED == $result->status) $failmessages[] = $result->message;
				elseif (_ALIRO_LOGIN_GOOD == $result->status AND $result->user instanceof aliroAnyUser) $my = $result->user;
			}
			else trigger_error(T_('An obsolete user validation plugin is active and should be upgraded'));
		}
		return (empty($nogomessages) AND isset($my)) ? $my : null;
	}

	public function logout () {
		$mambothandler = aliroMambotHandler::getInstance();
		$loginfo = new aliroLoginDetails($_SESSION['aliro_username']);
		$mambothandler->trigger('beforeLogout', array($loginfo));
		parent::logout();
	}

	public function authenticate (&$message, &$my, $username, $passwd, $remember=null) {
		$message = '';
		$database = aliroDatabase::getInstance();
		$my = new aliroAnyUser();
		$escuser = $database->getEscaped($username);
		$database->setQuery( "SELECT id, gid, block, name, username, email, sendEmail, usertype, lastvisitDate FROM #__users WHERE username='$escuser' OR email = '$escuser'");
		if ($database->loadObject($my)) {
			$escpass = $database->getEscaped($passwd);
			if ($my->block > 10) {
				$message = T_('Your login has been blocked. Please contact the administrator.');
				return false;
			}
			$database = aliroCoreDatabase::getInstance();
			$database->setQuery("SELECT COUNT(*) FROM #__core_users WHERE id = $my->id  AND password=MD5(CONCAT(salt,'$escpass'))");
			if ($database->loadResult()) {
				unset($my->block);
				$this->lastVisit = $my->lastvisitDate;
				return true;
			}
			$database->setQuery("SELECT COUNT(*) FROM #__new_passwords WHERE userid = $my->id  AND password=MD5(CONCAT(salt,'$escpass'))");
			if ($database->loadResult()) {
				unset($my->block);
				$this->lastVisit = $my->lastvisitDate;
				$salt = aliroAuthenticator::makeSalt();
				$database->doSQL("UPDATE #__core_users SET password = MD5(CONCAT('$salt', '$escpass')), salt = '$salt' WHERE id = $my->id");
				$database->doSQL("DELETE FROM #__new_passwords WHERE userid = $my->id OR SUBDATE(NOW(),7) > stamp");
				return true;
			}
		}
		$message = T_('Incorrect username or password. Please try again.');
		return false;
	}

}
