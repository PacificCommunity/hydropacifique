<?php
/*
----------------------------------------
Copyright (c) 2024 - Vai-Natura
----------------------------------------
*/

/*
 * Reverse-proxy note
 * ------------------
 * Behind a reverse proxy (hydro-pacific-uat.spc.int -> Docker) REMOTE_ADDR is
 * the proxy / Docker bridge address, and the real client address arrives in
 * X-Forwarded-For. That header is written by whoever sends the request, so it is
 * only believed when the request actually reached us through a proxy we know.
 * Trusting it unconditionally would let any client pick its own IP and walk
 * straight past the ip_out blacklist.
 *
 * X-Forwarded-For is also a *list* ("client, proxy1, proxy2"), not one address.
 * The old code stored the whole header, which meant the blacklist never matched
 * and a long enough chain overflowed ctrl_ip_login.ip (varchar 40) — an error,
 * not a truncation, under the MySQL 8.4 default sql_mode, which tep_db_query
 * turns into a hard die().
 */

// Column widths in the ctrl_ip_* tables, so a long value can never abort an INSERT.
define('HP_IP_MAXLEN', 40);
define('HP_DNS_MAXLEN', 80);


/*
 * True when $ip belongs to our own infrastructure (loopback, private range,
 * link-local) rather than to a real client — i.e. it is a hop we control.
 */
