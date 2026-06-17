# Componenta ClassFinder

Lazy PHP declaration discovery with composable filters and listener notification.

ClassFinder scans PHP files, extracts named class/interface/trait/enum declarations
through `componenta/tokenizer`, applies filters, and returns a replayable iterator of
`ClassInfo` metadata.

## Installation

```bash
composer require componenta/class-finder
```

## Requirements

- PHP 8.4+
- `symfony/finder`
- `componenta/tokenizer`
- `componenta/filter`
- `componenta/arrayable`
- `componenta/iterator`
- `psr/container`
- `psr/log`

## Related Packages

| Package | Why it matters here |
|---|---|
| `componenta/tokenizer` | Parses PHP source and returns `ClassInfo` declarations. |
| `symfony/finder` | Walks directories and selects PHP files. |
| `componenta/iterator` | Provides replayable iteration over discovered declarations. |
| `componenta/filter` | Provides composable filters used by the finder. |
| `componenta/app` and `*-app` packages | Run class discovery while building application cache. |

## Quick Start

```php
use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\Filter\InstantiableFilter;
use Componenta\ClassFinder\Filter\PatternFilter;

$finder = new ClassFinder([
    PatternFilter::endsWith('Controller'),
    new InstantiableFilter(),
]);

$controllers = $finder->find(__DIR__ . '/src', exclude: ['tests']);

foreach ($controllers as $file => $classInfo) {
    echo $classInfo->fullyQualifiedName . PHP_EOL;
}
```

## Search Modes

`find()` accepts the tokenizer search bitmask directly:

```php
use Componenta\Tokenizer\TokenizerInterface;

$classes = $finder->find('src/', mode: TokenizerInterface::SEARCH_CLASSES);
$contracts = $finder->find(
    'src/',
    mode: TokenizerInterface::SEARCH_INTERFACES | TokenizerInterface::SEARCH_TRAITS,
);
```

Search mode is a per-call argument. It is not a DI configuration key.

## ClassIterator

`ClassFinder::find()` returns `ClassIteratorInterface`: lazy, replayable,
countable, arrayable, and filterable.

```php
$classes = $finder->find('src/');

$classes->count();   // forces traversal once and caches the count
$classes->toArray(); // list<ClassInfo>

$filtered = $classes->withFilter(PatternFilter::namespace('App\\Http'));
```

The iterator caches traversed declarations so it can be iterated more than once.

## Pattern Filters

`PatternFilter` matches `ClassInfo` metadata without reflection.

```php
use Componenta\ClassFinder\Filter\PatternFilter;

new PatternFilter('*Controller');        // class name suffix
new PatternFilter('User*');              // class name prefix
new PatternFilter('App\\User');          // exact fully-qualified class name
new PatternFilter('*\\Api\\*Controller'); // fully-qualified wildcard pattern

PatternFilter::exactMatch('UserController');
PatternFilter::namespace('App\\Http');              // namespace and children
PatternFilter::exactNamespace('App\\Http\\Admin');  // exact namespace only
PatternFilter::exactFqn('App\\Http\\UserController');
PatternFilter::fqn('App\\*\\*Controller');
PatternFilter::in(['UserController', 'PostController']);
```

Use `exactNamespace()` when the input is a namespace without wildcard. A bare
string containing `\` is treated as a fully-qualified class name or FQN pattern.

## Reflection Filters

Some filters require the declaration to be loaded because they use
`ClassInfo::$reflector`:

- `AttributeSearchFilter`
- `AttributePatternFilter`
- `AnyAttributeFilter`
- `HasAnyAttributesFilter`
- `ImplementsFilter`
- `ImplementsAnyFilter`
- `SubclassFilter`

These filters are appropriate when scanned classes are autoloadable. For pure
source inspection of unloaded files, prefer metadata-only filters such as
`PatternFilter`, `InstantiableFilter`, `IsAbstractFilter`, and `IsFinalFilter`.

## Attribute Filters

```php
use Componenta\ClassFinder\Filter\AttributePatternFilter;
use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Filter\AnyAttributeFilter;

AttributeSearchFilter::hasAttribute(Route::class);
AttributeSearchFilter::hasAnyAttribute([Route::class, Command::class]);
AttributeSearchFilter::hasAllAttributes([Cache::class, Validate::class]);
AttributeSearchFilter::hasAttribute(Inject::class, deepSearch: true);

new AttributePatternFilter('*Attribute', deepSearch: true);
AttributePatternFilter::attributePrefix('App\\Attribute\\');

new AnyAttributeFilter([Route::class, Command::class], deepSearch: true);
```

`deepSearch: true` also checks methods, properties, and constants.

## Listeners

Listeners are notified for each accepted declaration. Finalizable listeners are
finalized after scanning, even when no declarations were found.

```php
use Componenta\ClassFinder\ClassListenerInterface;
use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\Tokenizer\ClassInfo;

final class RouteCollector implements FinalizableListenerInterface
{
    /** @var list<class-string> */
    private array $routes = [];

    public function handle(ClassInfo $info): void
    {
        if ($info->reflector->getAttributes(Route::class) !== []) {
            $this->routes[] = $info->fullyQualifiedName;
        }
    }

    public function finalize(): void
    {
        // Build final registry or cache.
    }
}
```

`ClassListenerNotifier` snapshots the provider's listeners once per `notify()`
call, so the same listener instances receive `handle()` and `finalize()`.

When a finalizable listener is later compiled into an application build cache,
it should also implement `FinalizationStateInterface`. The `finalized`
property becomes `true` only after a successful `finalize()` call. Repeated
finalization may be rejected by the listener; the package provides
`FinalizationExceptionInterface` and `ListenerAlreadyFinalizedException` for
that case.

## Compile Integration

Packages that collect metadata through listeners can expose compilers without
depending on an application runner:

```php
use Componenta\ClassFinder\Compile\CompileResult;
use Componenta\ClassFinder\Compile\ListenerCompilerInterface;

final class RouteCollectorCompiler implements ListenerCompilerInterface
{
    public function supports(object $listener): bool
    {
        return $listener instanceof RouteCollector;
    }

    public function compile(object $listener, string $cacheDir): CompileResult
    {
        return CompileResult::filesOnly([
            $cacheDir . '/routes.cache.php' => '<?php return [];',
        ]);
    }
}
```

Register compiler class names under
`Componenta\ClassFinder\Compile\ConfigKey::LISTENER_COMPILERS`. The host application
decides when discovery runs and where sidecar files are written.

A listener compiler should not scan classes, call `finalize()`, or read private
listener state through reflection. It receives an object that has already passed
through the discovery lifecycle. The `componenta/app` integration checks before
compilation that a finalizable listener implements `FinalizationStateInterface`
and is already finalized.

## Container Integration

Register the package provider in a PSR-11 compatible container:

```php
$config = (new Componenta\ClassFinder\ConfigProvider())();
```

Runtime configuration keys are defined in `Componenta\ClassFinder\ConfigKey`:

| Constant | Value | Description |
|----------|-------|-------------|
| `ConfigKey::FILTERS` | `Componenta\ClassFinder:filters` | Default `FilterInterface` instances for `ClassFinderFactory`. |
| `ConfigKey::LISTENERS` | `Componenta\ClassFinder:listeners` | Listener service ids or `ClassListenerInterface` instances. |

Listener config is fail-fast: every entry must be a listener instance or a
service id string resolving to `ClassListenerInterface`.

## License

MIT
