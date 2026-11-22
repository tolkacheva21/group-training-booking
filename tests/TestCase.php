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
}
