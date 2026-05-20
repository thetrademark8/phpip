<?php

use App\Services\LinkGeneratorService;

beforeEach(function () {
    $this->service = new LinkGeneratorService;
});

test('INPI design URL defaults variant to -001 when missing', function () {
    $url = $this->service->generateLink('INPI_DSG', '20242421', 'FR');

    expect($url)->toBe('https://data.inpi.fr/dessins_modeles/FR20242421-001?q=#FR20242421-001');
});

test('INPI design URL preserves explicit variant suffix', function () {
    $url = $this->service->generateLink('INPI_DSG', '20242421-003', 'FR');

    expect($url)->toBe('https://data.inpi.fr/dessins_modeles/FR20242421-003?q=#FR20242421-003');
});

test('INPI design URL strips redundant FR prefix from input', function () {
    $url = $this->service->generateLink('INPI_DSG', 'FR20242421', 'FR');

    expect($url)->toBe('https://data.inpi.fr/dessins_modeles/FR20242421-001?q=#FR20242421-001');
});

test('INPI design URL preserves variant when input has FR prefix', function () {
    $url = $this->service->generateLink('INPI_DSG', 'FR20242421-002', 'FR');

    expect($url)->toBe('https://data.inpi.fr/dessins_modeles/FR20242421-002?q=#FR20242421-002');
});
