<?php

use App\Enums\AnnouncementStatus;
use App\Jobs\BroadcastMessageJob;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DefStudio\Telegraph\Facades\Telegraph;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create test users
    $this->user = User::factory()->create();
});

describe('BroadcastMessageJob', function () {
    it('can broadcast message without attachment successfully', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'body' => 'Test announcement message',
            'has_attachment' => false,
            'file_path' => null,
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB query to return test clients
        DB::shouldReceive('query')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->once()
            ->with('chat_id')
            ->andReturnSelf();
        DB::shouldReceive('from')
            ->once()
            ->with('telegraph_chats')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->once()
            ->andReturn(collect([
                (object) ['chat_id' => '123456789'],
                (object) ['chat_id' => '987654321'],
                (object) ['chat_id' => '555666777'],
            ]));

        // Properly mock the Telegraph facade chain
        Telegraph::shouldReceive('chat')->andReturnSelf();
        Telegraph::shouldReceive('html')->andReturnSelf();
        Telegraph::shouldReceive('send')->andReturn(true);

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::SENT);
    });

    it('can broadcast message with attachment successfully', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'body' => 'Test announcement with attachment',
            'has_attachment' => true,
            'file_path' => 'announcements/test-image.jpg',
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB query to return test clients
        DB::shouldReceive('query')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->once()
            ->with('chat_id')
            ->andReturnSelf();
        DB::shouldReceive('from')
            ->once()
            ->with('telegraph_chats')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->once()
            ->andReturn(collect([
                (object) ['chat_id' => '123456789'],
                (object) ['chat_id' => '987654321'],
            ]));

        // Properly mock the Telegraph facade chain
        Telegraph::shouldReceive('chat')->andReturnSelf();
        Telegraph::shouldReceive('html')->andReturnSelf();
        Telegraph::shouldReceive('photo')->andReturnSelf();
        Telegraph::shouldReceive('send')->andReturn(true);

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::SENT);
    });

    it('handles empty clients list gracefully', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'body' => 'Test announcement no clients',
            'has_attachment' => false,
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB query to return empty collection
        DB::shouldReceive('query')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->once()
            ->with('chat_id')
            ->andReturnSelf();
        DB::shouldReceive('from')
            ->once()
            ->with('telegraph_chats')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->once()
            ->andReturn(collect([]));

        // Mock Telegraph facade (should not be called, but safe to mock)
        Telegraph::shouldReceive('chat')->andReturnSelf();
        Telegraph::shouldReceive('html')->andReturnSelf();
        Telegraph::shouldReceive('send')->andReturn(true);
        Telegraph::shouldReceive('photo')->andReturnSelf();

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::SENT);
    });

    it('sets locale to Russian before processing', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'body' => 'Test announcement',
            'has_attachment' => false,
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB query to return test clients
        DB::shouldReceive('query')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->once()
            ->with('chat_id')
            ->andReturnSelf();
        DB::shouldReceive('from')
            ->once()
            ->with('telegraph_chats')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->once()
            ->andReturn(collect([
                (object) ['chat_id' => '123456789'],
            ]));

        // Properly mock the Telegraph facade chain
        Telegraph::shouldReceive('chat')->andReturnSelf();
        Telegraph::shouldReceive('html')->andReturnSelf();
        Telegraph::shouldReceive('send')->andReturn(true);

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect(app()->getLocale())->toBe('ru');
    });

    it('handles database errors gracefully', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'body' => 'Test announcement database error',
            'has_attachment' => false,
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB to throw exception
        DB::shouldReceive('query')->andThrow(new Exception('Database error'));

        // Mock Log facade to capture errors
        Log::shouldReceive('error')->andReturn(true);

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::FAILED);
    });
});

describe('BroadcastMessageJob Constructor', function () {
    it('accepts announcement in constructor', function () {
        // Arrange
        $announcement = Announcement::factory()->create();

        // Act
        $job = new BroadcastMessageJob($announcement);

        // Assert
        expect($job->announcement)->toBe($announcement);
    });
});

describe('BroadcastMessageJob Queue Implementation', function () {
    it('implements ShouldQueue interface', function () {
        // Arrange & Act
        $announcement = Announcement::factory()->create();
        $job = new BroadcastMessageJob($announcement);

        // Assert
        expect($job)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    it('uses Queueable trait', function () {
        // Arrange & Act
        $announcement = Announcement::factory()->create();
        $job = new BroadcastMessageJob($announcement);

        // Assert
        expect(method_exists($job, 'onQueue'))->toBeTrue();
        expect(method_exists($job, 'onConnection'))->toBeTrue();
    });
});

describe('BroadcastMessageJob Status Updates', function () {
    it('updates status to SENT when successful', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB query to return test clients
        DB::shouldReceive('query')
            ->once()
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->once()
            ->with('chat_id')
            ->andReturnSelf();
        DB::shouldReceive('from')
            ->once()
            ->with('telegraph_chats')
            ->andReturnSelf();
        DB::shouldReceive('get')
            ->once()
            ->andReturn(collect([
                (object) ['chat_id' => '123456789'],
            ]));

        // Properly mock the Telegraph facade chain
        Telegraph::shouldReceive('chat')->andReturnSelf();
        Telegraph::shouldReceive('html')->andReturnSelf();
        Telegraph::shouldReceive('send')->andReturn(true);

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::SENT);
    });

    it('updates status to FAILED when exception occurs', function () {
        // Arrange
        $announcement = Announcement::factory()->create([
            'status' => AnnouncementStatus::IN_PROGRESS,
        ]);

        // Mock DB to throw exception
        DB::shouldReceive('query')->andThrow(new Exception('Database error'));

        // Mock Log facade
        Log::shouldReceive('error')->andReturn(true);

        // Act
        $job = new BroadcastMessageJob($announcement);
        $job->handle();

        // Assert
        expect($announcement->fresh()->status)->toBe(AnnouncementStatus::FAILED);
    });
}); 