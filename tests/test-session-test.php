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

	public function testNestedAdds()
	{
		$o = WP_Session::get_instance();
		$o->reset();
		$o['test'] = array();
		$o['test']['one'] = 'One';
		$o['test']['two'] = 'Two';
		$this->assertTrue($o->isDirty());
		$o->write_data();
		var_dump($o->isDirty());
		$this->assertFalse($o->isDirty());
		
		unset($o);
		
		$o = WP_Session::get_instance();		
		$this->assertArrayHasKey('test',$o);
		$this->assertArrayHasKey('one',$o['test']);
		$this->assertArrayHasKey('two',$o['test']);
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

