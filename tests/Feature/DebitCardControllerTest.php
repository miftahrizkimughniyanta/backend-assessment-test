<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use Tests\TestCase;

class DebitCardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCards()
    {
        // get /debit-cards
        DebitCard::factory()->count(3)->for($this->user)->create([
            'disabled_at' => null,
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertCount(3, $json);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        // get /debit-cards
        $otherUser = User::factory()->create();

        DebitCard::factory()->count(3)->for($otherUser)->create();

        DebitCard::factory()->count(2)->for($this->user)->create([
            'disabled_at' => null,
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertCount(2, $json);
    }
    

    public function testCustomerCanCreateADebitCard()
    {
        // post /debit-cards
        $payload = [
            'type' => 'debit',
            'expiration_date' => now()->addYear()->toDateString(),
        ];

        $response = $this->postJson('/api/debit-cards', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('debit_cards', [
            'user_id' => $this->user->id,
        ]);
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(200);

        $data = $response->json();

        $this->assertEquals($debitCard->id, $data['id']);
        $this->assertEquals($debitCard->number, $data['number']);
        $this->assertEquals($debitCard->type, $data['type']);
        $this->assertEquals($this->user->id, $data['user_id'] ?? $debitCard->user_id);
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
        $otherUser = User::factory()->create();

        $debitCard = DebitCard::factory()->for($otherUser)->create();

        $response = $this->getJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $payload = [
            'is_active' => true,
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $payload);

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertTrue($json['is_active']);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
            'disabled_at' => null,
        ]);
    }

    public function testCustomerCanDeactivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $payload = [
            'is_active' => false,
        ];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $payload);

        $response->assertStatus(200);

        $debitCard->refresh();
        $this->assertNotNull($debitCard->disabled_at);
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $originalDisabledAt = $debitCard->disabled_at;
        $payload = ['is_active' => 'invalid_string'];

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['is_active']);

        $debitCard->refresh();
        $this->assertEquals($originalDisabledAt, $debitCard->disabled_at);
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
        $debitCard = DebitCard::factory()->for($this->user)->create();
        DebitCardTransaction::factory()->for($debitCard)->create();

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotDeactivateAnotherUsersDebitCard()
    {
        $otherUser = User::factory()->create();

        $debitCard = DebitCard::factory()->for($otherUser)->create();

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false
        ]);

        $response->assertStatus(403);

        $debitCard->refresh();
    }
    
    public function testCustomerCannotDeleteAnotherUsersDebitCard()
    {
        $otherUser = User::factory()->create();

        $debitCard = DebitCard::factory()->for($otherUser)->create();

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
        ]);
    }

    public function testCustomerCannotActivateExpiredDebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create([
            'expiration_date' => now()->subDay(),
        ]);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true,
        ]);

        $response->assertStatus(200);

        $debitCard->refresh();

        $this->assertDatabaseHas('debit_cards', [
            'id' => $debitCard->id,
        ]);

        $data = $response->json();
        $this->assertArrayHasKey('is_active', $data);
    }

    public function testCustomerCannotCreateDebitCardWithoutType()
    {
        $response = $this->postJson('/api/debit-cards', []);

        $response->assertStatus(422);
    }
    
    public function testCustomerCannotDeleteAlreadyDeletedDebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();
        $debitCard->delete();

        $response = $this->deleteJson("/api/debit-cards/{$debitCard->id}");

        $response->assertStatus(404);
    }

    public function testCustomerCanActivateAndDeactivateDebitCard()
    {
        $debitCard = DebitCard::factory()->for($this->user)->create();

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => true,
        ]);
        $response->assertStatus(200);

        $response = $this->putJson("/api/debit-cards/{$debitCard->id}", [
            'is_active' => false,
        ]);
        $response->assertStatus(200);
    }
}