function hp_ip_is_infrastructure($ip)
{
	if (!filter_var($ip, FILTER_VALIDATE_IP))
	{
		return false;
	}

	// filter_var returns false for private/reserved addresses when these flags
	// are set, so "not public" means it is one of our own hops.
	return filter_var($ip, FILTER_VALIDATE_IP,
		FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
}


/* Information IP de l'utilisateur */
function getIP()
{
	// $_SERVER, not getenv(): getenv() only sees request headers under some
	// SAPIs (it works with mod_php, but returns false under php-fpm).
	$remote = isset($_SERVER['REMOTE_ADDR']) ? trim($_SERVER['REMOTE_ADDR']) : '';

	// Direct hit, no proxy in front: REMOTE_ADDR is the only trustworthy value.
	if (!hp_ip_is_infrastructure($remote))
	{
		return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
	}

	// The request came through our proxy, so the forwarded chain can be read.
	// Walk it right-to-left and take the first address that is not one of our
	// own hops: that is the closest address we can still believe.
	$forwarded = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';

	if ($forwarded !== '')
	{
		$hops = array_reverse(array_map('trim', explode(',', $forwarded)));

		foreach ($hops as $hop)
		{
			// Strip an optional :port, and [] around an IPv6 literal.
			$hop = preg_replace('/^\[(.+)\]$/', '$1', $hop);

			if (!filter_var($hop, FILTER_VALIDATE_IP))
			{
				continue;
			}

			if (hp_ip_is_infrastructure($hop))
			{
				continue;
			}

			return $hop;
		}
	}

	$client = isset($_SERVER['HTTP_CLIENT_IP']) ? trim($_SERVER['HTTP_CLIENT_IP']) : '';

	if (filter_var($client, FILTER_VALIDATE_IP) && !hp_ip_is_infrastructure($client))
	{
		return $client;
	}

	// Everything upstream is internal (health check, container-to-container).
	return filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '';
}


/* Information Navigateur de l'utilisateur */
function getUser_agent()
{
	return isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
}


/*
 * Reverse DNS for the audit tables. Guarded because gethostbyaddr() emits a
 * warning on a non-IP and can block for seconds when the resolver does not
 * answer — inside a container that stalls the whole request.
 */
function hp_ip_reverse_dns($ip)
{
	if (!filter_var($ip, FILTER_VALIDATE_IP))
	{
		return '';
	}

	$dns = @gethostbyaddr($ip);

	// gethostbyaddr() returns the input unchanged when there is no PTR record.
	if ($dns === false || $dns === $ip)
	{
		return '';
	}

	return substr($dns, 0, HP_DNS_MAXLEN);
}


// Verification que l'IP qui tente de se connecté n'est pas blacklisté : ip_out
function ip_out($sql_link)
{
	$ip = getIP();

	if (!tep_not_null($ip))
	{
		return false;
	}

	// Escaped: getIP() can originate from a client-supplied header.
	$ip_safe = mysqli_real_escape_string($sql_link, $ip);

	$ip_out_query = tep_db_query($sql_link,"SELECT * FROM ".TABLE_IP_OUT." WHERE ip='".$ip_safe."'");
	$ip_out_array = tep_db_fetch_array($ip_out_query);

	if(isset($ip_out_array) && tep_not_null($ip_out_array['id'])){return true;}
	else{return false;}
}



// Vérification d'un changement d'IP en cours de session : ip_suspect
function ip_suspect($sql_link)
{
	//info de session, on vérifie que c'est bien le premier accès du moment
	$session_info = getAdminInfo($sql_link);

	if(!tep_not_null($session_info))
	{
		$ip      = getIP();
		$ip_safe  = mysqli_real_escape_string($sql_link, $ip);
		$dns_safe = mysqli_real_escape_string($sql_link, hp_ip_reverse_dns($ip));

	  	$ip_suspect_query = tep_db_query($sql_link,"SELECT * FROM ".TABLE_IP_SUSPECT." WHERE ip='".$ip_safe.
								   "' AND dns='".$dns_safe."'");
 	 	$ip_suspect_array = tep_db_fetch_array($ip_suspect_query);

	  	if(isset($ip_suspect_array) && tep_not_null($ip_suspect_array['id']) && ($ip_suspect_array['last_access'] != date("Y/m/d H:i")))
	  	{
			  tep_db_query($sql_link,"UPDATE ".TABLE_IP_SUSPECT." SET last_access='" . date("Y/m/d H:i") .
									"' WHERE id='" . (int)$ip_suspect_array['id'] . "'");
		  	return true;
	  	}
	  	else{return false;}
	}
	else{return false;}
}


// Enregistrement d'un IP suspect
function save_ip_suspect($sql_link,$type)
{
	$ip = getIP();

	if(tep_not_null($ip))
	{
		$ctrl_last_access = date("Y/m/d H:i");

		// All three values can come from request headers, so escape them and
		// keep them inside their column widths.
		$ip_safe      = mysqli_real_escape_string($sql_link, substr($ip, 0, HP_IP_MAXLEN));
		$dns_safe     = mysqli_real_escape_string($sql_link, hp_ip_reverse_dns($ip));
		$browser_safe = mysqli_real_escape_string($sql_link, getUser_agent());
		$type_safe    = mysqli_real_escape_string($sql_link, $type);

		tep_db_query($sql_link,"INSERT INTO ".TABLE_IP_SUSPECT."(ip,dns,browser,type,date,heure,last_access) " .
						  "VALUES ('" . $ip_safe .
						           "', '" . $dns_safe .
						           "', '" . $browser_safe .
						           "', '" . $type_safe .
					        	   "', now()" .
					           	   ", current_time()" .
					           	   ",'" . $ctrl_last_access . "')");
	}
}



// Stopper une tentative de connexion par robot, pas plus de 5 tentatives d'accès consécutives sont autorisées.

function ip_login_enforce($sql_link)
{
	$compteur_essai_log = 0;
	$date_connect = date("Y/m/d H:i");

	$ip = getIP();

	if (!tep_not_null($ip))
	{
		// Nothing identifiable to rate-limit on; do not lock the page out.
		return false;
	}

	// varchar(40) NOT NULL — an over-long value is an error, not a truncation,
	// under the MySQL 8.4 default sql_mode.
	$ip_safe = mysqli_real_escape_string($sql_link, substr($ip, 0, HP_IP_MAXLEN));

	// Supression des ip contrôlés dont la date ne correspond plus
	tep_db_query($sql_link,"DELETE FROM ".TABLE_IP_LOGIN." WHERE date_connect <> '".$date_connect."'");

	// Récupération puis contrôle des ip qui se sont connectés dans la minute
	$ip_ctrl_query = tep_db_query($sql_link,"SELECT * FROM ".TABLE_IP_LOGIN." WHERE ip='".$ip_safe."'");
	$ip_ctrl_array = tep_db_fetch_array($ip_ctrl_query);
	if(isset($ip_ctrl_array) && tep_not_null($ip_ctrl_array['ip']))
	{
		$compteur_essai_log = $ip_ctrl_array['nb_tentatives'] + 1;
		tep_db_query($sql_link,"UPDATE ".TABLE_IP_LOGIN." SET nb_tentatives=" . (int)$compteur_essai_log .
							   " WHERE ip='" . $ip_safe . "'");
	}
	else
	{
		$compteur_essai_log = 1;
		tep_db_query($sql_link,"INSERT INTO ".TABLE_IP_LOGIN."(ip,date_connect,nb_tentatives) " .
						     "VALUES ('" . $ip_safe .
						       	      "', '" . $date_connect .
						       	      "', 1)");
	}


	// Si on a plus de 10 pages édités par minutes alors on rempli la table ip_out
	if($compteur_essai_log > 5)
	{
		save_ip_suspect($sql_link,'auto_login_force');
		return true;
	}
	else{return false;}
}

?>
