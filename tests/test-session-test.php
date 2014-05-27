<?PHP

class SessionTest extends WP_UnitTestCase
{
	public function testInstanceCreation()
	{
		require_once 'class-recursive-arrayaccess.php';
		require_once 'class-wp-session.php';
		$o = WP_Session::get_instance();
		$this->assertInstanceOf('WP_Session',$o);
	}

}

