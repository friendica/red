<?php
/**
 * @file mod/id.php
 * @brief OpenID implementation
 */

require 'library/openid/provider/provider.php';


$attrMap = array(
	'namePerson/first'       => t('First Name'),
	'namePerson/last'        => t('Last Name'),
	'namePerson/friendly'    => t('Nickname'),
	'namePerson'             => t('Full Name'),
	'contact/internet/email' => t('Email'),
	'contact/email'          => t('Email'),
	'media/image/aspect11'   => t('Profile Photo'),
	'media/image'            => t('Profile Photo'),
	'media/image/default'    => t('Profile Photo'),
	'media/image/16x16'      => t('Profile Photo 16px'),
	'media/image/32x32'      => t('Profile Photo 32px'),
	'media/image/48x48'      => t('Profile Photo 48px'),
	'media/image/64x64'      => t('Profile Photo 64px'),
	'media/image/80x80'      => t('Profile Photo 80px'),
	'media/image/128x128'    => t('Profile Photo 128px'),
	'timezone'               => t('Timezone'),
	'contact/web/default'    => t('Homepage URL'),
	'language/pref'          => t('Language'),
	'birthDate/birthYear'    => t('Birth Year'),
	'birthDate/birthMonth'   => t('Birth Month'),
	'birthDate/birthday'     => t('Birth Day'),
	'birthDate'              => t('Birthdate'),
	'gender'                 => t('Gender'),
);


/**
 * @brief Entrypoint for the OpenID implementation.
 *
 * @param App &$a
 */
function id_init(&$a) {

	logger('id: ' . print_r($_REQUEST, true));

	if(argc() > 1) {
		$which = argv(1);
	} else {
		$a->error = 404;
		return;
	}

	$profile = '';
	$channel = $a->get_channel();
	profile_load($a,$which,$profile);

	$op = new MysqlProvider;
	$op->server();
}

/**
 * @brief Returns user data needed for OpenID.
 *
 * If no $handle is provided we will use local_channel() by default.
 *
 * @param string $handle (default null)
 * @return boolean|array
 */
function getUserData($handle = null) {
	if (! local_channel()) {
		notice( t('Permission denied.') . EOL);
		get_app()->page['content'] =  login();

		return false;
	}

//	logger('handle: ' . $handle);

	if ($handle) {
		$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_address = '%s' limit 1",
			dbesc($handle)
		);
	} else {
		$r = q("select * from channel left join xchan on channel_hash = xchan_hash where channel_id = %d",
			intval(local_channel())
		);
	}

	if (! r)
		return false;

	$x = q("select * from account where account_id = %d limit 1", 
		intval($r[0]['channel_account_id'])
	);
	if ($x)
		$r[0]['email'] = $x[0]['account_email'];

	$p = q("select * from profile where is_default = 1 and uid = %d limit 1",
		intval($r[0]['channel_account_id'])
	);

	$gender = '';
	if ($p[0]['gender'] == t('Male'))
		$gender = 'M';
	if ($p[0]['gender'] == t('Female'))
		$gender = 'F';

	$r[0]['firstName'] = ((strpos($r[0]['channel_name'],' ')) ? substr($r[0]['channel_name'],0,strpos($r[0]['channel_name'],' ')) : $r[0]['channel_name']);
	$r[0]['lastName'] = ((strpos($r[0]['channel_name'],' ')) ? substr($r[0]['channel_name'],strpos($r[0]['channel_name'],' ')+1) : '');
	$r[0]['namePerson'] = $r[0]['channel_name'];
	$r[0]['pphoto'] = $r[0]['xchan_photo_l'];
	$r[0]['pphoto16'] = z_root() . '/photo/profile/16/' . $r[0]['channel_id'] . '.jpg';
	$r[0]['pphoto32'] = z_root() . '/photo/profile/32/' . $r[0]['channel_id'] . '.jpg';
	$r[0]['pphoto48'] = z_root() . '/photo/profile/48/' . $r[0]['channel_id'] . '.jpg';
	$r[0]['pphoto64'] = z_root() . '/photo/profile/64/' . $r[0]['channel_id'] . '.jpg';
	$r[0]['pphoto80'] = z_root() . '/photo/profile/80/' . $r[0]['channel_id'] . '.jpg';
	$r[0]['pphoto128'] = z_root() . '/photo/profile/128/' . $r[0]['channel_id'] . '.jpg';
	$r[0]['timezone'] = $r[0]['channel_timezone'];
	$r[0]['url'] = $r[0]['xchan_url'];
	$r[0]['language'] = (($x[0]['account_language']) ? $x[0]['account_language'] : 'en');
	$r[0]['birthyear'] = ((intval(substr($p[0]['dob'],0,4))) ? intval(substr($p[0]['dob'],0,4)) : '');
	$r[0]['birthmonth'] = ((intval(substr($p[0]['dob'],5,2))) ? intval(substr($p[0]['dob'],5,2)) : '');
	$r[0]['birthday'] = ((intval(substr($p[0]['dob'],8,2))) ? intval(substr($p[0]['dob'],8,2)) : '');
	$r[0]['birthdate'] = (($r[0]['birthyear'] && $r[0]['birthmonth'] && $r[0]['birthday']) ? $p[0]['dob'] : '');
	$r[0]['gender'] = $gender;

	return $r[0];

