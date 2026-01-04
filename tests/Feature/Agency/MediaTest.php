<?php

namespace Tests\Feature\Agency;

use App\Jobs\ProcessMediaUpload;
use App\Models\Media;
use App\Models\Offering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Feature\Traits\AuthenticationHelpers;
use Tests\Feature\Traits\MediaTestHelpers;
use Tests\Feature\Traits\ResponseHelpers;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase, AuthenticationHelpers, MediaTestHelpers, ResponseHelpers;

    protected function tearDown(): void
    {
        $this->cleanupMediaFiles();

        parent::tearDown();
    }

    // ===== UPLOAD Tests =====

    public function test_agency_can_upload_image_to_real_storage(): void
    {
        Queue::fake();  // We'll process jobs manually

        $agency = $this->actingAsAgency();
        $offering = Offering::factory()->forUser($agency->id)->create();

        $file = $this->uploadFixture('test-photo.jpg');

        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_image',
            'file' => $file,
        ]);

        $this->assertStandardResponse($response);
        $this->assertMediaStructure($response);

        $uuid = $response->json('data.uuid');

        $this->assertDatabaseHas('media', [
            'uuid' => $uuid,
            'mediable_type' => Offering::class,
            'mediable_id' => $offering->id,
            'collection' => 'offering_image',
            'status' => 'uploading',
        ]);

        Queue::assertPushed(ProcessMediaUpload::class);

        $this->processMediaUpload($uuid, $file);

        $media = Media::where('uuid', $uuid)->first();

        $this->assertMediaExists($uuid);

        $realPath = $this->getMediaRealPath($media->path);
        $this->assertGreaterThan(0, filesize($realPath));

        $this->assertDatabaseHas('media', [
            'uuid' => $uuid,
            'status' => 'completed',
        ]);
    }

    public function test_agency_can_upload_video_to_real_storage(): void
    {
        Queue::fake();

        $agency = $this->actingAsAgency();
        $offering = Offering::factory()->forUser($agency->id)->create();

        $file = $this->uploadFixture('test-video.mp4');

        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_video',
            'file' => $file,
        ]);

        $this->assertStandardResponse($response);
        $uuid = $response->json('data.uuid');

        Queue::assertPushed(ProcessMediaUpload::class);

        $this->processMediaUpload($uuid, $file);

        $this->assertMediaExists($uuid);

        $this->assertDatabaseHas('media', [
            'uuid' => $uuid,
            'collection' => 'offering_video',
            'status' => 'completed',
        ]);
    }

    public function test_deleting_media_removes_file_from_real_storage(): void
    {
        Queue::fake();

        $agency = $this->actingAsAgency();
        $offering = Offering::factory()->forUser($agency->id)->create();

        // Use REAL image file from fixtures
        $file = $this->uploadFixture('test-photo.jpg');

        // Upload
        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_image',
            'file' => $file,
        ]);

        $uuid = $response->json('data.uuid');

        $this->processMediaUpload($uuid, $file);

        $this->assertMediaExists($uuid);

        $deleteResponse = $this->getJson($this->mediaDeleteUrl($uuid));

        $deleteResponse->assertStatus(200);

        $this->assertMediaDeleted($uuid);
    }

    // ===== VALIDATION Tests =====

    public function test_file_is_required(): void
    {
        $agency = $this->actingAsAgency();
        $offering = Offering::factory()->forUser($agency->id)->create();

        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_image',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_cannot_upload_duplicate_media_for_same_collection(): void
    {
        Queue::fake();

        $agency = $this->actingAsAgency();
        $offering = Offering::factory()->forUser($agency->id)->create();

        $response1 = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_image',
            'file' => $this->uploadFixture('test-photo.jpg'),
        ]);

        $response1->assertStatus(200);

        $response2 = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_image',
            'file' => $this->uploadFixture('test-photo.jpg'),
        ]);

        $response2->assertStatus(409);
        $this->assertEquals("This entity already has a media file of type 'offering_image'.", $response2->json('errorMessage'));
    }

    // ===== AUTHORIZATION Tests =====

    public function test_agency_cannot_upload_media_for_another_agencys_offering(): void
    {
        $agency1 = $this->actingAsAgency();
        $agency2 = $this->createAgencyUser();
        $offering = Offering::factory()->forUser($agency2->id)->create();

        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => $offering->id,
            'collection' => 'offering_image',
            'file' => $this->uploadFixture('test-photo.jpg'),
        ]);

        $response->assertStatus(404);
        $this->assertEquals('mediable not found.', $response->json('errorMessage'));
    }

    public function test_unauthenticated_user_cannot_upload_media(): void
    {
        $this->actingAsUnauthenticated();

        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => 1,
            'collection' => 'offering_image',
            'file' => $this->uploadFixture('test-photo.jpg'),
        ]);

        $response->assertStatus(401);
        $this->assertEquals('Unauthenticated.', $response->json('message'));
    }

    public function test_customer_cannot_upload_media(): void
    {
        $this->actingAsCustomer();

        $response = $this->postJson($this->mediaUploadUrl(), [
            'entity' => 'offering',
            'entity_id' => 1,
            'collection' => 'offering_image',
            'file' => $this->uploadFixture('test-photo.jpg'),
        ]);

        $response->assertStatus(401);
        $this->assertEquals('You are not allowed to access this page.', $response->json('errorMessage'));
    }
}
