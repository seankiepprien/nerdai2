<?php

namespace Nerd\Nerdai\Classes;

use Nerd\Inventory\Models\Vehicle;
use Nerd\Nerdai\Classes\ClientInterface;

interface AIModelInterface
{
    public static function formatInput(string $prompt, string $mode, TaskInterface $task, Vehicle $vehicle, array $parameters = [],): array;
    public static function formatResponse(array $response, string $logId, array $parameters = []): array;
}