/*
*    if(isset($_POST['login'],$_POST['password'])) {
*        $login = mysql_real_escape_string($_POST['login']);
*        $password = sha1($_POST['password']);
*        $q = mysql_query("SELECT * FROM Users WHERE login = '$login' AND password = '$password'");
*        if($data = mysql_fetch_assoc($q)) {
*            return $data;
*        }
*        if($handle) {
*            echo 'Wrong login/password.';
*        }
*    }
*    if($handle) {
*    ?>
*    <form action="" method="post">
*    <input type="hidden" name="openid.assoc_handle" value="<?php echo $handle?>">
*    Login: <input type="text" name="login"><br>
*    Password: <input type="password" name="password"><br>
*    <button>Submit</button>
*    </form>
*    <?php
*    die();
*    }
*/

}


/**
 * @brief MySQL provider for OpenID implementation.
 *
 */
class MysqlProvider extends LightOpenIDProvider {

	// See http://openid.net/specs/openid-attribute-properties-list-1_0-01.html
	// This list contains a few variations of these attributes to maintain 
	// compatibility with legacy clients

	private $attrFieldMap = array(
		'namePerson/first'       => 'firstName',
		'namePerson/last'        => 'lastName',
		'namePerson/friendly'    => 'channel_address',
		'namePerson'             => 'namePerson',
		'contact/internet/email' => 'email',
		'contact/email'          => 'email',
		'media/image/aspect11'   => 'pphoto',
		'media/image'            => 'pphoto',
		'media/image/default'    => 'pphoto',
		'media/image/16x16'      => 'pphoto16',
		'media/image/32x32'      => 'pphoto32',
		'media/image/48x48'      => 'pphoto48',
		'media/image/64x64'      => 'pphoto64',
		'media/image/80x80'      => 'pphoto80',
		'media/image/128x128'    => 'pphoto128',
		'timezone'               => 'timezone',
		'contact/web/default'    => 'url',
		'language/pref'          => 'language',
		'birthDate/birthYear'    => 'birthyear',
		'birthDate/birthMonth'   => 'birthmonth',
		'birthDate/birthday'     => 'birthday',
		'birthDate'              => 'birthdate',
		'gender'                 => 'gender',
	);

	function setup($identity, $realm, $assoc_handle, $attributes) {
		global $attrMap;

//		logger('identity: ' . $identity);
//		logger('realm: ' . $realm);
//		logger('assoc_handle: ' . $assoc_handle);
//		logger('attributes: ' . print_r($attributes,true));

		$data = getUserData($assoc_handle);


/** @FIXME this needs to be a template with localised strings */

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
                       . $this->attrMap[$attr] . ' <span class="required">*</span></li>';
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

	function checkid($realm, &$attributes) {

		logger('checkid: ' . $realm);
		logger('checkid attrs: ' . print_r($attributes,true));

		if(isset($_POST['cancel'])) {
			$this->cancel();
		}

		$data = getUserData();
		if(! $data) {
			return false;
		}

		$q = get_pconfig(local_channel(), 'openid', $realm);

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

	function assoc_handle() {
		logger('assoc_handle');
		$channel = get_app()->get_channel();

		return z_root() . '/channel/' . $channel['channel_address']; 
	}

	function setAssoc($handle, $data) {
		logger('setAssoc');
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			set_pconfig($channel['channel_id'],'openid','associate',$data);
	}

	function getAssoc($handle) {
		logger('getAssoc: ' . $handle);

		$channel = channelx_by_nick(basename($handle));
		if($channel)
			return get_pconfig($channel['channel_id'], 'openid', 'associate');

		return false;
	}

	function delAssoc($handle) {
		logger('delAssoc');
		$channel = channelx_by_nick(basename($handle));
		if($channel)
			return del_pconfig($channel['channel_id'], 'openid', 'associate');
	}
}
