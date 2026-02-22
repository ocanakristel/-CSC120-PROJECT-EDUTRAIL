<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProjectControllerTest extends TestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_creates_project_with_user_id()
    {
        $response = $this->postJson('/api/projects', [
            'description' => 'Test Project',
            'due_date' => '2026-02-20',
            'due_time' => '14:00',
            'steps' => [],
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['project' => ['id', 'user_id']]]);
        $this->assertEquals($this->user->id, $response['data']['project']['user_id']);
    }

    /** @test */
    public function it_uploads_image_with_project()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.png');

        $response = $this->postJson('/api/projects', [
            'description' => 'Test Project',
            'due_date' => '2026-02-20',
            'file' => $file,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['image_public_url']]);
        $this->assertNotNull($response['data']['image_public_url']);
    }

    /** @test */
    public function it_returns_correct_project_count()
    {
        Project::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/projects/count');

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    }

    /** @test */
    public function it_returns_latest_projects()
    {
        Project::factory()->count(6)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/projects/latest');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data' => []]);
        $this->assertLessThanOrEqual(5, count($response['data']));
    }

    /** @test */
    public function it_returns_project_summary()
    {
        Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/projects/summary');

        $response->assertStatus(200);
        $response->assertJsonStructure(['total', 'completed', 'pending']);
    }

    /** @test */
    public function upload_returns_compatible_response_shape()
    {
        Storage::fake('public');
        $file = UploadedFile::fake()->image('test.png');

        $response = $this->postJson('/api/storage/edutrail/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        // Check both old and new response shapes
        $this->assertTrue(
            isset($response['publicUrl']) || isset($response['data']['publicUrl'])
        );
    }
}
