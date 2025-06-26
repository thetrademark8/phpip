<?php

use App\Contracts\Services\DateServiceInterface;
use App\Contracts\Services\MatterServiceInterface;
use App\Contracts\Services\RenewalServiceInterface;
use App\Contracts\Services\NotificationServiceInterface;
use App\Services\DateService;

test('date service is properly registered', function () {
    $service = app(DateServiceInterface::class);
    
    expect($service)->toBeInstanceOf(DateService::class);
});

test('matter service interface is registered', function () {
    $service = app(MatterServiceInterface::class);
    
    expect($service)->toBeInstanceOf(MatterServiceInterface::class);
});

test('renewal service interface is registered', function () {
    $service = app(RenewalServiceInterface::class);
    
    expect($service)->toBeInstanceOf(RenewalServiceInterface::class);
});

test('notification service interface is registered', function () {
    $service = app(NotificationServiceInterface::class);
    
    expect($service)->toBeInstanceOf(NotificationServiceInterface::class);
});

test('services can be injected into controllers', function () {
    $controller = new class(app(DateServiceInterface::class)) {
        public function __construct(
            public DateServiceInterface $dateService
        ) {}
    };
    
    expect($controller->dateService)->toBeInstanceOf(DateService::class);
});