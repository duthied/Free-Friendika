Friendica strategy and decorator Hooks
===========================================

* [Home](help)

## Strategy hooks

This type of hook is based on the [Strategy Design Pattern](https://refactoring.guru/design-patterns/strategy).

A strategy class defines a possible implementation of a given interface based on a unique name.
Every name is possible as long as it's unique and not `null`.
Using an empty name (`''`) is possible as well and should be used as the "default" implementation.
To register a strategy, use the [`ICanRegisterInstance`](../src/Core/Hooks/Capabilities/ICanRegisterInstances.php) interface.

After registration, a caller can automatically create this instance with the [`ICanCreateInstances`](../src/Core/Hooks/Capabilities/ICanCreateInstances.php) interface and the chosen name.

This is useful in case there are different, possible implementations for the same purpose, like for logging, locking, caching, ...

Normally, a config entry is used to choose the right implementation at runtime.
And if no config entry is set, the "default" implementation should be used.

### Example

```php
interface ExampleInterface
{
	public function testMethod();
}

public class ConcreteClassA implements ExampleInterface
{
	public function testMethod()
	{
		echo "concrete class A";
	}
}

public class ConcreteClassB implements ExampleInterface
{
	public function testMethod()
	{
		echo "concrete class B";
	}
}

/** @var \Friendica\Core\Hooks\Capabilities\ICanRegisterInstances $instanceRegister */
$instanceRegister->registerStrategy(ExampleInterface::class, ConcreteClassA::class, 'A');
$instanceRegister->registerStrategy(ExampleInterface::class, ConcreteClassB::class, 'B');

/** @var \Friendica\Core\Hooks\Capabilities\ICanCreateInstances $instanceManager */
/** @var ConcreteClassA $concreteClass */
$concreteClass = $instanceManager->createWithName(ExampleInterface::class, 'A');

$concreteClass->testMethod();
// output:
// "concrete class A";
```

## Decorator hooks

This type of hook is based on the [Decorator Design Pattern](https://refactoring.guru/design-patterns/decorator).

A decorator class extends a given strategy instance (see  [Strategy hooks](#strategy-hooks)]).
To register a decorator, use the [`ICanRegisterInstance`](../src/Core/Hooks/Capabilities/ICanRegisterInstances.php) interface.

After registration, a caller can automatically create an instance with the [`ICanCreateInstances`](../src/Core/Hooks/Capabilities/ICanCreateInstances.php) interface and the decorator will wrap its logic around the call.

This is useful in case you want to extend a given class but the given class isn't responsible for these business logic. Or you want to extend an interface without knowing the concrete implementation.
For example profiling logger calls, Friendica is using a [`ProfilerLogger`](../src/Core/Logger/Type/ProfilerLogger.php), which wraps all other logging implementations and traces each log call.

Normally, a config entry is used to enable/disable decorator.

### Example

```php
interface ExampleInterface
{
	public function testMethod();
}

public class ConcreteClassA implements ExampleInterface
{
	public function testMethod()
	{
		echo "concrete class A";
	}
}

public class DecoratorClassA implements ExampleInterface
{
	/** @var ExampleInterface */
	protected $example;

	public function __construct(ExampleInterface $example)
	{
		$this->example = $example;
	}

	public function testMethod()
	{
		echo "decorated!\n";
		$this->example->testMethod();
	}
}

/** @var \Friendica\Core\Hooks\Capabilities\ICanRegisterInstances $instanceRegister */
$instanceRegister->registerStrategy(ExampleInterface::class, ConcreteClassA::class, 'A');
$instanceRegister->registerDecorator(ExampleInterface::class, DecoratorClassA::class);

/** @var \Friendica\Core\Hooks\Capabilities\ICanCreateInstances $instanceManager */
/** @var ConcreteClassA $concreteClass */
$concreteClass = $instanceManager->createWithName(ExampleInterface::class, 'A');

$concreteClass->testMethod();
// output:
// "decorated!"
// "concrete class A";
```

## hooks.config.php

To avoid registering all strategies and decorators manually inside the code, Friendica introduced the [`hooks.config.php`](../static/hooks.config.php) file.

There, you can register all kind of strategies and decorators in one file.

### [`HookType::STRATEGY`](../src/Core/Hooks/Capabilities/HookType.php)

For each given interface, a list of key-value pairs can be set, where the key is the concrete implementation class and the value is an array of unique names.

### [`HookType::DECORATOR`](../src/Core/Hooks/Capabilities/HookType.php)

For each given interface, a list of concrete decorator classes can be set.

### Example

```php
use Friendica\Core\Hooks\Capabilities\HookType as H;

return [
	H::STRATEGY  => [
		ExampleInterface::class => [
			ConcreteClassA::class => ['A'],
			ConcreteClassB::class => ['B'],
		],
	],
	H::DECORATOR => [
		ExampleInterface::class => [
			DecoratorClassA::class,
		],
	],
];
```

## Addons

The hook logic is useful for decoupling the Friendica core logic, but its primary goal is to modularize Friendica in creating addons.

Therefor you can either use the interfaces directly as shown above, or you can place your own `hooks.config.php` file inside a `static` directory directly under your addon core directory.
Friendica will automatically search these config files for each **activated** addon and register the given hooks.
