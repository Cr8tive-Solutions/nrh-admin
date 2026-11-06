<?php

use Tests\Support\Fixtures;

it('builds avatar initials from the first two words, or two letters of one word', function (?string $name, string $expected) {
    expect(initials($name, 'A'))->toBe($expected);
})->with([
    'two words' => ['Anwar Saad', 'AS'],
    'longer name' => ['Ahmad Faizal bin Rahman', 'AF'],
    'one word' => ['Kadboard', 'KA'],
    'extra spaces' => ['  anwar   saad ', 'AS'],
    'empty' => ['', 'A'],
    'null' => [null, 'A'],
]);

it('shows the signed-in admin initials when no picture is uploaded', function () {
    Fixtures::seedPermissions();
    $admin = Fixtures::admin(['name' => 'Anwar Saad', 'role' => 'super_admin']);

    Fixtures::actingAs($this, $admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSeeInOrder(['sb-avatar', 'AS'], false)
        ->assertDontSee('>AN<', false);
});
