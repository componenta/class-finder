# Componenta ClassFinder

[English version](README.md)

Ленивое обнаружение PHP-объявлений с компонуемыми предикатами и уведомлением слушателей.

ClassFinder обходит PHP-файлы, извлекает именованные объявления классов, интерфейсов, трейтов и перечислений через `componenta/tokenizer`, применяет предикаты и возвращает итератор `ClassInfo` с повторным обходом.

## Установка

```bash
composer require componenta/class-finder
```

## Требования

- PHP 8.4+
- `symfony/finder`
- `componenta/tokenizer` 1.0.1+
- `componenta/filter` 2.x
- `componenta/config` 3.x или 4.x
- `componenta/arrayable`
- `componenta/iterator` 1.1.2+
- `psr/container`
- `psr/log`

## Предикаты

`ClassFinder` и `ClassIterator` принимают `Componenta\Filter\PredicateInterface`. Пользовательский предикат больше не обязан реализовывать контракт итерируемой коллекции:

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

Встроенные фильтры ClassFinder по-прежнему наследуются от `Componenta\Filter\AbstractFilter`, поэтому реализуют и `PredicateInterface`, и обычный `FilterInterface`. Операторы только для коллекций, например `PercentageFilter` и `MergingFilter`, предикатами ClassFinder не являются.

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

`find()` принимает битовую маску токенизатора напрямую:

```php
use Componenta\Tokenizer\TokenizerInterface;

$classes = $finder->find('src/', mode: TokenizerInterface::SEARCH_CLASSES);
$contracts = $finder->find(
    'src/',
    mode: TokenizerInterface::SEARCH_INTERFACES | TokenizerInterface::SEARCH_TRAITS,
);
```

Режим поиска задаётся на конкретный вызов `find()`, а не через конфигурацию контейнера.
Корневые директории и исключения фиксируются в момент вызова. Каждый канонический
путь к файлу сканируется один раз, даже при пересечении директорий поиска.
Ошибки чтения и токенизации прерывают обход. Токенизатор предполагает синтаксически
корректный PHP и не заменяет проверку синтаксиса.

## ClassIterator

`ClassFinder::find()` возвращает `ClassIteratorInterface`: ленивый итератор с повторным обходом, подсчётом и преобразованием в массив, который можно дополнительно фильтровать предикатами.

```php
$classes = $finder->find('src/');

$classes->count();
$classes->toArray();

$filtered = $classes->withFilter(PatternFilter::namespace('App\\Http'));
```

`withFilter()` и `withoutFilter()` принимают `PredicateInterface` и возвращают
новое представление, не изменяя список предикатов исходного итератора.
`hasFilter()` проверяет наличие именно переданного экземпляра предиката.
Представления используют общий кеш исходных записей и независимые курсоры.
Вложенные обходы и `count()` не перемещают другие курсоры. При ошибке источника
последующие чтения повторно выбрасывают исходное исключение.

Подсчёт с фильтрами заново проверяет текущие предикаты; результат не кешируется.
Для итератора без предикатов повторный подсчёт после полного успешного обхода
не требует итерации по всем декларациям. Прямые источники `ClassIterator` допускают
ключи `string`, `int` или `null` и должны возвращать экземпляры `ClassInfo`.
Некорректные записи вызывают `UnexpectedValueException` до фильтрации.
Некорректные метаданные пользовательского токенизатора прерывают обнаружение.
`toArray()` возвращает список; чтобы сохранить имена файлов, используйте `foreach`,
поскольку одинаковые ключи при преобразовании с сохранением ключей перезаписываются.

### Восстановление подготовленной карты

`ClassIterator::fromMap()` создаёт итератор из упорядоченного списка объявлений:

```php
use Componenta\ClassFinder\ClassIterator;

$classes = ClassIterator::fromMap([
    [
        'file' => '/app/src/OrderDto.php',
        'name' => 'App\\OrderDto',
        'type' => 'class',
        'abstract' => false,
        'final' => true,
        'readonly' => true,
    ],
]);
```

Каждая запись содержит непустые строки `file` и `name`, тип `class`, `interface`,
`trait` или `enum` и булевы флаги `abstract`, `final`, `readonly`. Вся карта
проверяется до возврата итератора; неверная запись вызывает `RuntimeException`.
Пустой список допустим. Проверенные скалярные поля сохраняются в независимом
снимке, даже если входной массив содержит PHP-ссылки.

