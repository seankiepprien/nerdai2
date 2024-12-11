<?php

namespace Nerd\Nerdai\Classes\tasks;

use Nerd\Inventory\Models\Vehicle;

class VehicleDescriptionTask extends BuildTask
{
    public function __construct()
    {
        parent::__construct('vehicle-description');
    }

    public function makePrompt(string|array $input, $options): string
    {
        if (is_array($input)) {
            $vehicle = Vehicle::find($input[0]); // We only handle one vehicle at a time
            if (!$vehicle) {
                throw new \Exception("Vehicle not found with ID: {$input[0]}");
            }
            return parent::makePrompt($this->formatVehicleData($vehicle->toArray()), $options);
        }

        if (is_string($input) && is_numeric($input)) {
            $vehicle = Vehicle::find($input);
            if (!$vehicle) {
                throw new \Exception("Vehicle not found with ID: {$input}");
            }
            return parent::makePrompt($this->formatVehicleData($vehicle->toArray()), $options);
        }

        return parent::makePrompt($input, $options);
    }

    public function batchProcess(array $vehicleIds): array
    {
        $results = [];
        foreach ($vehicleIds as $id) {
            // Each vehicle gets its own complete prompt cycle
            $results[$id] = $this->getResponse($id);
        }
        return $results;
    }

    protected function formatVehicleData(array $data): string
    {
        $dataString = "";
        $excludeFields = ['created_at', 'updated_at', 'deleted_at', 'id'];

        foreach ($data as $key => $value) {
            if (in_array($key, $excludeFields)) {
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? 'Oui' : 'Non';
            } elseif (is_null($value)) {
                continue;
            }

            $dataString .= "$key: $value\n";
        }

        return $dataString;
    }
}
