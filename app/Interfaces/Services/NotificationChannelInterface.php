<?php

namespace App\Interfaces\Services;

interface NotificationChannelInterface
{
    /**
     * Send notification to destination
     */
    public function send(string $destination, string $message): bool;

    /**
     * Check if channel is available
     */
    public function isAvailable(): bool;

    /**
     * Get channel name
     */
    public function getName(): string;
}