Фабрика сохраняет порядок объявлений и повторяющиеся имена файлов. Она не читает
файлы и не обходит директории. Обход, фильтрация и повторное чтение используют
обычное поведение `ClassIterator`. Загрузка и сохранение карты остаются у вызывающего кода.

## Фильтры по имени

`PatternFilter` сопоставляет `ClassInfo` без рефлексии:

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

Шаблоны учитывают регистр. Шаблон с единственным завершающим `\\*` выбирает
пространство имён и его дочерние пространства; остальные шаблоны с разделителями
пространств имён сопоставляются с полным именем. `fqn()` явно выбирает полное имя.
`exactNamespace('')` выбирает глобальное пространство имён.

## Фильтры с рефлексией

Некоторые предикаты требуют, чтобы объявление было доступно через автозагрузку, поскольку используют `ClassInfo::$reflector`:

- `AttributeSearchFilter`
- `AttributePatternFilter`
- `AnyAttributeFilter`
- `HasAnyAttributesFilter`
- `ImplementsFilter`
- `ImplementsAnyFilter`
- `SubclassFilter`
- `InstantiableFilter`
- `CallableFilter` (конкретные классы и перечисления с собственным или унаследованным открытым методом `__invoke`; видимость конструктора не учитывается)

Для анализа исходников без загрузки классов используйте предикаты, работающие только с метаданными: `PatternFilter`, `IsAbstractFilter`, `IsFinalFilter`. Фильтры с рефлексией могут
инициировать автозагрузку, поэтому их следует использовать только с доверенным кодом.

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

Точное сопоставление имён атрибутов не зависит от регистра и допускает начальный
разделитель пространства имён. Шаблоны учитывают регистр. Глубокий поиск охватывает
методы, свойства и константы, включая унаследованные члены, доступные через
рефлексию. Параметры методов не проверяются; экземпляры атрибутов не создаются.
В режиме AND требуемые атрибуты могут находиться на разных членах объявления.
Пустой список атрибутов или интерфейсов не совпадает ни с одним объявлением.
Списки имён классов и атрибутов принимают только строки; неверные элементы вызывают
`InvalidArgumentException` при создании предиката без преобразования в строку.

## Слушатели

Слушатели получают найденные объявления. Повторные регистрации одного объекта объединяются в порядке первого появления; разные экземпляры одного класса остаются независимыми. Слушатели, реализующие `FinalizableListenerInterface`, финализируются после успешного сканирования, даже если объявления не найдены. Ошибки обнаружения, обработки и финализации прерывают вызов; уже выполненные действия слушателей не откатываются. Финализация не является обработчиком ошибок.

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

Слушатель может публиковать состояние завершения через `FinalizationStateInterface`.
Повторная финализация может быть отклонена слушателем с `FinalizationExceptionInterface`;
для этого предусмотрено исключение `ListenerAlreadyFinalizedException`.

## Интеграция с контейнером

Контракты фабрик и композиции конфигурации проверяются с Config 3.x и 4.x независимо
от конкретной реализации контейнера. Отдельные интеграционные тесты DI 5.x
используют Config 3.x в соответствии с ограничением зависимости самого DI 5.x.

```php
$contribution = (new Componenta\ClassFinder\ConfigProvider())();
```

| Константа | Значение | Описание |
|-----------|----------|----------|
| `ConfigKey::FILTERS` | `Componenta\ClassFinder:filters` | Предикаты `PredicateInterface` по умолчанию для `ClassFinderFactory`. |
| `ConfigKey::LISTENERS` | `Componenta\ClassFinder:listeners` | Идентификаторы сервисов слушателей или экземпляры `ClassListenerInterface`. |

`FILTERS` принимает предикат или итерируемую последовательность предикатов.
`LISTENERS` принимает массив экземпляров слушателей или идентификаторов сервисов,
реализующих `ClassListenerInterface`.

Сервисы токенизатора и логгера можно не регистрировать. При явной регистрации
по идентификатору интерфейса сервис должен реализовывать этот интерфейс.
Зарегистрированный `null` или несовместимый объект считается ошибкой конфигурации,
а не указанием использовать реализацию по умолчанию.

## Разработка

```bash
composer install
composer test
```

Тесты написаны на Pest и прогоняются в CI на PHP 8.4 и 8.5.

## Лицензия

MIT

## Измерение производительности

Команда `composer bench` запускает PHPBench: холодное сканирование, восстановление
карт, повторный обход, предикаты с рефлексией и без неё, уведомление слушателей.
Подготовка данных не входит в измеряемый участок; результаты проверяются после замера.

