<?php

namespace App\Services;

use App\Models\Students;

class TokenService
{
    /**
     * Generate API token for student
     * 
     * @param Students $student
     * @return string Plain text token
     */
    public function generateToken(Students $student): string
    {
        // Create a new Sanctum token for the student
        // Token name is set to 'api-token' and has full abilities (*)
        $token = $student->createToken('api-token', ['*']);
        
        // Return the plain text token (this is what the client will use)
        return $token->plainTextToken;
    }
    
    /**
     * Revoke current token
     * 
     * @param Students $student
     * @return void
     */
    public function revokeCurrentToken(Students $student): void
    {
        // Delete the current access token being used for this request
        // This is typically called during logout
        $student->currentAccessToken()->delete();
    }
}
