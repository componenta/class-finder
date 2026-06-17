# Componenta ClassFinder

Ленивое обнаружение PHP-объявлений в файлах. Пакет обходит директории, читает PHP-файлы через `componenta/tokenizer`, применяет фильтры и возвращает переигрываемый итератор `ClassInfo`.

Используйте пакет, когда приложению нужно найти классы, интерфейсы, трейты или enum по директориям: например, контроллеры с `#[Route]`, обработчики команд, слушателей событий или конфигурационные провайдеры.

## Установка

```bash
composer require componenta/class-finder
```

## Требования

- PHP 8.4+
- `symfony/finder`
- `componenta/tokenizer`
- `componenta/filter`
- `componenta/arrayable`
- `componenta/iterator`
- `psr/container`
- `psr/log`

## Связанные пакеты

| Пакет | Зачем нужен здесь |
|---|---|
| `componenta/tokenizer` | Разбирает PHP-код и возвращает `ClassInfo`. |
| `symfony/finder` | Обходит директории и выбирает PHP-файлы. |
| `componenta/iterator` | Даёт переигрываемый итератор найденных объявлений. |
| `componenta/filter` | Используется для компонуемых фильтров. |
| `componenta/app` и `*-app` пакеты | Запускают обнаружение классов при сборке кеша приложения. |
| `psr/container` | Нужен, если слушатели обнаружения берутся из контейнера по service id. |

## Быстрый старт

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

## Режимы поиска

`find()` принимает bitmask токенайзера напрямую:

```php
use Componenta\Tokenizer\TokenizerInterface;

$classes = $finder->find('src/', mode: TokenizerInterface::SEARCH_CLASSES);
$contracts = $finder->find(
    'src/',
    mode: TokenizerInterface::SEARCH_INTERFACES | TokenizerInterface::SEARCH_TRAITS,
);
```

Режим поиска задаётся на конкретный вызов `find()`. Это не ключ DI-конфига.

## Итератор классов

`ClassFinder::find()` возвращает `ClassIteratorInterface`: ленивый, переигрываемый, счётный итератор с `toArray()` и фильтрацией.

```php
$classes = $finder->find('src/');

$classes->count();
$classes->toArray();

$filtered = $classes->withFilter(PatternFilter::namespace('App\\Http'));
```

Итератор кеширует уже пройденные объявления, поэтому его можно обходить несколько раз.

## Фильтры по имени

`PatternFilter` сопоставляет `ClassInfo` без рефлексии.

```php
use Componenta\ClassFinder\Filter\PatternFilter;

new PatternFilter('*Controller');         // суффикс имени класса
new PatternFilter('User*');               // префикс имени класса
new PatternFilter('App\\User');           // точный FQCN
new PatternFilter('*\\Api\\*Controller'); // маска по FQCN

PatternFilter::exactMatch('UserController');
PatternFilter::namespace('App\\Http');
PatternFilter::exactNamespace('App\\Http\\Admin');
PatternFilter::exactFqn('App\\Http\\UserController');
PatternFilter::fqn('App\\*\\*Controller');
PatternFilter::in(['UserController', 'PostController']);
```

Используйте `exactNamespace()`, когда входное значение является namespace без маски. Строка с `\` в конструкторе считается FQCN или FQCN-паттерном.

## Фильтры с рефлексией

Часть фильтров требует, чтобы объявление уже было загружено через autoload, потому что они используют `ClassInfo::$reflector`:

- `AttributeSearchFilter`
- `AttributePatternFilter`
- `AnyAttributeFilter`
- `HasAnyAttributesFilter`
- `ImplementsFilter`
- `ImplementsAnyFilter`
- `SubclassFilter`

Эти фильтры подходят, когда найденные классы доступны через autoload. Для чистого анализа исходников без загрузки классов используйте фильтры по данным токенайзера: `PatternFilter`, `InstantiableFilter`, `IsAbstractFilter`, `IsFinalFilter`.

## Фильтры атрибутов

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

`deepSearch: true` дополнительно проверяет методы, свойства и константы.

## Слушатели

Слушатели получают найденные объявления. `FinalizableListenerInterface` финализируется после сканирования, даже если объявлений не найдено.

```php
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
        // Собрать финальный реестр или cache.
    }
}
```

`ClassListenerNotifier` один раз материализует список слушателей на вызов `notify()`, поэтому `handle()` и `finalize()` вызываются на тех же экземплярах.

Если финализируемый слушатель затем компилируется в сборочный кеш приложения, он должен также реализовать `FinalizationStateInterface`. Свойство `finalized` становится `true` только после успешного `finalize()`. Повторный вызов `finalize()` может быть ошибкой домена слушателя; для такого случая пакет предоставляет `FinalizationExceptionInterface` и `ListenerAlreadyFinalizedException`.

## Интеграция с компиляцией

Пакеты, которые собирают метаданные через слушателей, могут предоставить компилятор без зависимости от раннера приложения:

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

Классы компиляторов регистрируются под ключом `Componenta\ClassFinder\Compile\ConfigKey::LISTENER_COMPILERS`. Хост-приложение решает, когда запускать обнаружение и куда писать дополнительные файлы кеша.

Компилятор слушателя не должен сам сканировать классы, вызывать `finalize()` или читать приватное состояние слушателя через reflection. Он получает объект, который уже прошел discovery lifecycle. Интеграционный слой `componenta/app` перед вызовом компилятора проверяет, что финализируемый слушатель реализует `FinalizationStateInterface` и уже финализирован.

## Интеграция с контейнером

Провайдер пакета:

```php
$config = (new Componenta\ClassFinder\ConfigProvider())();
```

Ключи конфигурации времени выполнения находятся в `Componenta\ClassFinder\ConfigKey`:

| Константа | Значение | Описание |
|-----------|----------|----------|
| `ConfigKey::FILTERS` | `Componenta\ClassFinder:filters` | Фильтры по умолчанию для `ClassFinderFactory`. |
| `ConfigKey::LISTENERS` | `Componenta\ClassFinder:listeners` | Service id слушателей или экземпляры `ClassListenerInterface`. |

Конфиг слушателей работает fail-fast: каждая запись должна быть экземпляром слушателя или строковым service id, который резолвится в `ClassListenerInterface`.
