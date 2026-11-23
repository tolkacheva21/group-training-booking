<?php

namespace Tests;

use Database\Seeders\StatusSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Record statuses are reference data required by every record.
     */
    protected $seed = true;

    protected $seeder = StatusSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Тесты работают в UTC, чтобы данные, построенные через now(), совпадали
        // с временем, которое формы отправляют и получают.
        config(['app.display_timezone' => 'UTC']);
    }
}
