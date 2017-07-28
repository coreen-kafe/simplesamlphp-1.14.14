<?php
class sspmod_kafe_Auth_Source_MyAuth extends sspmod_core_Auth_UserPassBase {

    /* The database DSN.
     * See the documentation for the various database drivers for information about the syntax:
     *     http://www.php.net/manual/en/pdo.drivers.php
     */
    private $dsn;

    /* The database username & password. */
    private $username;
    private $password;

	 // 대칭키 salt
	 private $aes_key_string = 'HereIsTheAESKeyString!';

    public function __construct($info, $config) {
        parent::__construct($info, $config);

        if (!is_string($config['dsn'])) {
            throw new Exception('Missing or invalid dsn option in config.');
        }
        $this->dsn = $config['dsn'];
        if (!is_string($config['username'])) {
            throw new Exception('Missing or invalid username option in config.');
        }
        $this->username = $config['username'];
        if (!is_string($config['password'])) {
            throw new Exception('Missing or invalid password option in config.');
        }
        $this->password = $config['password'];
    }


// 비밀번호 암호화
private function _generateHash($plainText, $salt = null)
{
	$salt = "kreonet core 2013";  

	if ($salt === null)
	{
		$salt = substr(md5(uniqid(rand(), true)), 0, 25);
	}
	else
	{
		$salt = substr($salt, 0, 25);
	}

	$salt = sha1($salt);
	$digest = $salt . hash('sha256', $salt . $plainText);
	return $salt . hash('sha256', $salt . $plainText);
}

// 문자열 암호화
private function _aes_encrypt($value, $secret = '')
{
	if (empty($secret)) {
		$secret = $this->aes_key_string;
	}

    return rtrim(
        base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                $secret, $value,
                MCRYPT_MODE_ECB,
                mcrypt_create_iv(
                    mcrypt_get_iv_size(
                        MCRYPT_RIJNDAEL_256,
                        MCRYPT_MODE_ECB
                    ),
                    MCRYPT_RAND)
                )
            ), "\0"
        );
}

// 문자열 복호화
private function _aes_decrypt($value, $secret = '')
{
	if (empty($secret)) {
		$secret = $this->aes_key_string;
	}

    return rtrim(
        mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256,
            $secret,
            base64_decode($value),
            MCRYPT_MODE_ECB,
            mcrypt_create_iv(
                mcrypt_get_iv_size(
                    MCRYPT_RIJNDAEL_256,
                    MCRYPT_MODE_ECB
                ),
                MCRYPT_RAND
            )
        ), "\0"
    );
}

    protected function login($username, $password) {

        /* Connect to the database. */
        $db = new PDO($this->dsn, $this->username, $this->password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* Ensure that we are operating with UTF-8 encoding.
         * This command is for MySQL. Other databases may need different commands.
         */
        $db->exec("SET NAMES 'utf8'");

        /* With PDO we use prepared statements. This saves us from having to escape
         * the username in the database query.
         */
        $st = $db->prepare('SELECT * FROM cosso_users WHERE user_name=:username');

        if (!$st->execute(array('username' => $this->_aes_encrypt($username)))) {
            throw new Exception('Failed to query database for user.');
        }

        /* Retrieve the row from the database. */
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            /* User not found. */
            SimpleSAML_Logger::warning('MyAuth: Could not find user ' . var_export($username, TRUE) . '.');
            throw new SimpleSAML_Error_Error('WRONGUSERPASS');
        }

        /* Check the password. */
        if ($row['password'] !== $this->_generateHash($password)) {
            /* Invalid password. */
            SimpleSAML_Logger::warning('MyAuth: Wrong password for user ' . var_export($username, TRUE) . '.');
            throw new SimpleSAML_Error_Error('WRONGUSERPASS');
        }

		  $display_name = $row['display_name'];
		  $display_name = $this->_aes_decrypt($display_name);
		  $mail = $row['email'];
		  $mail = $this->_aes_decrypt($mail);
		  $affiliation = $row['affiliation'];
		  $schahomeorg = $row['schahomeorg'];
		  $eduppn = $username.'@coreen.or.kr';

		  // 일단 관리자를 만들기 위해서...
		  $organization = '';
		  if ($affiliation === 'staff' && $schahomeorg === 'coreen.kr') {
				$organization = 'KISTI';
		  }

        /* Create the attribute array of the user. */
			$attributes = array(
				'uid' => array($username),
				'displayName' => array($display_name),
				'mail' => array($mail),
				'eduPersonAffiliation' => array($affiliation),
				'organization' => array($organization),
				'eduPersonScopedAffiliation' => array(''), 
				'eduPersonPrincipalName' => array($eduppn), 
				'schHomeOrganization' => array($schahomeorg),
			);

			// IDP 에 로그인 횟수 기록
			$db->exec('INSERT INTO cosso_login_users(user_id,user_ip,access_stamp) VALUES ("'.$username.'","'.$_SERVER['REMOTE_ADDR'].'",now())');

        /* Return the attributes. */
        return $attributes;
    }

}
