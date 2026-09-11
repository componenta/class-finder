# Componenta ClassFinder

Ленивое обнаружение PHP-объявлений с компонуемыми предикатами и уведомлением слушателей.

ClassFinder обходит PHP-файлы, извлекает объявления class/interface/trait/enum через `componenta/tokenizer`, применяет предикаты и возвращает переигрываемый итератор `ClassInfo`.

## Установка

```bash
composer require componenta/class-finder
```

## Требования

- PHP 8.4+
- `symfony/finder`
- `componenta/tokenizer`
- `componenta/filter` 2.x
- `componenta/arrayable`
- `componenta/iterator`
- `psr/container`
- `psr/log`

## Предикаты

`ClassFinder` и `ClassIterator` принимают `Componenta\Filter\PredicateInterface`. Пользовательский предикат больше не обязан реализовывать iterable-контракт:

```php
use Componenta\ClassFinder\ClassFinder;
use Componenta\Filter\PredicateInterface;

$controllers = new class implements PredicateInterface {
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return str_ends_with($value->name, 'Controller');
    }
};

$finder = new ClassFinder($controllers);
```

Встроенные фильтры ClassFinder по-прежнему наследуются от `Componenta\Filter\AbstractFilter`, поэтому реализуют и `PredicateInterface`, и обычный `FilterInterface`. Collection-only операторы, например `PercentageFilter` и `MergingFilter`, предикатами ClassFinder не являются.

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

Режим поиска задаётся на конкретный вызов `find()`.

## ClassIterator

`ClassFinder::find()` возвращает `ClassIteratorInterface`: ленивый, переигрываемый, countable и arrayable итератор, который можно дополнительно фильтровать предикатами.

```php
$classes = $finder->find('src/');

$classes->count();
$classes->toArray();

$filtered = $classes->withFilter(PatternFilter::namespace('App\\Http'));
```

`withFilter()` и `withoutFilter()` принимают `PredicateInterface`.

## Фильтры по имени

`PatternFilter` сопоставляет `ClassInfo` без reflection:

```php
use Componenta\ClassFinder\Filter\PatternFilter;

new PatternFilter('*Controller');
new PatternFilter('User*');
new PatternFilter('App\\User');
new PatternFilter('*\\Api\\*Controller');

PatternFilter::exactMatch('UserController');
PatternFilter::namespace('App\\Http');
PatternFilter::exactNamespace('App\\Http\\Admin');
PatternFilter::exactFqn('App\\Http\\UserController');
PatternFilter::fqn('App\\*\\*Controller');
PatternFilter::in(['UserController', 'PostController']);
```

## Фильтры с reflection

Некоторые предикаты требуют, чтобы объявление было доступно через autoload, поскольку используют `ClassInfo::$reflector`:

- `AttributeSearchFilter`
- `AttributePatternFilter`
- `AnyAttributeFilter`
- `HasAnyAttributesFilter`
- `ImplementsFilter`
- `ImplementsAnyFilter`
- `SubclassFilter`

Для анализа исходников без загрузки классов используйте metadata-only предикаты: `PatternFilter`, `InstantiableFilter`, `IsAbstractFilter`, `IsFinalFilter`.

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

## Слушатели

Слушатели получают найденные объявления. `FinalizableListenerInterface` финализируется после сканирования, даже если объявления не найдены.

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
    }
}
```

## Интеграция с контейнером

```php
$config = (new Componenta\ClassFinder\ConfigProvider())();
```

| Константа | Значение | Описание |
|-----------|----------|----------|
| `ConfigKey::FILTERS` | `Componenta\ClassFinder:filters` | Предикаты `PredicateInterface` по умолчанию для `ClassFinderFactory`. |
| `ConfigKey::LISTENERS` | `Componenta\ClassFinder:listeners` | Service id слушателей или экземпляры `ClassListenerInterface`. |

Название ключа `FILTERS` сохранено, но в 2.x его элементы являются предикатами.

## Breaking changes при переходе на componenta/filter 2.x

- `ClassFinder` и `ClassIterator` принимают `PredicateInterface` вместо `FilterInterface`.
- Пользовательскому предикату больше не нужны `IteratorAggregate`, `withIterable()` и `toArray()`.
- Collection-only операторы нельзя передавать как discovery predicates.
- Существующие встроенные фильтры остаются совместимы, поскольку `FilterInterface` в componenta/filter 2.x расширяет `PredicateInterface`.

## Разработка

```bash
composer install
composer test
```

Тесты написаны на Pest и прогоняются в CI на PHP 8.4 и 8.5.

## Лицензия

MIT
