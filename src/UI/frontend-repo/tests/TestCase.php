<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use BaseballAnalytics\Database\Connection;
use Mockery;

abstract class TestCase extends BaseTestCase
{
    protected ?Connection $db = null;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set up database connection for tests if needed
        if (method_exists($this, 'needsDatabase') && $this->needsDatabase()) {
            $this->db = new Connection([
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => ''
            ]);
        }
    }
    
    protected function tearDown(): void
    {
        // Close database connection if it exists
        if ($this->db !== null) {
            $this->db = null;
        }
        
        // Clean up Mockery
        Mockery::close();
        
        parent::tearDown();
    }
    
    protected function createMock($class)
    {
        return Mockery::mock($class);
    }
    
    protected function needsDatabase(): bool
    {
        return false;
    }
} 