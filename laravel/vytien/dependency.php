<?php
class Dependency {
 protected $object = null;
 protected $blueprint = null;

 /**
  * @param $instance callable The callable passed to the IoC object.
  */
 public function __construct($instance) {
   if (!is_object($instance)) {
     throw new InvalidArgumentException("Received argument should be object.");
   }

   $this->blueprint = $instance;
 }

 /**
  * (Magic function)
  *
  * This function serves as man-in-the-middle for method calls,
  * the if statement there serves for lazy loading the objects
  * (They get created whenever you call the first method and
  * all later calls use the same instance).
  *
  * This could allow laziest possible object definitions, like
  * adding annotation parsing functionality which can extract everything during
  * the call to the method. once the object is created it can get the annotations
  * for the method, automatically resolve its dependencies and satisfy them,
  * if possible or throw an error.
  *
  * all arguments passed to the method get passed to the method
  * of the actual code dependency.
  *
  * @param $name string The method name to invoke
  * @param $args array The array of arguments which will be passed
  *               to the call of the method
  *
  * @return mixed the result of the called method.
  */
 public function __call($name, $args = array())
 {
   if (is_null($this->object)) {
     $this->object = call_user_func($this->blueprint);
   }

   return call_user_func_array(array($this->object, $name), $args);
 }
}

/*
 * If the object implements \ArrayAccess you could
 * have easier access to the dependencies.
 *
 */
class IoC {
  protected $immutable = array(); // Holds aliases for write-protected definitions
  protected $container = array(); // Holds all the definitions

  /**
   * @param $alias string Alias to access the definition
   * @param $callback callable The calback which constructs the dependency
   * @param $immutable boolean Can the definition be overriden?
   */
  public function register ($alias, $callback, $immutable = false) {
    if (in_array($alias, $this->immutable)) {
      return false;
    }

    if ($immutable) {
      $this->immutable[] = $alias;
    }

    $this->container[$alias] = new Dependency($callback);
    return $this; // can disable this line
  }

  public function get ($alias) {
    if (!array_key_exists($alias, $this->container)) {
      return null;
    }
    return $this->container[$alias];
  }
}

class FooBar {
  public function say()
  {
    return 'I say: ';
  }

  public function hello()
  {
    return 'Hello';
  }

  public function world()
  {
    return ', World!';
  }
}

class Baz {
  protected $argument;

  public function __construct($argument)
  {
    $this->argument = $argument;
  }

  public function working()
  {
    return $this->argument->say() . 'Yep!';
  }
}

/**
 * Define dependencies
 */

$dic = new IoC;
$dic->register('greeter', function () {
  return new FooBar();
});

$dic->register('status', function () use ($dic) {
  return new Baz($dic->get('greeter'));
});

/**
 * Real Usage
 */
$greeter = $dic->get('greeter');

print $greeter->say() . ' ' . $greeter->hello() . ' ' . $greeter->world() . PHP_EOL . '<br />'; // I say: Hello , World! 

$status = $dic->get('status'); // I say: Yep!
print $status->working();
?>