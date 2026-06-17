<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\ClassFinder\Tests\Fixture\ProductController;
use Componenta\ClassFinder\Tests\Fixture\UserService;
use Componenta\ClassFinder\Tests\Fixture\ScanTarget;

describe('suffix pattern (*Controller)', function () {
    it('matches classes ending with the suffix', function (string $class, bool $expected) {
        $filter = new PatternFilter('*Controller');

        expect($filter->accept(ClassInfoFactory::fromClass($class)))->toBe($expected);
    })->with([
        'matches UserController' => [UserController::class, true],
        'matches ProductController' => [ProductController::class, true],
        'rejects UserService' => [UserService::class, false],
    ]);
});

describe('prefix pattern (User*)', function () {
    it('matches classes starting with the prefix', function (string $class, bool $expected) {
        $filter = new PatternFilter('User*');

        expect($filter->accept(ClassInfoFactory::fromClass($class)))->toBe($expected);
    })->with([
        'matches UserController' => [UserController::class, true],
        'matches UserService' => [UserService::class, true],
        'rejects ProductController' => [ProductController::class, false],
    ]);
});

it('matches contains pattern', function () {
    $filter = new PatternFilter('*Contro*');

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeFalse();
});

it('matches exact class name', function () {
    $filter = new PatternFilter('UserController');

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
});

it('matches exact fully qualified class names when the pattern contains namespace separators', function () {
    $filter = new PatternFilter(UserController::class);

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
});

it('matches FQN pattern with backslash in middle', function () {
    $filter = new PatternFilter('*\\Fixture\\User*');

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
});

it('matches single-char wildcard via fnmatch', function () {
    $filter = new PatternFilter('UserControll?r');

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeFalse();
});

it('rejects non-ClassInfo values', function () {
    $filter = new PatternFilter('*Controller');

    expect($filter->accept('not a ClassInfo'))->toBeFalse()
        ->and($filter->accept(null))->toBeFalse();
});

describe('static factories', function () {
    it('contains() matches substring', function () {
        $filter = PatternFilter::contains('Service');

        expect($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeFalse();
    });

    it('startsWith() matches prefix', function () {
        $filter = PatternFilter::startsWith('User');

        expect($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
    });

    it('endsWith() matches suffix', function () {
        $filter = PatternFilter::endsWith('Controller');

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeFalse();
    });

    it('in() matches any value from set', function () {
        $filter = PatternFilter::in(['UserController', 'UserService']);

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
    });

    it('exactMatch() matches exact name', function () {
        $filter = PatternFilter::exactMatch('UserController');

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
    });

    it('namespace() matches namespace prefix and children', function () {
        $filter = PatternFilter::namespace('Componenta\\ClassFinder\\Tests\\Fixture');

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ScanTarget\ScannedClass::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(\stdClass::class)))->toBeFalse();
    });

    it('exactNamespace() matches exact namespace but not children', function () {
        $filter = PatternFilter::exactNamespace('Componenta\\ClassFinder\\Tests\\Fixture');

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ScanTarget\ScannedClass::class)))->toBeFalse()
            ->and($filter->accept(ClassInfoFactory::fromClass(\stdClass::class)))->toBeFalse();
    });

    it('exactFqn() matches exact fully qualified class name', function () {
        $filter = PatternFilter::exactFqn(UserController::class);

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeFalse();
    });

    it('fqn() matches fully qualified wildcard patterns', function () {
        $filter = PatternFilter::fqn('Componenta\\ClassFinder\\Tests\\Fixture\\*Controller');

        expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ProductController::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(UserService::class)))->toBeFalse();
    });
});
