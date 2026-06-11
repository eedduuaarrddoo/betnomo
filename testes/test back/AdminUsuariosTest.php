<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ficha;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private string $adminToken;
    private User $regularUser;
    private string $regularToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin User
        $this->adminUser = User::create([
            'username' => 'admin_user',
            'email'    => 'admin@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
        ]);
        $this->adminToken = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->adminUser);

        // Regular User
        $this->regularUser = User::create([
            'username' => 'regular_user',
            'email'    => 'user@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => false,
        ]);
        $this->regularToken = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($this->regularUser);
    }

    /** @test */
    public function non_admin_cannot_access_usuarios_list()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->regularToken}"])
            ->getJson('/api/admin/usuarios');

        $response->assertStatus(403);
        $response->assertJson([
            'error' => 'Acesso negado. Área restrita.'
        ]);
    }

    /** @test */
    public function admin_can_access_usuarios_list_and_get_correct_format()
    {
        // Add some active and inactive/used fichas for the regular user
        Ficha::create([
            'tipo' => 'A',
            'valor' => 50,
            'token' => 'TOKEN_A1',
            'usada' => false,
            'user_id' => $this->regularUser->id,
        ]);
        Ficha::create([
            'tipo' => 'A',
            'valor' => 50,
            'token' => 'TOKEN_A2',
            'usada' => true, // should not count as active
            'user_id' => $this->regularUser->id,
        ]);
        Ficha::create([
            'tipo' => 'B',
            'valor' => 25,
            'token' => 'TOKEN_B1',
            'usada' => false,
            'user_id' => $this->regularUser->id,
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->adminToken}"])
            ->getJson('/api/admin/usuarios');

        $response->assertStatus(200);
        
        // Assert structure
        $response->assertJsonStructure([
            '*' => [
                'id',
                'username',
                'email',
                'is_admin',
                'fichas_resumo' => [
                    'A',
                    'B',
                    'C',
                ]
            ]
        ]);

        // Find the regular user's data in the response list
        $data = $response->json();
        $regularUserData = collect($data)->firstWhere('username', 'regular_user');

        $this->assertNotNull($regularUserData);
        $this->assertEquals(1, $regularUserData['fichas_resumo']['A']);
        $this->assertEquals(1, $regularUserData['fichas_resumo']['B']);
        $this->assertEquals(0, $regularUserData['fichas_resumo']['C']);
    }
}
