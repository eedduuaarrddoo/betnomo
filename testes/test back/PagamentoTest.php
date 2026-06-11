<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Pagamento;
use App\Models\Ficha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PagamentoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Cria o usuário de teste
        $this->user = User::create([
            'username' => 'test_user',
            'email'    => 'test_user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Autentica via JWT e obtém o token
        $this->token = auth('api')->login($this->user);
    }

    /** @test */
    public function can_initiate_pix_purchase()
    {
        // Mock da API do PagSeguro
        Http::fake([
            'sandbox.api.pagseguro.com/*' => Http::response([
                'id' => 'CHAR_TEST_123',
                'status' => 'PENDING',
                'payment_method' => [
                    'type' => 'PIX',
                    'pix' => [
                        'text' => '00020101021226850014br.gov.bcb.pix_test_copia_cola',
                        'expiration_date' => '2026-06-12T15:00:00-03:00',
                        'links' => [
                            [
                                'rel' => 'QRCODE.PNG',
                                'href' => 'https://sandbox.api.pagseguro.com/qrcode/QRCO_TEST/png',
                            ]
                        ]
                    ]
                ]
            ], 201)
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/fichas/iniciar-compra', [
                'tipo' => 'A'
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'referencia',
            'pix_copia_cola',
            'qr_code',
            'valor',
            'status'
        ]);

        $this->assertDatabaseHas('pagamentos', [
            'user_id' => $this->user->id,
            'tipo_ficha' => 'A',
            'status' => 'pendente',
            'pagseguro_charge_id' => 'CHAR_TEST_123',
        ]);
    }

    /** @test */
    public function can_check_status_and_generate_ficha_when_paid()
    {
        // Cria um pagamento pendente no banco
        $pagamento = Pagamento::create([
            'user_id' => $this->user->id,
            'tipo_ficha' => 'B',
            'valor' => 2500,
            'referencia' => 'referencia-teste-status',
            'pagseguro_charge_id' => 'CHAR_TEST_456',
            'status' => 'pendente',
            'expira_em' => now()->addMinutes(30),
        ]);

        // Mock do status consultado no PagSeguro como PAID (pago)
        Http::fake([
            'sandbox.api.pagseguro.com/*' => Http::response([
                'id' => 'CHAR_TEST_456',
                'status' => 'PAID',
            ], 200)
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->getJson("/api/fichas/compra-status/{$pagamento->referencia}");

        $response->assertStatus(200);
        $response->assertJson([
            'referencia' => $pagamento->referencia,
            'status' => 'pago',
        ]);

        // Verifica se a ficha foi criada no banco de dados e associada ao pagamento
        $this->assertDatabaseHas('pagamentos', [
            'id' => $pagamento->id,
            'status' => 'pago',
        ]);

        $updatedPagamento = Pagamento::find($pagamento->id);
        $this->assertNotNull($updatedPagamento->ficha_id);

        $this->assertDatabaseHas('fichas', [
            'id' => $updatedPagamento->ficha_id,
            'user_id' => $this->user->id,
            'tipo' => 'B',
            'usada' => false,
        ]);
    }

    /** @test */
    public function can_receive_webhook_and_confirm_payment()
    {
        // Cria um pagamento pendente
        $pagamento = Pagamento::create([
            'user_id' => $this->user->id,
            'tipo_ficha' => 'C',
            'valor' => 500,
            'referencia' => 'referencia-webhook-test',
            'pagseguro_charge_id' => 'CHAR_TEST_WEBHOOK',
            'status' => 'pendente',
            'expira_em' => now()->addMinutes(30),
        ]);

        $payload = [
            'id' => 'CHAR_TEST_WEBHOOK',
            'reference_id' => 'referencia-webhook-test',
            'status' => 'PAID',
        ];

        // Envia requisição sem assinatura (deve funcionar no sandbox/local)
        $response = $this->postJson('/api/pagamentos/webhook', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Status processado com sucesso.'
        ]);

        $this->assertDatabaseHas('pagamentos', [
            'id' => $pagamento->id,
            'status' => 'pago',
        ]);

        $updatedPagamento = Pagamento::find($pagamento->id);
        $this->assertNotNull($updatedPagamento->ficha_id);
    }
}
