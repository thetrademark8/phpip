<?php

namespace Tests\Traits;

trait FunctionalTestDatabase
{
    // This trait is now empty as we're using standard SQLite for testing
    // When MySQL is available, you can uncomment the following:
    
    /*
    protected function setUpTraits(): void
    {
        parent::setUpTraits();
        
        // Switch to functional database connection
        config(['database.default' => 'mysql_functional']);
        \Illuminate\Support\Facades\DB::setDefaultConnection('mysql_functional');
    }
    
    protected function connectionsToTransact(): array
    {
        return ['mysql_functional'];
    }
    */
}