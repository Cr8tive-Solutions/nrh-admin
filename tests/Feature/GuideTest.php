<?php

use App\Support\AdminGuide;
use Tests\Support\Fixtures;

it('redirects guests to login', function () {
    $this->get('/guide')->assertRedirect(route('login'));
});

it('is readable by every role, including viewer', function () {
    $admin = Fixtures::admin(['role' => 'viewer']);

    Fixtures::actingAs($this, $admin)
        ->get('/guide')
        ->assertOk()
        ->assertSee('NRH Admin Portal')
        ->assertSee('Recording Findings');
});

it('renders the guide as a PDF', function () {
    $admin = Fixtures::admin(['role' => 'viewer']);

    $response = Fixtures::actingAs($this, $admin)->get('/guide/download');

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');

    // dompdf output, not an error page rendered with a PDF content type.
    expect(substr($response->streamedContent() ?: $response->getContent(), 0, 5))->toBe('%PDF-');
});

it('gives every section a unique anchor so the contents page links resolve', function () {
    $ids = array_column(AdminGuide::sections(), 'id');

    expect($ids)->not->toBeEmpty()
        ->and(array_unique($ids))->toHaveCount(count($ids))
        ->and(array_filter($ids, fn ($id) => $id === ''))->toBeEmpty();
});

it('only references routes that exist', function () {
    foreach (AdminGuide::sections() as $section) {
        foreach ($section['blocks'] as $block) {
            if (($block['type'] ?? null) !== 'links') {
                continue;
            }
            foreach ($block['items'] as $link) {
                expect(fn () => route($link['route']))->not->toThrow(Exception::class);
            }
        }
    }
});
