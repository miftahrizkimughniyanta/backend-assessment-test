<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
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
            'user_id' => $this->user->id,
            'number' => '1234567890123456',
            'type' => 'debit',
            'disabled_at' => null,
            'expiration_date' => now()->addYear(),
        ]);

        $response = $this->getJson('/api/debit-cards');

        $response->assertStatus(200);

        $json = $response->json();

        $this->assertCount(3, $json);
    }

    public function testCustomerCannotSeeAListOfDebitCardsOfOtherCustomers()
    {
        $otherUser = User::factory()->create();

        DebitCard::factory()->count(3)->for($otherUser)->create([
            'number' => '1111222233334444',
            'type' => 'debit',
            'expiration_date' => now()->addYear(),
            'disabled_at' => null,
        ]);

        DebitCard::factory()->count(2)->for($this->user)->create([
            'number' => '5555666677778888',
            'type' => 'debit',
            'expiration_date' => now()->addYear(),
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
    }

    public function testCustomerCanSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCannotSeeASingleDebitCardDetails()
    {
        // get api/debit-cards/{debitCard}
    }

    public function testCustomerCanActivateADebitCard()
    {
        // put api/debit-cards/{debitCard}
        $this->actingAs($this->user);
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
    }

    public function testCustomerCannotUpdateADebitCardWithWrongValidation()
    {
        // put api/debit-cards/{debitCard}
    }

    public function testCustomerCanDeleteADebitCard()
    {
        // delete api/debit-cards/{debitCard}
    }

    public function testCustomerCannotDeleteADebitCardWithTransaction()
    {
        // delete api/debit-cards/{debitCard}
    }

    // Extra bonus for extra tests :)
}
