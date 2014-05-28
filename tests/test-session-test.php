<?PHP

class SessionTest extends WP_UnitTestCase
{
	public function testInstanceCreation()
	{
		require_once 'class-recursive-arrayaccess.php';
		require_once 'class-wp-session.php';
		$o = WP_Session::get_instance();
		$this->assertInstanceOf('WP_Session',$o);
		return;
	}

	public function testSimpleIsDirty()
	{
		$o = WP_Session::get_instance();
		$o->reset();
		$this->assertFalse($o->isDirty());
		$o['test'] = true;
		$this->assertTrue($o->isDirty());
		return;
	}

	public function testNestedIsDirty()
	{
		$o = WP_Session::get_instance();
		$o->reset();
		$this->assertFalse($o->isDirty());
		$o['test'] = array();
		$o['test']['one'] = true;
		$this->assertTrue($o->isDirty());
		return;

	}

	public function testGenerateId()
	{
		// kludgy
 		$class = new ReflectionClass('WP_Session');
  		$method = $class->getMethod('generate_id');
  		$method->setAccessible(true);		
		$o = WP_Session::get_instance();
		$o->reset();
		$this->assertNotEmpty($method->invokeArgs($o,array()));
		return;
	}

}

