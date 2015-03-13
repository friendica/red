<?php

function id_init(&$a) {

logger('id: ' . print_r($_REQUEST,true));

/**
 * This example shows several things:
 * - How a setup interface should look like.
 * - How to use a mysql table for authentication
 * - How to store associations in mysql table, instead of php sessions.
 * - How to store realm authorizations.
 * - How to send AX/SREG parameters.
 * For the example to work, you need to create the necessary tables:
CREATE TABLE Users (
    id INT NOT NULL auto_increment PRIMARY KEY,
    login VARCHAR(32) NOT NULL,
    password CHAR(40) NOT NULL,
    firstName VARCHAR(32) NOT NULL,
    lastName VARCHAR(32) NOT NULL
);

CREATE TABLE AllowedSites (
    user INT NOT NULL,
    realm TEXT NOT NULL,
    attributes TEXT NOT NULL,
    INDEX(user)
);

CREATE TABLE Associations (
    id INT NOT NULL PRIMARY KEY,
    data TEXT NOT NULL
);
 *
 * This is only an example. Don't use it in your code as-is.
 * It has several security flaws, which you shouldn't copy (like storing plaintext login and password in forms).
 *
 * This setup could be very easily flooded with many associations, 
 * since non-private ones aren't automatically deleted.
 * You could prevent this by storing a date of association and removing old ones,
 * or by setting $this->dh = false;
 * However, the latter one would disable stateful mode, unless connecting via HTTPS.
 */
require 'library/openid/provider/provider.php';


function getUserData($handle=null)
{
	if(! local_channel()) {
		notice( t('Permission denied.') . EOL);
		get_app()->page['content'] =  login();
		return false;
	}

//	logger('handle: ' . $handle);

	if($handle) {
		$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_address = '%s' limit 1",
			dbesc($handle)
		);
	}
	else {
		$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d",
			intval(local_channel())
		);
	}

	if(! r)
		return false;

	$x = q("select * from account where account_id = %d limit 1", 
		intval($r[0]['channel_account_id'])
	);
	if($x)
		$r[0]['email'] = $x[0]['account_email'];
		
	$r[0]['firstName'] = ((strpos($r[0]['channel_name'],' ')) ? substr($r[0]['channel_name'],0,strpos($r[0]['channel_name'],' ')) : $r[0]['channel_name']);
	$r[0]['lastName'] = ((strpos($r[0]['channel_name'],' ')) ? substr($r[0]['channel_name'],strpos($r[0]['channel_name'],' ')+1) : '');
	

	return $r[0];

/*
    if(isset($_POST['login'],$_POST['password'])) {
        $login = mysql_real_escape_string($_POST['login']);
        $password = sha1($_POST['password']);
        $q = mysql_query("SELECT * FROM Users WHERE login = '$login' AND password = '$password'");
        if($data = mysql_fetch_assoc($q)) {
            return $data;
        }
        if($handle) {
            echo 'Wrong login/password.';
        }
    }
    if($handle) {
    ?>
    <form action="" method="post">
    <input type="hidden" name="openid.assoc_handle" value="<?php echo $handle?>">
    Login: <input type="text" name="login"><br>
    Password: <input type="password" name="password"><br>
    <button>Submit</button>
    </form>
    <?php
    die();
    }
*/

}

class MysqlProvider extends LightOpenIDProvider
{
    private $attrMap = array(
        'namePerson/first'       => 'First name',
        'namePerson/last'        => 'Last name',
        'namePerson/friendly'    => 'Nickname',
		'contact/internet/email' => 'Email'
        );
    
    private $attrFieldMap = array(
        'namePerson/first'       => 'firstName',
        'namePerson/last'        => 'lastName',
        'namePerson/friendly'    => 'channel_address',
		'contact/internet/email' => 'email'
        );
    
    function setup($identity, $realm, $assoc_handle, $attributes)
    {

//		logger('identity: ' . $identity);
//		logger('realm: ' . $realm);
//		logger('assoc_handle: ' . $assoc_handle);
//		logger('attributes: ' . print_r($attributes,true));

        $data = getUserData($assoc_handle);

        $o .= '<form action="" method="post">'
           . '<input type="hidden" name="openid.assoc_handle" value="' . $assoc_handle . '">'
           . '<input type="hidden" name="login" value="' . $_POST['login'] .'">'
           . '<input type="hidden" name="password" value="' . $_POST['password'] .'">'
           . "<b>$realm</b> wishes to authenticate you.";
        if($attributes['required'] || $attributes['optional']) {
            $o .= " It also requests following information (required fields marked with *):"
               . '<ul>';
            
            foreach($attributes['required'] as $attr) {
                if(isset($this->attrMap[$attr])) {
                    $o .= '<li>'
                       . '<input type="checkbox" name="attributes[' . $attr . ']"> '
                       . $this->attrMap[$attr] . '(*)</li>';
                }
            }
            
            foreach($attributes['optional'] as $attr) {
                if(isset($this->attrMap[$attr])) {
                    $o .= '<li>'
                       . '<input type="checkbox" name="attributes[' . $attr . ']"> '
                       . $this->attrMap[$attr] . '</li>';
                }
            }
            $o .= '</ul>';
        }
        $o .= '<br>'
           . '<button name="once">Allow once</button> '
           . '<button name="always">Always allow</button> '
           . '<button name="cancel">cancel</button> '
           . '</form>';

		get_app()->page['content'] .= $o;

    }
    
    function checkid($realm, &$attributes)
    {

		logger('checkid: ' . $realm);

		logger('checkid attrs: ' . print_r($attributes,true));


        if(isset($_POST['cancel'])) {
            $this->cancel();
        }
        
        $data = getUserData();
        if(! $data) {
            return false;
        }


		logger('checkid: checkpoint1');


		$q = get_pconfig(local_channel(),'openid',$realm);

		$attrs = array();
		if($q) {
			$attrs = $q;
        } elseif(isset($_POST['attributes'])) {
            $attrs = array_keys($_POST['attributes']);
        } elseif(!isset($_POST['once']) && !isset($_POST['always'])) {
            return false;
        }

        $attributes = array();
        foreach($attrs as $attr) {
            if(isset($this->attrFieldMap[$attr])) {
                $attributes[$attr] = $data[$this->attrFieldMap[$attr]];
            }
        }
        
        if(isset($_POST['always'])) {
			set_pconfig(local_channel(),'openid',$realm,array_keys($attributes));
        }
  
		return z_root() . '/id/' . $data['channel_address'];      
    }
    
    function assoc_handle()
    {
		
		$channel = get_app()->get_channel();
		return z_root() . '/id/' . $channel['channel_address']; 

    }
    
    function setAssoc($handle, $data)
    {
		logger('setAssoc');
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			set_pconfig($channel['channel_id'],'openid','associate',$data);

    }
    
    function getAssoc($handle)
    {
		logger('getAssoc: ' . $handle);

		$channel = channelx_by_nick(basename($handle));
		if($channel)
			return get_pconfig($channel['channel_id'],'openid','associate');
		return false;
    }
    
    function delAssoc($handle)
    {
		logger('delAssoc');
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			return del_pconfig($channel['channel_id'],'openid','associate');
    }
    
}
$op = new MysqlProvider;
$op->server();

}











