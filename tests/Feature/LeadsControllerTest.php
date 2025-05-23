<?php

use App\Models\Lead;
use App\Libraries\Mailchimp;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->mailchimpMock = \Mockery::mock('overload:App\Libraries\Mailchimp');
    app()->instance(Mailchimp::class, $this->mailchimpMock);

    $this->dbMock = Mockery::mock('alias:Illuminate\Support\Facades\DB');
    $this->dbMock->shouldReceive('beginTransaction')->andReturn(null);
    $this->dbMock->shouldReceive('commit')->andReturn(null);
    $this->dbMock->shouldReceive('rollBack')->andReturn(null);
});

afterEach(function () {
    Mockery::close();
});

test('store method creates a new lead and subscribes to mailchimp', function () {
    $this->mailchimpMock->shouldReceive('subscribe')
        ->once()
        ->with('john@example.com', ['FNAME' => 'John', 'LNAME' => 'Doe'])
        ->andReturn(true);

    $response = $this->post('/leads', [
        'email' => 'john@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'allowSendEmails' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Lead added successfully');

    $this->assertDatabaseHas('leads', [
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);
});

test('store method validates required fields', function () {
    $response = $this->post('/leads', []);

    $response->assertSessionHasErrors(['email', 'firstName', 'lastName', 'allowSendEmails']);
});

test('store method validates email format', function () {
    $response = $this->post('/leads', [
        'email' => 'invalid-email',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'allowSendEmails' => true,
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('store method validates email uniqueness', function () {
    Lead::query()->create([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);

    $response = $this->post('/leads', [
        'email' => 'john@example.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'allowSendEmails' => true,
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('store method handles mailchimp exception', function () {
    $this->mailchimpMock->shouldReceive('subscribe')
        ->once()
        ->andThrow(new Exception('Mailchimp error'));

    $response = $this->post('/leads', [
        'email' => 'john@test.com',
        'firstName' => 'John',
        'lastName' => 'Doe',
        'allowSendEmails' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['message']);
});

test('update method updates a lead and updates mailchimp subscription', function () {
    $lead = Lead::query()->create([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);

    $this->mailchimpMock->shouldReceive('unsubscribe')
        ->once()
        ->with('john@example.com')
        ->andReturn(true);

    $this->mailchimpMock->shouldReceive('subscribe')
        ->once()
        ->with('jane@example.com', ['FNAME' => 'Jane', 'LNAME' => 'Smith'])
        ->andReturn(true);

    $response = $this->put("/leads/{$lead->id}", [
        'email' => 'jane@example.com',
        'firstName' => 'Jane',
        'lastName' => 'Smith',
        'allowSendEmails' => false,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Lead updated successfully');

    $this->assertDatabaseHas('leads', [
        'id' => $lead->id,
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'allow_send_emails' => false,
    ]);
});

test('update method returns error when lead not found', function () {
    $response = $this->put('/leads/999', [
        'email' => 'jane@example.com',
        'firstName' => 'Jane',
        'lastName' => 'Smith',
        'allowSendEmails' => false,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['message']);
});

test('update method validates required fields', function () {
    $lead = Lead::query()->create([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);

    $response = $this->put("/leads/{$lead->id}", []);

    $response->assertSessionHasErrors(['email', 'firstName', 'lastName', 'allowSendEmails']);
});

test('update method handles mailchimp exception', function () {
    $lead = Lead::query()->create([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);

    $this->mailchimpMock->shouldReceive('unsubscribe')
        ->once()
        ->andThrow(new Exception('Mailchimp error'));

    $response = $this->put("/leads/{$lead->id}", [
        'email' => 'jane@example.com',
        'firstName' => 'Jane',
        'lastName' => 'Smith',
        'allowSendEmails' => false,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['message']);

    $this->assertDatabaseHas('leads', [
        'id' => $lead->id,
        'email' => 'john@example.com',
    ]);
});

test('destroy method deletes a lead and unsubscribes from mailchimp', function () {
    $lead = Lead::query()->create([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);

    $this->mailchimpMock->shouldReceive('unsubscribe')
        ->once()
        ->with('john@example.com')
        ->andReturn(true);

    $response = $this->delete("/leads/{$lead->id}");

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Lead deleted successfully');

    $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
});

test('destroy method returns error when lead not found', function () {
    $response = $this->delete('/leads/999');

    $response->assertRedirect();
    $response->assertSessionHasErrors(['message']);
});

test('destroy method handles mailchimp exception', function () {
    $lead = Lead::query()->create([
        'email' => 'john@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'allow_send_emails' => true,
    ]);

    $this->mailchimpMock->shouldReceive('unsubscribe')
        ->once()
        ->andThrow(new Exception('Mailchimp error'));

    $response = $this->delete("/leads/{$lead->id}");

    $response->assertRedirect();
    $response->assertSessionHasErrors(['message']);
});
