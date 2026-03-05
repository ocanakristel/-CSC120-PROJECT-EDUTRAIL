<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class AuthUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        // run the migrations for a clean database
        $this->artisan('migrate');
    }

    /**
     * Ensure updating with an empty payload does not crash.
     */
    public function test_update_with_empty_payload_returns_success()
    {
        $user = User::factory()->create([
            'firstname' => 'Foo',
            'lastname'  => 'Bar',
            'email'     => 'foo@example.com',
        ]);

        $this->actingAs($user)
             ->postJson('/api/auth/update', [])
             ->assertStatus(200)
             ->assertJson(['error' => null]);
    }

    /**
     * Sending only firstname should not trigger an undefined index error.
     */
    public function test_partial_update_firstname_only()
    {
        $user = User::factory()->create([
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'email'     => 'john@example.com',
        ]);

        $response = $this->actingAs($user)
                         ->postJson('/api/auth/update', ['firstname' => 'Jack']);

        $response->assertStatus(200)
                 ->assertJsonPath('data.user.firstname', 'Jack')
                 ->assertJsonPath('data.user.lastname', 'Doe');
    }

    /**
     * Full name field should be splittable by front-end logic; backend stores
     * each piece independently. This test exercises logic indirectly by
     * mimicking the payload constructed by the client.
     */
    public function test_update_splitted_fullname()
    {
        $user = User::factory()->create([
            'firstname' => 'Will',
            'lastname'  => 'Smith',
            'email'     => 'will@example.com',
        ]);

        $this->actingAs($user)
             ->postJson('/api/auth/update', ['firstname' => 'Will', 'lastname' => 'Smith Jr.'])
             ->assertStatus(200)
             ->assertJsonPath('data.user.lastname', 'Smith Jr.');
    }

    /**
     * Behavior when the database is missing profile columns (migration not run).
     * Controller should return a clear 500 message instead of collapsing.
     */
    public function test_update_with_missing_columns_reports_schema_error()
    {
        $user = User::factory()->create();
        // artificially drop the profile columns to simulate an out-of-date
        // schema.  RefreshDatabase already ran migrations so they exist.
        Schema::table('users', function (Blueprint $table) {
            $cols = ['firstname','lastname','nickname','gender','contact_number'];
            foreach ($cols as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $table->dropColumn($c);
                }
            }
        });

        $this->actingAs($user)
             ->postJson('/api/auth/update', ['firstname' => 'Foo'])
             ->assertStatus(500)
             ->assertJsonPath('error.message', 'Database schema not up to date')
             ->assertJsonPath('error.details',
                 'Missing columns: firstname, lastname, nickname, gender, contact_number. Run migrations.');
    }
}
